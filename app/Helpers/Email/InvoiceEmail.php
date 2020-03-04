<?php
/**
 * Created by PhpStorm.
 * User: michael.hampton
 * Date: 14/02/2020
 * Time: 19:51
 */

namespace App\Helpers\Email;


use App\Models\Invoice;
use App\Models\InvoiceInvitation;
use App\Utils\Number;

class InvoiceEmail extends EmailBuilder
{

    public function build(InvoiceInvitation $invitation, $reminder_template)
    {
        $client = $invitation->contact->client;
        $invoice = $invitation->invoice;
        $contact = $invitation->contact;

        if(!$reminder_template)
            $reminder_template = $invoice->calculateTemplate();

        $body_template = $client->getSetting('email_template_' . $reminder_template);


        /* Use default translations if a custom message has not been set*/
        if (iconv_strlen($body_template) == 0) {
            $body_template = trans('texts.invoice_message',
                [
                    'invoice' => $invoice->number, 
                    'company' => $invoice->company->present()->name(),
                    'amount' => Number::formatMoney($invoice->balance, $invoice->client),
                ], 
                null,
                $invoice->client->locale());
        }

        $subject_template = $client->getSetting('email_subject_' . $reminder_template);

        if (iconv_strlen($subject_template) == 0) {
            if ($reminder_template == 'quote') {
                $subject_template = trans('texts.invoice_subject',
                    [
                        'invoice' => $invoice->present()->invoice_number(),
                        'account' => $invoice->company->present()->name()
                    ],
                    null, $invoice->client->locale());
            } else {
                $subject_template = trans('texts.reminder_subject',
                    [
                        'invoice' => $invoice->present()->invoice_number(),
                        'account' => $invoice->company->present()->name()
                    ],
                    null, $invoice->client->locale());
            }
        }
        
        $this->setTemplate($invoice->client->getSetting('email_style'))
            ->setContact($contact)
            ->setVariables($invoice->makeValues($contact))
            ->setSubject($subject_template)
            ->setBody($body_template)
            ->setFooter("<a href='{$invitation->getLink()}'>{$invitation->getLink()}</a>");

        if ($client->getSetting('pdf_email_attachment') !== false) {
            $this->setAttachments($invoice->pdf_file_path());
        }
        return $this;
    }
}
