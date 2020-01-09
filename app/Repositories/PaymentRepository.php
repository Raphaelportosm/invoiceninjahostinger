<?php
/**
 * Invoice Ninja (https://invoiceninja.com)
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2020. Invoice Ninja LLC (https://invoiceninja.com)
 *
 * @license https://opensource.org/licenses/AAL
 */

namespace App\Repositories;

use App\Events\Payment\PaymentWasCreated;
use App\Factory\CreditFactory;
use App\Jobs\Client\UpdateClientPaidToDate;
use App\Jobs\Company\UpdateCompanyLedgerWithPayment;
use App\Jobs\Invoice\ApplyClientPayment;
use App\Jobs\Invoice\ApplyInvoicePayment;
use App\Jobs\Invoice\UpdateInvoicePayment;
use App\Models\Credit;
use App\Models\Invoice;
use App\Models\Payment;
use App\Repositories\CreditRepository;
use Illuminate\Http\Request;

/**
 * PaymentRepository
 */
class PaymentRepository extends BaseRepository
{

    protected $credit_repo;


    public function __construct(CreditRepository $credit_repo)
    {

        $this->credit_repo = $credit_repo;

    } 

    public function getClassName()
    {
        return Payment::class;
    }
    
    /**
     * Saves and updates a payment. //todo refactor to handle refunds and payments.
     * 
     * 
     * @param  Request $request the request object
     * @param  Payment $payment The Payment object
     * @return Object       Payment $payment
     */
    public function save(Request $request, Payment $payment) : ?Payment
    {

        //todo this save() only works for new payments... will fail if a Payment is updated and saved through here.
        $payment->fill($request->all());
        $payment->status_id = Payment::STATUS_COMPLETED;

        $payment->save();

        if(!$payment->number)
            $payment->number = $payment->client->getNextPaymentNumber($payment->client);
        
        //we only ever update the ACTUAL amount of money transferred
        UpdateClientPaidToDate::dispatchNow($payment->client, $payment->amount, $payment->company);

        $invoice_totals = 0;
        $credit_totals = 0;

        if ($request->input('invoices') && is_array($request->input('invoices'))) {
        
        $invoice_totals = array_sum(array_column($request->input('invoices'),'amount'));

            $invoices = Invoice::whereIn('id', array_column($request->input('invoices'), 'id'))->company()->get();

            $payment->invoices()->saveMany($invoices);

            foreach ($request->input('invoices') as $paid_invoice) {
                $invoice = Invoice::whereId($paid_invoice['id'])->company()->first();

                if ($invoice) {
                    ApplyInvoicePayment::dispatchNow($invoice, $payment, $paid_invoice['amount'], $invoice->company);
                }
            }
        } else {
            //payment is made, but not to any invoice, therefore we are applying the payment to the clients credit
            ApplyClientPayment::dispatchNow($payment, $payment->company);
        }

        if($request->input('credits') && is_array($request->input('credits')))
        {

        $credit_totals = array_sum(array_column($request->input('credits'),'amount'));
    
            $credits = Credit::whereIn('id', array_column($request->input('credits'), 'id'))->company()->get();

            $payment->credits()->saveMany($credits);

            foreach ($request->input('credits') as $paid_credit) 
            {
                $credit = Credit::whereId($paid_credit['id'])->company()->first();

                if($credit)
                    ApplyCreditPayment::dispatchNow($paid_credit, $payment, $paid_credit['amount'], $credit->company);
            }

        }

        event(new PaymentWasCreated($payment, $payment->company));

        $invoice_totals -= $credit_totals;

        if($invoice_totals == $payment->amount)
            $payment->applied = $payment->amount;
        elseif($invoice_totals < $payment->amount)
            $payment->applied = $invoice_totals;

        //UpdateInvoicePayment::dispatchNow($payment);
        $payment->save();

        return $payment->fresh();
    }

    /**
     * Updates
     *
     * The update code path is different to the save path
     * additional considerations come into play when 'updating'
     * a payment.
     * 
     * @param  Request $request the request object
     * @param  Payment $payment The Payment object
     * @return Object       Payment $payment
     */
    public function update(Request $request, Payment $payment) :?Payment
    {
        
        if($payment->amount >= 0)
            return $this->applyPayment($request, $payment);
        
        return $this->refundPayment($request, $payment);

    }


    private function applyPayment(Request $request, Payment $payment) :?Payment
    {
        $invoice_totals = array_sum(array_column($request->input('invoices'),'amount'));

        $invoices = Invoice::whereIn('id', array_column($request->input('invoices'), 'id'))->company()->get();

        $payment->invoices()->saveMany($invoices);
    
        foreach ($request->input('invoices') as $paid_invoice) {
            $invoice = Invoice::whereId($paid_invoice['id'])->company()->first();

            if ($invoice) {
                ApplyInvoicePayment::dispatchNow($invoice, $payment, $paid_invoice['amount'], $invoice->company);
            }
        }

    }

    private function refundPayment(Request $request, Payment $payment) :?Payment
    {
        //temp variable to sum the total refund/credit amount
        $invoice_total_adjustment = 0;

        if($request->has('invoices') && is_array($request->input('invoices'))){
            
            foreach($request->input('invoices') as $adjusted_invoice) {

                $invoice = Invoice::whereId($adjusted_invoice['id'])->company()->first();

                $invoice_total_adjustment += $adjusted_invoice['amount'];

                if(array_key_exists('credits', $adjusted_invoice)){
                    
                    //process and insert credit notes
                    foreach($adjusted_invoice['credits'] as $credit){

                        $credit = $this->credit_repo->save($credit, CreditFactory::create(auth()->user()->company()->id, auth()->user()->id), $invoice);

                    }

                }
                else {
                    //todo - generate Credit Note for $amount on $invoice - the assumption here is that it is a FULL refund        
                }

            }

            if($request->input('amount') != $invoice_total_adjustment)
                return 'Amount must equal the sum of invoice adjustments';
        }


        //adjust applied amount
        $payment->applied += $invoice_total_adjustment;

        //adjust clients paid to date
        $client = $payment->client;
        $client->paid_to_date += $invoice_total_adjustment;
    
        $payment->save();
        $client->save();
    }

    
}
