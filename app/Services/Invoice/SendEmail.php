<?php

namespace App\Services\Invoice;

use App\Helpers\Email\InvoiceEmail;
use App\Jobs\Invoice\EmailInvoice;
use App\Models\Invoice;
use Illuminate\Support\Carbon;

class SendEmail
{

    public $invoice;

    public function __construct($invoice)
    {
        $this->invoice = $invoice;
    }

    /**
     * Builds the correct template to send
     * @param string $reminder_template The template name ie reminder1
     * @return array
     */
    public function run($reminder_template = null, $contact = null): array
    {
        if (!$reminder_template) {
            $reminder_template = $this->invoice->status_id == Invoice::STATUS_DRAFT || Carbon::parse($this->invoice->due_date) > now() ? 'invoice' : $this->invoice->calculateTemplate();
        }

        $email_builder = (new InvoiceEmail())->build($this->invoice, $reminder_template, $contact);

        $this->invoice->invitations->each(function ($invitation) use ($email_builder) {
            if ($invitation->contact->send_invoice && $invitation->contact->email) {
                EmailInvoice::dispatchNow($email_builder, $invitation);
            }
        });
    }
}
