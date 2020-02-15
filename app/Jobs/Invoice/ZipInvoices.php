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

use App\Libraries\MultiDB;
use App\Models\Company;
use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use ZipStream\Option\Archive;
use ZipStream\ZipStream;
use Illuminate\Support\Facades\Storage;

class ZipInvoices implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $invoice;

    private $company;

    /**
     * @deprecated confirm to be deleted
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($invoices, Company $company)
    {
        $this->invoices = $invoices;

        $this->company = $company;
    }

    /**
     * Execute the job.
     *
     *
     * @return void
     */
    public function handle()
    {
        MultiDB::setDB($this->company->db);

        $tempStream = fopen('php://memory', 'w+');

        $options = new Archive();
        $options->setOutputStream($tempStream);

        # create a new zipstream object
        $file_name = date('Y-m-d') . '_' . str_replace(' ', '_', trans('texts.invoices')).".zip";

        $path = $this->invoices->first()->client->invoice_filepath();

        $zip = new ZipStream($file_name, $options);

        foreach ($invoices as $invoice) {
            $zip->addFileFromPath(basename($invoice->pdf_file_path()), public_path($invoice->pdf_file_path()));
        }

        $zip->finish();

        Storage::disk(config('filesystems.default'))->put($path . $file_name, $tempStream);

        fclose($tempStream);


        //fire email here
        return Storage::disk(config('filesystems.default'))->url($path . $file_name);

    }
}
