<?php

/**
 * Invoice Ninja (https://invoiceninja.com).
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2020. Invoice Ninja LLC (https://invoiceninja.com)
 *
 * @license https://opensource.org/licenses/AAL
 */

namespace App\Utils;

use App\Models\Country;
use App\Models\CreditInvitation;
use App\Models\InvoiceInvitation;
use App\Models\QuoteInvitation;
use App\Models\RecurringInvoiceInvitation;
use App\Utils\Number;
use App\Utils\Traits\MakesDates;
use Exception;
use Illuminate\Support\Facades\App;

class HtmlEngine
{
    use MakesDates;

    public $entity;

    public $invitation;

    public $client;

    public $contact;

    public $company;

    public $settings;

    public $entity_calc;

    public $entity_string;

    public function __construct($invitation)
    {

        $this->invitation = $invitation;

        $this->entity_string = $this->resolveEntityString();

        $this->entity = $invitation->{$this->entity_string};

        $this->company = $invitation->company;

        $this->contact = $invitation->contact;

        $this->client = $this->entity->client;

        $this->settings = $this->client->getMergedSettings();

        $this->entity_calc = $this->entity->calc();

    }


    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    private function resolveEntityString()
    {
        switch ($this->invitation) {
            case ($this->invitation instanceof InvoiceInvitation):
                return 'invoice';
                break;
            case ($this->invitation instanceof CreditInvitation):
                return 'credit';
                break;
            case ($this->invitation instanceof QuoteInvitation):
                return 'quote';
                break;
            case ($this->invitation instanceof RecurringInvoiceInvitation):
                return 'recurring_invoice';
                break;
            default:
                # code...
                break;
        }
    }

