<?php

/**
 * Invoice Ninja (https://invoiceninja.com).
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2021. Invoice Ninja LLC (https://invoiceninja.com)
 *
 * @license https://www.elastic.co/licensing/elastic-license
 */

namespace App\PaymentDrivers\PayFast;

use App\Jobs\Mail\PaymentFailureMailer;
use App\Jobs\Util\SystemLogger;
use App\Models\ClientGatewayToken;
use App\Models\Payment;
use App\Models\PaymentHash;
use App\Models\SystemLog;
use App\PaymentDrivers\PayFastPaymentDriver;
use GuzzleHttp\RequestOptions;

class Token
{
    public $payfast;

    //https://api.payfast.co.za/subscriptions/dc0521d3-55fe-269b-fa00-b647310d760f/adhoc

    public function __construct(PayFastPaymentDriver $payfast)
    {
        $this->payfast = $payfast;
    }

    // Attributes
    // merchant-id
    // integer, 8 char | REQUIRED
    // Header, the Merchant ID as given by the PayFast system.
    // version
    // string | REQUIRED
    // Header, the PayFast API version (i.e. v1).
    // timestamp
    // ISO-8601 date and time | REQUIRED
    // Header, the current timestamp (YYYY-MM-DDTHH:MM:SS[+HH:MM]).
    // signature
    // string | REQUIRED
    // Header, MD5 hash of the alphabetised submitted header and body variables, as well as the passphrase. Characters must be in lower case.
    // amount
    // integer | REQUIRED
    // Body, the amount which the buyer must pay, in cents (ZAR), no decimals.
    // item_name
    // string, 100 char | REQUIRED
    // Body, the name of the item being charged for.
    // item_description
    // string, 255 char | OPTIONAL
    // Body, the description of the item being charged for.
    // itn
    // boolean | OPTIONAL
    // Body, specify whether an ITN must be sent for the tokenization payment (true by default).
    // m_payment_id
    // string, 100 char | OPTIONAL
    // Body, unique payment ID on the merchant’s system.
    // cc_cvv
    // numeric | OPTIONAL


    public function tokenBilling(ClientGatewayToken $cgt, PaymentHash $payment_hash)
    {
        $amount = array_sum(array_column($payment_hash->invoices(), 'amount')) + $payment_hash->fee_total;
        $amount = round(($amount * pow(10, $this->payfast->client->currency()->precision)), 0);

        $header =[
            'merchant-id' => $this->payfast->company_gateway->getConfigField('merchantId'),
            'version' => 'v1',
            'timestamp' => now()->format('c'),
        ];

        $body = [
            'amount' => $amount,
            'item_name' => 'purchase',
            'item_description' => ctrans('texts.invoices') . ': ' . collect($payment_hash->invoices())->pluck('invoice_number'),
            'm_payment_id' => $payment_hash->hash,
        ];

        nlog(array_merge($body, $header));

        // $header['signature'] = md5( $this->generate_parameter_string(array_merge($header, $body), false) );
        
        $header['signature'] = $this->payfast->generateTokenSignature(array_merge($body, $header));

        nlog($header['signature']);

        $result = $this->send($header, $body, $cgt->token);

        nlog($result);

        //     $api = new \PayFast\PayFastApi(
        //         [
        //             'merchantId' => $this->payfast->company_gateway->getConfigField('merchantId'),
        //             'passPhrase' => $this->payfast->company_gateway->getConfigField('passPhrase'),
        //             'testMode' => $this->payfast->company_gateway->getConfigField('testMode')
        //         ]
        //     );

        //     $adhocArray = $api
        //                ->subscriptions
        //                ->adhoc($cgt->token, ['amount' => $amount, 'item_name' => 'purchase']);


        // nlog($adhocArray);


        
        // /*Refactor and push to BaseDriver*/
        // if ($data['response'] != null && $data['response']->getMessages()->getResultCode() == 'Ok') {

        //     $response = $data['response'];

        //     $this->storePayment($payment_hash, $data);

        //     $vars = [
        //         'invoices' => $payment_hash->invoices(),
        //         'amount' => $amount,
        //     ];

        //     $logger_message = [
        //         'server_response' => $response->getTransactionResponse()->getTransId(),
        //         'data' => $this->formatGatewayResponse($data, $vars),
        //     ];

        //     SystemLogger::dispatch($logger_message, SystemLog::CATEGORY_GATEWAY_RESPONSE, SystemLog::EVENT_GATEWAY_SUCCESS, SystemLog::TYPE_AUTHORIZE, $this->authorize->client, $this->authorize->client->company);

        //     return true;
        // } else {

        //     $vars = [
        //         'invoices' => $payment_hash->invoices(),
        //         'amount' => $amount,
        //     ];

        //     $logger_message = [
        //         'server_response' => $response->getTransactionResponse()->getTransId(),
        //         'data' => $this->formatGatewayResponse($data, $vars),
        //     ];

        //     PaymentFailureMailer::dispatch($this->authorize->client, $response->getTransactionResponse()->getTransId(), $this->authorize->client->company, $amount);

        //     SystemLogger::dispatch($logger_message, SystemLog::CATEGORY_GATEWAY_RESPONSE, SystemLog::EVENT_GATEWAY_FAILURE, SystemLog::TYPE_AUTHORIZE, $this->authorize->client, $this->authorize->client->company);

        //     return false;
        // }
    }

    protected function generate_parameter_string($api_data, $sort_data_before_merge = true, $skip_empty_values = true)
    {

        // if sorting is required the passphrase should be added in before sort.
        if (! empty($this->payfast->company_gateway->getConfigField('passphrase')) && $sort_data_before_merge) {
            $api_data['passphrase'] = $this->payfast->company_gateway->getConfigField('passphrase');
        }

        if ($sort_data_before_merge) {
            ksort($api_data);
        }

        // concatenate the array key value pairs.
        $parameter_string = '';
        foreach ($api_data as $key => $val) {
            if ($skip_empty_values && empty($val)) {
                continue;
            }

            if ('signature' !== $key) {
                $val = urlencode($val);
                $parameter_string .= "$key=$val&";
            }
        }
        // when not sorting passphrase should be added to the end before md5
        if ($sort_data_before_merge) {
            $parameter_string = rtrim($parameter_string, '&');
        } elseif (! empty($this->pass_phrase)) {
            $parameter_string .= 'passphrase=' . urlencode($this->payfast->company_gateway->getConfigField('passphrase'));
        } else {
            $parameter_string = rtrim($parameter_string, '&');
        }

        nlog($parameter_string);

        return $parameter_string;
    }

    private function genSig($data)
    {
        $fields = [];

        ksort($data);

        foreach ($data as $key => $value) {
            if (!empty($data[$key])) {
                $fields[$key] = $data[$key];
            }
        }

        nlog(http_build_query($fields));

        return md5(http_build_query($fields));
    }

    private function send($headers, $body, $token)
    {
        $client =  new \GuzzleHttp\Client(
            [
            'headers' => $headers,
        ]
        );

        try {
            $response = $client->post("https://api.payfast.co.za/subscriptions/{$token}/adhoc?testing=true", [
                RequestOptions::JSON => ['body' => $body], RequestOptions::ALLOW_REDIRECTS => false
            ]);

            return json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            nlog($e->getMessage());
        }
    }
}
