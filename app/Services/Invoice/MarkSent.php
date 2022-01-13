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

namespace App\Services\Invoice;

use App\Events\Invoice\InvoiceWasUpdated;
use App\Models\Client;
use App\Models\Invoice;
use App\Services\AbstractService;
use App\Utils\Ninja;

class MarkSent extends AbstractService
{
    public $client;

    public $invoice;

    public function __construct(Client $client, Invoice $invoice)
    {
        $this->client = $client;
        
        $this->invoice = $invoice;
    }

    public function run()
    {

        /* Return immediately if status is not draft */
        if ($this->invoice && $this->invoice->fresh()->status_id != Invoice::STATUS_DRAFT) {
            return $this->invoice;
        }

        $adjustment = $this->invoice->amount;

        /*Set status*/
        $this->invoice
             ->service()
             ->setStatus(Invoice::STATUS_SENT)
             ->updateBalance($adjustment, true)
             ->save();

        /*Adjust client balance*/
        $this->client
             ->service()
             ->updateBalance($adjustment)
             ->save();

        /*Update ledger*/
        $this->invoice
             ->ledger()
             ->updateInvoiceBalance($adjustment, "Invoice {$this->invoice->number} marked as sent.");

        /* Perform additional actions on invoice */
        $this->invoice
             ->service()
             ->applyNumber()
             ->setDueDate()
             // ->deletePdf() //08-01-2022
             ->touchPdf() //08-01-2022
             ->setReminder()
             ->save();

        $this->invoice->markInvitationsSent();

        event(new InvoiceWasUpdated($this->invoice, $this->invoice->company, Ninja::eventVars(auth()->user() ? auth()->user()->id : null)));

        return $this->invoice->fresh();
    }
}
