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

use App\Designs\Designer;
use App\Designs\Modern;
use App\Libraries\MultiDB;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentTerm;
use App\Repositories\InvoiceRepository;
use App\Utils\Traits\MakesInvoiceHtml;
use App\Utils\Traits\NumberFormatter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Spatie\Browsershot\Browsershot;
use Symfony\Component\Debug\Exception\FatalThrowableError;

class CreateInvoicePdf implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, NumberFormatter, MakesInvoiceHtml;

    public $invoice;

    public $company;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Invoice $invoice, Company $company)
    {
        $this->invoice = $invoice;

        $this->company = $company;
    }

    public function handle()
    {
        MultiDB::setDB($this->company->db);


        $input_variables = [
            'client_details' => [
                'name',
                'id_number',
                'vat_number',
                'address1',
                'address2',
                'city_state_postal',
                'postal_city_state',
                'country',
                'email',
                'custom_value1',
                'custom_value2',
                'custom_value3',
                'custom_value4',
            ],
            'company_details' => [
                'company_name',
                'id_number',
                'vat_number',
                'website',
                'email',
                'phone',
                'custom_value1',
                'custom_value2',
                'custom_value3',
                'custom_value4',
            ],
            'company_address' => [
                'address1',
                'address2',
                'city_state_postal',
                'postal_city_state',
                'country',
                'custom_value1',
                'custom_value2',
                'custom_value3',
                'custom_value4',
            ],
            'invoice_details' => [
                'invoice_number',
                'po_number',
                'date',
                'due_date',
                'balance_due',
                'invoice_total',
                'partial_due',
                'custom_value1',
                'custom_value2',
                'custom_value3',
                'custom_value4',
            ],
            'table_columns' => [
                'product_key', 
                'notes', 
                'cost',
                'quantity', 
                'discount', 
                'tax_name1', 
                'line_total'
            ],
        ];





        $this->invoice->load('client');
        $path = 'public/' . $this->invoice->client->client_hash . '/invoices/';
        $file_path = $path . $this->invoice->number . '.pdf';

        $modern = new Modern();
        $designer = new Designer($modern, $input_variables);

        //get invoice design
        $html = $this->generateInvoiceHtml($designer->build($this->invoice)->getHtml(), $this->invoice);

        //todo - move this to the client creation stage so we don't keep hitting this unnecessarily
        Storage::makeDirectory($path, 0755);

\Log::error($html);
        //create pdf
        $pdf = $this->makePdf(null, null, $html);

        $path = Storage::put($file_path, $pdf);
    }

    /**
     * Returns a PDF stream
     *
     * @param  string $header Header to be included in PDF
     * @param  string $footer Footer to be included in PDF
     * @param  string $html   The HTML object to be converted into PDF
     *
     * @return string        The PDF string
     */
    private function makePdf($header, $footer, $html)
    {
        return Browsershot::html($html)
            //->showBrowserHeaderAndFooter()
            //->headerHtml($header)
            //->footerHtml($footer)
            ->deviceScaleFactor(1)
            ->showBackground()
            ->waitUntilNetworkIdle(false)->pdf();
        //->margins(10,10,10,10)
            //->savePdf('test.pdf');
    }
}