    public function buildEntityDataArray() :array
    {
        if (! $this->client->currency()) {
            throw new Exception(debug_backtrace()[1]['function'], 1);
            exit;
        }

        $data = [];
        $data['$global-margin'] = ['value' => 'm-8', 'label' => ''];
        $data['$global-padding'] = ['value' => 'p-8', 'label' => ''];
        $data['$tax'] = ['value' => '', 'label' => ctrans('texts.tax')];
        $data['$app_url'] = ['value' => $this->generateAppUrl(), 'label' => ''];
        $data['$from'] = ['value' => '', 'label' => ctrans('texts.from')];
        $data['$to'] = ['value' => '', 'label' => ctrans('texts.to')];
        $data['$total_tax_labels'] = ['value' => $this->totalTaxLabels(), 'label' => ctrans('texts.taxes')];
        $data['$total_tax_values'] = ['value' => $this->totalTaxValues(), 'label' => ctrans('texts.taxes')];
        $data['$line_tax_labels'] = ['value' => $this->lineTaxLabels(), 'label' => ctrans('texts.taxes')];
        $data['$line_tax_values'] = ['value' => $this->lineTaxValues(), 'label' => ctrans('texts.taxes')];
        $data['$date'] = ['value' => $this->formatDate($this->entity->date, $this->entity->client->date_format()) ?: '&nbsp;', 'label' => ctrans('texts.date')];
        //$data['$invoice_date']           = ['value' => $this->date ?: '&nbsp;', 'label' => ctrans('texts.invoice_date')];
        $data['$invoice.date'] = &$data['$date'];
        $data['$due_date'] = ['value' => $this->formatDate($this->entity->due_date, $this->entity->client->date_format()) ?: '&nbsp;', 'label' => ctrans('texts.'.$this->entity_string.'_due_date')];
        $data['$payment_due'] = ['value' => $this->formatDate($this->entity->due_date, $this->entity->client->date_format()) ?: '&nbsp;', 'label' => ctrans('texts.payment_due')];
        $data['$invoice.due_date'] = &$data['$due_date'];
        $data['$invoice.number'] = ['value' => $this->entity->number ?: '&nbsp;', 'label' => ctrans('texts.invoice_number')];
        $data['$invoice.po_number'] = ['value' => $this->entity->po_number ?: '&nbsp;', 'label' => ctrans('texts.po_number')];
        // $data['$line_taxes'] = ['value' => $this->makeLineTaxes() ?: '&nbsp;', 'label' => ctrans('texts.taxes')];
        // $data['$invoice.line_taxes'] = &$data['$line_taxes'];

        // $data['$total_taxes'] = ['value' => $this->makeTotalTaxes() ?: '&nbsp;', 'label' => ctrans('texts.taxes')];
        // $data['$invoice.total_taxes'] = &$data['$total_taxes'];

        if ($this->entity_string == 'invoice') {
            $data['$entity'] = ['value' => '', 'label' => ctrans('texts.invoice')];
            $data['$number'] = ['value' => $this->entity->number ?: '&nbsp;', 'label' => ctrans('texts.invoice_number')];
            $data['$entity.terms'] = ['value' => $this->entity->terms ?: '&nbsp;', 'label' => ctrans('texts.invoice_terms')];
            $data['$terms'] = &$data['$entity.terms'];
            $data['$view_link'] = ['value' => '<a href="'.$this->invitation->getLink().'">'.ctrans('texts.view_invoice').'</a>', 'label' => ctrans('texts.view_invoice')];
            $data['$view_url'] = ['value' => $this->invitation->getLink(), 'label' => ctrans('texts.view_invoice')];
        }

        if ($this->entity_string == 'quote') {
            $data['$entity'] = ['value' => '', 'label' => ctrans('texts.quote')];
            $data['$number'] = ['value' => $this->entity->number ?: '&nbsp;', 'label' => ctrans('texts.quote_number')];
            $data['$entity.terms'] = ['value' => $this->entity->terms ?: '&nbsp;', 'label' => ctrans('texts.quote_terms')];
            $data['$terms'] = &$data['$entity.terms'];
            $data['$view_link'] = ['value' => '<a href="'.$this->invitation->getLink().'">'.ctrans('texts.view_quote').'</a>', 'label' => ctrans('texts.view_quote')];
            $data['$view_url'] = ['value' => $this->invitation->getLink(), 'label' => ctrans('texts.view_quote')];
        }

        if ($this->entity_string == 'credit') {
            $data['$entity'] = ['value' => '', 'label' => ctrans('texts.credit')];
            $data['$number'] = ['value' => $this->entity->number ?: '&nbsp;', 'label' => ctrans('texts.credit_number')];
            $data['$entity.terms'] = ['value' => $this->entity->terms ?: '&nbsp;', 'label' => ctrans('texts.credit_terms')];
            $data['$terms'] = &$data['$entity.terms'];
            $data['$view_link'] = ['value' => '<a href="'.$this->invitation->getLink().'">'.ctrans('texts.view_credit').'</a>', 'label' => ctrans('texts.view_credit')];
            $data['$view_url'] = ['value' => $this->invitation->getLink(), 'label' => ctrans('texts.view_credit')];
            // $data['$view_link']          = ['value' => $this->invitation->getLink(), 'label' => ctrans('texts.view_credit')];
        }

        $data['$entity_number'] = &$data['$number'];
        $data['$invoice.discount'] = ['value' => Number::formatMoney($this->entity_calc->getTotalDiscount(), $this->client) ?: '&nbsp;', 'label' => ctrans('texts.discount')];
        $data['$discount'] = &$data['$invoice.discount'];
        $data['$subtotal'] = ['value' => Number::formatMoney($this->entity_calc->getSubTotal(), $this->client) ?: '&nbsp;', 'label' => ctrans('texts.subtotal')];
        $data['$invoice.subtotal'] = &$data['$subtotal'];
        $data['$balance_due'] = ['value' => Number::formatMoney($this->entity->balance, $this->client) ?: '&nbsp;', 'label' => ctrans('texts.balance_due')];
        $data['$quote.balance_due'] = &$data['$balance_due'];
        $data['$invoice.balance_due'] = &$data['$balance_due'];
        $data['$balance_due'] = &$data['$balance_due'];
        $data['$outstanding'] = &$data['$balance_due'];
        $data['$partial_due'] = ['value' => Number::formatMoney($this->entity->partial, $this->client) ?: '&nbsp;', 'label' => ctrans('texts.partial_due')];
        $data['$total'] = ['value' => Number::formatMoney($this->entity_calc->getTotal(), $this->client) ?: '&nbsp;', 'label' => ctrans('texts.total')];
        $data['$amount'] = &$data['$total'];
        $data['$amount_due'] = ['value' => &$data['$total']['value'], 'label' => ctrans('texts.amount_due')];
        $data['$quote.total'] = &$data['$total'];
        $data['$invoice.total'] = ['value' => Number::formatMoney($this->entity_calc->getTotal(), $this->client) ?: '&nbsp;', 'label' => ctrans('texts.invoice_total')];
        $data['$invoice.amount'] = &$data['$total'];
        $data['$quote.amount'] = ['value' => Number::formatMoney($this->entity_calc->getTotal(), $this->client) ?: '&nbsp;', 'label' => ctrans('texts.quote_total')];
        $data['$credit.total'] = ['value' => Number::formatMoney($this->entity_calc->getTotal(), $this->client) ?: '&nbsp;', 'label' => ctrans('texts.credit_total')];
        $data['$credit.number'] = ['value' => $this->entity->number ?: '&nbsp;', 'label' => ctrans('texts.credit_number')];
        $data['$credit.total'] = &$data['$credit.total'];
        $data['$credit.po_number'] = &$data['$invoice.po_number'];
        $data['$credit.date'] = ['value' => $this->formatDate($this->entity->date, $this->entity->client->date_format()), 'label' => ctrans('texts.credit_date')];
        $data['$balance'] = ['value' => Number::formatMoney($this->entity_calc->getBalance(), $this->client) ?: '&nbsp;', 'label' => ctrans('texts.balance')];
        $data['$credit.balance'] = &$data['$balance'];

        $data['$invoice.balance'] = &$data['$balance'];
        $data['$taxes'] = ['value' => Number::formatMoney($this->entity_calc->getItemTotalTaxes(), $this->client) ?: '&nbsp;', 'label' => ctrans('texts.taxes')];
        $data['$invoice.taxes'] = &$data['$taxes'];

        $data['$invoice.custom1'] = ['value' => $this->formatCustomFieldValue('invoice1', $this->entity->custom_value1) ?: '&nbsp;', 'label' => $this->makeCustomField('invoice1')];
        $data['$invoice.custom2'] = ['value' => $this->formatCustomFieldValue('invoice2', $this->entity->custom_value2) ?: '&nbsp;', 'label' => $this->makeCustomField('invoice2')];
        $data['$invoice.custom3'] = ['value' => $this->formatCustomFieldValue('invoice3', $this->entity->custom_value3) ?: '&nbsp;', 'label' => $this->makeCustomField('invoice3')];
        $data['$invoice.custom4'] = ['value' => $this->formatCustomFieldValue('invoice4', $this->entity->custom_value4) ?: '&nbsp;', 'label' => $this->makeCustomField('invoice4')];
        $data['$invoice.public_notes'] = ['value' => $this->entity->public_notes ?: '&nbsp;', 'label' => ctrans('texts.public_notes')];
        $data['$entity.public_notes'] = &$data['$invoice.public_notes'];

        $data['$entity_issued_to'] = ['value' => '', 'label' => ctrans("texts.{$this->entity_string}_issued_to")];
        $data['$your_entity'] = ['value' => '', 'label' => ctrans("texts.your_{$this->entity_string}")];

        $data['$quote.date'] = ['value' => $this->formatDate($this->entity->date, $this->entity->client->date_format()) ?: '&nbsp;', 'label' => ctrans('texts.quote_date')];
        $data['$quote.number'] = ['value' => $this->entity->number ?: '&nbsp;', 'label' => ctrans('texts.quote_number')];
        $data['$quote.po_number'] = &$data['$invoice.po_number'];
        $data['$quote.quote_number'] = &$data['$quote.number'];
        $data['$quote_no'] = &$data['$quote.number'];
        $data['$quote.quote_no'] = &$data['$quote.number'];
        $data['$quote.valid_until'] = ['value' => $this->formatDate($this->entity->due_date, $this->client->date_format()), 'label' => ctrans('texts.valid_until')];
        $data['$credit_amount'] = ['value' => Number::formatMoney($this->entity_calc->getTotal(), $this->client) ?: '&nbsp;', 'label' => ctrans('texts.credit_amount')];
        $data['$credit_balance'] = ['value' => Number::formatMoney($this->entity->balance, $this->client) ?: '&nbsp;', 'label' => ctrans('texts.credit_balance')];

        $data['$credit_number'] = &$data['$number'];
        $data['$credit_no'] = &$data['$number'];
        $data['$credit.credit_no'] = &$data['$number'];

        // $data['$invoice_issued_to'] = ;
        // $data['$quote_issued_to'] = ;
        // $data['$rate'] = ;
        // $data['$hours'] = ;
        // $data['$from'] = ;
        // $data['$to'] = ;
        // $data['$invoice_to'] = ;
        // $data['$quote_to'] = ;
        // $data['$details'] = ;
        $data['$invoice_no'] = &$data['$number'];
        $data['$invoice.invoice_no'] = &$data['$number'];
        $data['$client1'] = ['value' => $this->formatCustomFieldValue('client1', $this->client->custom_value1) ?: '&nbsp;', 'label' => $this->makeCustomField('client1')];
        $data['$client2'] = ['value' => $this->formatCustomFieldValue('client2', $this->client->custom_value2) ?: '&nbsp;', 'label' => $this->makeCustomField('client2')];
        $data['$client3'] = ['value' => $this->formatCustomFieldValue('client3', $this->client->custom_value3) ?: '&nbsp;', 'label' => $this->makeCustomField('client3')];
        $data['$client4'] = ['value' => $this->formatCustomFieldValue('client4', $this->client->custom_value4) ?: '&nbsp;', 'label' => $this->makeCustomField('client4')];
        $data['$address1'] = ['value' => $this->client->address1 ?: '&nbsp;', 'label' => ctrans('texts.address1')];
        $data['$address2'] = ['value' => $this->client->address2 ?: '&nbsp;', 'label' => ctrans('texts.address2')];
        $data['$id_number'] = ['value' => $this->client->id_number ?: '&nbsp;', 'label' => ctrans('texts.id_number')];
        $data['$vat_number'] = ['value' => $this->client->vat_number ?: '&nbsp;', 'label' => ctrans('texts.vat_number')];
        $data['$website'] = ['value' => $this->client->present()->website() ?: '&nbsp;', 'label' => ctrans('texts.website')];
        $data['$phone'] = ['value' => $this->client->present()->phone() ?: '&nbsp;', 'label' => ctrans('texts.phone')];
        $data['$country'] = ['value' => isset($this->client->country->name) ? $this->client->country->name : '', 'label' => ctrans('texts.country')];
        $data['$email'] = ['value' => isset($this->contact) ? $this->contact->email : 'no contact email on record', 'label' => ctrans('texts.email')];
        $data['$client_name'] = ['value' => $this->entity->present()->clientName() ?: '&nbsp;', 'label' => ctrans('texts.client_name')];
        $data['$client.name'] = &$data['$client_name'];
        $data['$client.address1'] = &$data['$address1'];
        $data['$client.address2'] = &$data['$address2'];
        $data['$client_address'] = ['value' => $this->entity->present()->address() ?: '&nbsp;', 'label' => ctrans('texts.address')];
        $data['$client.address'] = &$data['$client_address'];
        $data['$client.id_number'] = &$data['$id_number'];
        $data['$client.vat_number'] = &$data['$vat_number'];
        $data['$client.website'] = &$data['$website'];
        $data['$client.phone'] = &$data['$phone'];
        $data['$city_state_postal'] = ['value' => $this->entity->present()->cityStateZip($this->client->city, $this->client->state, $this->client->postal_code, false) ?: '&nbsp;', 'label' => ctrans('texts.city_state_postal')];
        $data['$client.city_state_postal'] = &$data['$city_state_postal'];
        $data['$postal_city_state'] = ['value' => $this->entity->present()->cityStateZip($this->client->city, $this->client->state, $this->client->postal_code, true) ?: '&nbsp;', 'label' => ctrans('texts.postal_city_state')];
        $data['$client.postal_city_state'] = &$data['$postal_city_state'];
        $data['$client.country'] = &$data['$country'];
        $data['$client.email'] = &$data['$email'];

        $data['$client.balance'] = ['value' => Number::formatMoney($this->client->balance, $this->client), 'label' => ctrans('texts.account_balance')];
        $data['$outstanding'] = ['value' => Number::formatMoney($this->client->balance, $this->client), 'label' => ctrans('texts.account_balance')];
        $data['$client_balance'] = ['value' => Number::formatMoney($this->client->balance, $this->client), 'label' => ctrans('texts.account_balance')];
        $data['$paid_to_date'] = ['value' => Number::formatMoney($this->client->paid_to_date, $this->client), 'label' => ctrans('texts.paid_to_date')];

        $data['$contact.full_name'] = ['value' => $this->contact->present()->name(), 'label' => ctrans('texts.name')];
        $data['$contact.email'] = ['value' => $this->contact->email, 'label' => ctrans('texts.email')];
        $data['$contact.phone'] = ['value' => $this->contact->phone, 'label' => ctrans('texts.phone')];

        $data['$contact.name'] = ['value' => isset($this->contact) ? $this->contact->present()->name() : 'no contact name on record', 'label' => ctrans('texts.contact_name')];
        $data['$contact.first_name'] = ['value' => isset($this->contact) ? $this->contact->first_name : '', 'label' => ctrans('texts.first_name')];
        $data['$contact.last_name'] = ['value' => isset($this->contact) ? $this->contact->last_name : '', 'label' => ctrans('texts.last_name')];
        $data['$contact.custom1'] = ['value' => isset($this->contact) ? $this->contact->custom_value1 : '&nbsp;', 'label' => $this->makeCustomField('contact1')];
        $data['$contact.custom2'] = ['value' => isset($this->contact) ? $this->contact->custom_value2 : '&nbsp;', 'label' => $this->makeCustomField('contact1')];
        $data['$contact.custom3'] = ['value' => isset($this->contact) ? $this->contact->custom_value3 : '&nbsp;', 'label' => $this->makeCustomField('contact1')];
        $data['$contact.custom4'] = ['value' => isset($this->contact) ? $this->contact->custom_value4 : '&nbsp;', 'label' => $this->makeCustomField('contact1')];

        $data['$company.city_state_postal'] = ['value' => $this->company->present()->cityStateZip($this->settings->city, $this->settings->state, $this->settings->postal_code, false) ?: '&nbsp;', 'label' => ctrans('texts.city_state_postal')];
        $data['$company.postal_city_state'] = ['value' => $this->company->present()->cityStateZip($this->settings->city, $this->settings->state, $this->settings->postal_code, true) ?: '&nbsp;', 'label' => ctrans('texts.postal_city_state')];
        $data['$company.name'] = ['value' => $this->company->present()->name() ?: '&nbsp;', 'label' => ctrans('texts.company_name')];
        $data['$company.address1'] = ['value' => $this->settings->address1 ?: '&nbsp;', 'label' => ctrans('texts.address1')];
        $data['$company.address2'] = ['value' => $this->settings->address2 ?: '&nbsp;', 'label' => ctrans('texts.address2')];
        $data['$company.city'] = ['value' => $this->settings->city ?: '&nbsp;', 'label' => ctrans('texts.city')];
        $data['$company.state'] = ['value' => $this->settings->state ?: '&nbsp;', 'label' => ctrans('texts.state')];
        $data['$company.postal_code'] = ['value' => $this->settings->postal_code ?: '&nbsp;', 'label' => ctrans('texts.postal_code')];
        $data['$company.country'] = ['value' => $this->getCountryName(), 'label' => ctrans('texts.country')];
        $data['$company.phone'] = ['value' => $this->settings->phone ?: '&nbsp;', 'label' => ctrans('texts.phone')];
        $data['$company.email'] = ['value' => $this->settings->email ?: '&nbsp;', 'label' => ctrans('texts.email')];
        $data['$company.vat_number'] = ['value' => $this->settings->vat_number ?: '&nbsp;', 'label' => ctrans('texts.vat_number')];
        $data['$company.id_number'] = ['value' => $this->settings->id_number ?: '&nbsp;', 'label' => ctrans('texts.id_number')];
        $data['$company.website'] = ['value' => $this->settings->website ?: '&nbsp;', 'label' => ctrans('texts.website')];
        $data['$company.address'] = ['value' => $this->company->present()->address($this->settings) ?: '&nbsp;', 'label' => ctrans('texts.address')];

        $logo = $this->company->present()->logo($this->settings);

        $data['$company.logo'] = ['value' => $logo ?: '&nbsp;', 'label' => ctrans('texts.logo')];
        $data['$company_logo'] = &$data['$company.logo'];
        $data['$company1'] = ['value' => $this->formatCustomFieldValue('company1', $this->settings->custom_value1) ?: '&nbsp;', 'label' => $this->makeCustomField('company1')];
        $data['$company2'] = ['value' => $this->formatCustomFieldValue('company2', $this->settings->custom_value2) ?: '&nbsp;', 'label' => $this->makeCustomField('company2')];
        $data['$company3'] = ['value' => $this->formatCustomFieldValue('company3', $this->settings->custom_value3) ?: '&nbsp;', 'label' => $this->makeCustomField('company3')];
        $data['$company4'] = ['value' => $this->formatCustomFieldValue('company4', $this->settings->custom_value4) ?: '&nbsp;', 'label' => $this->makeCustomField('company4')];

        $data['$custom_surcharge1'] = ['value' => $this->entity->custom_surcharge1 ?: '&nbsp;', 'label' => $this->makeCustomField('custom_surcharge1')];
        $data['$custom_surcharge2'] = ['value' => $this->entity->custom_surcharge2 ?: '&nbsp;', 'label' => $this->makeCustomField('custom_surcharge2')];
        $data['$custom_surcharge3'] = ['value' => $this->entity->custom_surcharge3 ?: '&nbsp;', 'label' => $this->makeCustomField('custom_surcharge3')];
        $data['$custom_surcharge4'] = ['value' => $this->entity->custom_surcharge4 ?: '&nbsp;', 'label' => $this->makeCustomField('custom_surcharge4')];

        $data['$product.date'] = ['value' => '', 'label' => ctrans('texts.date')];
        $data['$product.discount'] = ['value' => '', 'label' => ctrans('texts.discount')];
        $data['$product.product_key'] = ['value' => '', 'label' => ctrans('texts.product_key')];
        $data['$product.notes'] = ['value' => '', 'label' => ctrans('texts.notes')];
        $data['$product.cost'] = ['value' => '', 'label' => ctrans('texts.cost')];
        $data['$product.quantity'] = ['value' => '', 'label' => ctrans('texts.quantity')];
        $data['$product.tax_name1'] = ['value' => '', 'label' => ctrans('texts.tax')];
        $data['$product.tax'] = ['value' => '', 'label' => ctrans('texts.tax')];
        $data['$product.tax_name2'] = ['value' => '', 'label' => ctrans('texts.tax')];
        $data['$product.tax_name3'] = ['value' => '', 'label' => ctrans('texts.tax')];
        $data['$product.line_total'] = ['value' => '', 'label' => ctrans('texts.line_total')];

        $data['$task.date'] = ['value' => '', 'label' => ctrans('texts.date')];
        $data['$task.discount'] = ['value' => '', 'label' => ctrans('texts.discount')];
        $data['$task.product_key'] = ['value' => '', 'label' => ctrans('texts.product_key')];
        $data['$task.notes'] = ['value' => '', 'label' => ctrans('texts.notes')];
        $data['$task.rate'] = ['value' => '', 'label' => ctrans('texts.rate')];
        $data['$task.hours'] = ['value' => '', 'label' => ctrans('texts.hours')];
        $data['$task.tax'] = ['value' => '', 'label' => ctrans('texts.tax')];
        $data['$task.tax_name1'] = ['value' => '', 'label' => ctrans('texts.tax')];
        $data['$task.tax_name2'] = ['value' => '', 'label' => ctrans('texts.tax')];
        $data['$task.tax_name3'] = ['value' => '', 'label' => ctrans('texts.tax')];
        $data['$task.line_total'] = ['value' => '', 'label' => ctrans('texts.line_total')];

        if($this->settings->signature_on_pdf)
            $data['$contact.signature'] = ['value' => $this->invitation->signature_base64, 'label' => ctrans('texts.signature')];
        else
            $data['$contact.signature'] = ['value' => '', 'label' => ''];

        $data['$thanks'] = ['value' => '', 'label' => ctrans('texts.thanks')];
        $data['$from'] = ['value' => '', 'label' => ctrans('texts.from')];
        $data['$to'] = ['value' => '', 'label' => ctrans('texts.to')];
        $data['$details'] = ['value' => '', 'label' => ctrans('texts.details')];

        $data['_rate1'] = ['value' => '', 'label' => ctrans('texts.tax')];
        $data['_rate2'] = ['value' => '', 'label' => ctrans('texts.tax')];
        $data['_rate3'] = ['value' => '', 'label' => ctrans('texts.tax')];

        $data['$font_size'] = ['value' => $this->settings->font_size . 'px', 'label' => ''];

        $data['$invoiceninja.whitelabel'] = ['value' => Helpers::asset('images/created-by-invoiceninja-new.png'), 'label' => ''];

        $data['$primary_color'] = ['value' => $this->settings->primary_color, 'label' => ''];
        $data['$secondary_color'] = ['value' => $this->settings->secondary_color, 'label' => ''];

        $data['$item'] = ['value' => '', 'label' => ctrans('texts.item')];
        $data['$description'] = ['value' => '', 'label' => ctrans('texts.description')];

        // $data['custom_label1']              = ['value' => '', 'label' => ctrans('texts.')];
        // $data['custom_label2']              = ['value' => '', 'label' => ctrans('texts.')];
        // $data['custom_label3']              = ['value' => '', 'label' => ctrans('texts.')];
        // $data['custom_label4']              = ['value' => '', 'label' => ctrans('texts.')];
        //$data['$blank'] = ;
        //$data['$surcharge'] = ;
        /*
        $data['$tax_invoice'] =
        $data['$tax_quote'] =
        $data['$statement'] = ;
        $data['$statement_date'] = ;
        $data['$your_statement'] = ;
        $data['$statement_issued_to'] = ;
        $data['$statement_to'] = ;
        $data['$credit_note'] = ;
        $data['$credit_date'] = ;
        $data['$credit_issued_to'] = ;
        $data['$credit_to'] = ;
        $data['$your_credit'] = ;
        $data['$phone'] = ;

        $data['$outstanding'] = ;
        $data['$invoice_due_date'] = ;
        $data['$quote_due_date'] = ;
        $data['$service'] = ;
        $data['$product_key'] = ;
        $data['$unit_cost'] = ;
        $data['$custom_value1'] = ;
        $data['$custom_value2'] = ;
        $data['$delivery_note'] = ;
        $data['$date'] = ;
        $data['$method'] = ;
        $data['$payment_date'] = ;
        $data['$reference'] = ;
        $data['$amount'] = ;
        $data['$amount_paid'] =;
            */

        $arrKeysLength = array_map('strlen', array_keys($data));
        array_multisort($arrKeysLength, SORT_DESC, $data);

//info(print_r($data,1));

        return $data;
    }

