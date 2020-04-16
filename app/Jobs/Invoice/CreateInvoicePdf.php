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

namespace App\Jobs\Invoice;

use App\Designs\Custom;
use App\Designs\Designer;
use App\Designs\Modern;
use App\Libraries\MultiDB;
use App\Models\ClientContact;
use App\Models\Company;
use App\Models\Design;
use App\Models\Invoice;
use App\Utils\HtmlEngine;
use App\Utils\Traits\MakesHash;
use App\Utils\Traits\MakesInvoiceHtml;
use App\Utils\Traits\NumberFormatter;
use App\Utils\Traits\Pdf\PdfMaker;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;
use Spatie\Browsershot\Browsershot;

class CreateInvoicePdf implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, NumberFormatter, MakesInvoiceHtml, PdfMaker, MakesHash;

    public $invoice;

    public $company;

    public $contact;

    private $disk;

    public $invitation;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($invitation)
    {
        $this->invoice = $invitation->invoice;

        $this->company = $invitation->company;

        $this->contact = $invitation->contact;

        $this->disk = $disk ?? config('filesystems.default');
    }

    public function handle()
    {
        $this->invoice->load('client');

        App::setLocale($this->contact->preferredLocale());

        $path      = $this->invoice->client->invoice_filepath();

        $file_path = $path . $this->invoice->number . '.pdf';
        
        $design    = Design::find($this->decodePrimaryKey($this->invoice->client->getSetting('invoice_design_id')));

        $designer  = new Designer($this->invoice, $design, $this->invoice->client->getSetting('pdf_variables'), 'invoice');

        //get invoice design
        $start = microtime(true);

        $invitation = $this->invoice->invitations->first();
        // $invitation->contact = $this->contact;
        // $invitation->setRelation('contact', $this->contact);
        // $invitation->invoice = $this->invoice;
        // $invitation->setRelation('invoice', $this->invoice);

        $html = (new HtmlEngine($designer, $invitation, 'invoice'))->build();

        \Log::error("generate HTML time the NEW way = ".(microtime(true) - $start));

        //todo - move this to the client creation stage so we don't keep hitting this unnecessarily
        Storage::makeDirectory($path, 0755);

        $pdf       = $this->makePdf(null, null, $html);

        $instance  = Storage::disk($this->disk)->put($file_path, $pdf);

        return $file_path;
    }
}