    public function makeValues() :array
    {
        $data = [];

        $values = $this->buildEntityDataArray();

        foreach ($values as $key => $value) {
            $data[$key] = $value['value'];
        }

        return $data;
    }

    public function generateLabelsAndValues()
    {
        $data = [];

        $values = $this->buildEntityDataArray();

        foreach ($values as $key => $value) {
            $data['values'][$key] = $value['value'];
            $data['labels'][$key.'_label'] = $value['label'];
        }

        return $data;
    }

    private function totalTaxLabels() :string
    {
        $data = '';

        if (! $this->entity_calc->getTotalTaxMap()) {
            return $data;
        }

        foreach ($this->entity_calc->getTotalTaxMap() as $tax) {
            $data .= '<span>'.$tax['name'].'</span>';
        }

        return $data;
    }

    private function totalTaxValues() :string
    {
        $data = '';

        if (! $this->entity_calc->getTotalTaxMap()) {
            return $data;
        }

        foreach ($this->entity_calc->getTotalTaxMap() as $tax) {
            $data .= '<span>'.Number::formatMoney($tax['total'], $this->client).'</span>';
        }

        return $data;
    }

    private function lineTaxLabels() :string
    {
        $tax_map = $this->entity_calc->getTaxMap();

        $data = '';

        foreach ($tax_map as $tax) {
            $data .= '<span>'.$tax['name'].'</span>';
        }

        return $data;
    }

    private function getCountryName() :string
    {
        $country = Country::find($this->settings->country_id);

        if ($country) {
            return $country->name;
        }

        return '&nbsp;';
    }

    /**
     * Due to the way we are compiling the blade template we
     * have no ability to iterate, so in the case
     * of line taxes where there are multiple rows,
     * we use this function to format a section of rows.
     *
     * @return string a collection of <tr> rows with line item
     * aggregate data
     */
    private function makeLineTaxes() :string
    {
        $tax_map = $this->entity_calc->getTaxMap();

        $data = '';

        foreach ($tax_map as $tax) {
            $data .= '<tr class="line_taxes">';
            $data .= '<td>'.$tax['name'].'</td>';
            $data .= '<td>'.Number::formatMoney($tax['total'], $this->client).'</td></tr>';
        }

        return $data;
    }

    private function lineTaxValues() :string
    {
        $tax_map = $this->entity_calc->getTaxMap();

        $data = '';

        foreach ($tax_map as $tax) {
            $data .= '<span>'.Number::formatMoney($tax['total'], $this->client).'</span>';
        }

        return $data;
    }

    private function makeCustomField($field) :string
    {
        $custom_fields = $this->company->custom_fields;

        if ($custom_fields && property_exists($custom_fields, $field)) {
            $custom_field = $custom_fields->{$field};

            $custom_field_parts = explode('|', $custom_field);

            return $custom_field_parts[0];
        }

        return '';
    }

    private function formatCustomFieldValue($field, $value) :string
    {
        $custom_fields = $this->company->custom_fields;
        $custom_field = '';

        if ($custom_fields && property_exists($custom_fields, $field)) {
            $custom_field = $custom_fields->{$field};
            $custom_field_parts = explode('|', $custom_field);

            if(count($custom_field_parts) >= 2)
                $custom_field = $custom_field_parts[1];
        }

        switch ($custom_field) {
            case 'date':
                return $this->formatDate($value, $this->client->date_format());
                break;

            default:
                return is_null($value) ? '' : $value;
                break;
        }
    }

    private function makeTotalTaxes() :string
    {
        $data = '';

        if (! $this->entity_calc->getTotalTaxMap()) {
            return $data;
        }

        foreach ($this->entity_calc->getTotalTaxMap() as $tax) {
            $data .= '<tr>';
            $data .= '<td colspan="{ count($this->entity->company->settings->pdf_variables->total_columns) - 2 }"></td>';
            $data .= '<td>'.$tax['name'].'</td>';
            $data .= '<td>'.Number::formatMoney($tax['total'], $this->client).'</td></tr>';
        }

        return $data;
    }

    private function parseLabelsAndValues($labels, $values, $section) :string
    {
        $section = strtr($section, $labels);

        return strtr($section, $values);
    }

    /*
    | Ensures the URL doesn't have duplicated trailing slash
    */
    public function generateAppUrl()
    {
        //return rtrim(config('ninja.app_url'), "/");
        return config('ninja.app_url');
    }

    /**
     * Builds CSS to assist with the generation
     * of Repeating headers and footers on the PDF.
     * @return string The css string
     */
    private function generateCustomCSS() :string
    {
        $header_and_footer = '
.header, .header-space {
  height: 160px;
}

.footer, .footer-space {
  height: 160px;
}

.footer {
  position: fixed;
  bottom: 0;
  width: 100%;
}

.header {
  position: fixed;
  top: 0mm;
  width: 100%;
}

@media print {
   thead {display: table-header-group;}
   tfoot {display: table-footer-group;}
   button {display: none;}
   body {margin: 0;}
}';

        $header = '
.header, .header-space {
  height: 160px;
}

.header {
  position: fixed;
  top: 0mm;
  width: 100%;
}

@media print {
   thead {display: table-header-group;}
   button {display: none;}
   body {margin: 0;}
}';

        $footer = '

.footer, .footer-space {
  height: 160px;
}

.footer {
  position: fixed;
  bottom: 0;
  width: 100%;
}

@media print {
   tfoot {display: table-footer-group;}
   button {display: none;}
   body {margin: 0;}
}';
        $css = '';

        if ($this->settings->all_pages_header && $this->settings->all_pages_footer) {
            $css .= $header_and_footer;
        } elseif ($this->settings->all_pages_header && ! $this->settings->all_pages_footer) {
            $css .= $header;
        } elseif (! $this->settings->all_pages_header && $this->settings->all_pages_footer) {
            $css .= $footer;
        }

        $css .= '
.page {
  page-break-after: always;
}

@page {
  margin: 0mm
}

html {
        ';

        $css .= 'font-size:'.$this->settings->font_size.'px;';
//        $css .= 'font-size:14px;';

        $css .= '}';

        return $css;
    }
}
