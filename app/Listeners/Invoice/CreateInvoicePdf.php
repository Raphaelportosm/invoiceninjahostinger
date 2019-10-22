<?php
/**
 * Invoice Ninja (https://invoiceninja.com)
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2019. Invoice Ninja LLC (https://invoiceninja.com)
 *
 * @license https://opensource.org/licenses/AAL
 */

namespace App\Listeners\Invoice;

use App\Utils\Traits\MakesHash;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Spatie\Browsershot\Browsershot;
use Symfony\Component\Debug\Exception\FatalThrowableError;

class CreateInvoicePdf implements ShouldQueue
{
    protected $activity_repo;
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle($event)
    {

        $invoice = $event->invoice;

        $invoice->load('client');
        $path = 'public/' . $invoice->client->client_hash . '/invoices/'; 
        $file_path = $path . $invoice->invoice_number . '.pdf';

        //get invoice design
        $html = $this->generateInvoiceHtml($invoice->design(), $invoice);

        //todo - move this to the client creation stage so we don't keep hitting this unnecessarily
        Storage::makeDirectory($path, 0755);

        //create pdf
        $pdf = $this->makePdf(null,null,$html);

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
            ->waitUntilNetworkIdle(false)->pdf();
            //->margins(10,10,10,10)
            //->savePdf('test.pdf');
    }

    /**
     * Generate the HTML invoice parsing variables 
     * and generating the final invoice HTML
     *     
     * @param  string $design either the path to the design template, OR the full design template string
     * @param  Collection $invoice  The invoice object
     * 
     * @return string           The invoice string in HTML format
     */
    private function generateInvoiceHtml($design, $invoice) :string
    {

        $variables = array_merge($invoice->makeLabels(), $invoice->makeValues());
        $design = str_replace(array_keys($variables), array_values($variables), $design);

        $data['invoice'] = $invoice;

        return $this->renderView($design, $data);

        //return view($design, $data)->render();

    }

    /**
     * Parses the blade file string and processes the template variables
     * 
     * @param  string $string The Blade file string
     * @param  array $data   The array of template variables
     * @return string         The return HTML string
     * 
     */
    private function renderView($string, $data) :string
    {
        if (!$data) {
        $data = [];
        }

        $data['__env'] = app(\Illuminate\View\Factory::class);

        $php = Blade::compileString($string);

        $obLevel = ob_get_level();
        ob_start();
        extract($data, EXTR_SKIP);

        try {
            eval('?' . '>' . $php);
        } catch (\Exception $e) {
            while (ob_get_level() > $obLevel) {
                ob_end_clean();
            }

            throw $e;
        } catch (\Throwable $e) {
            while (ob_get_level() > $obLevel) {
                ob_end_clean();
            }

            throw new FatalThrowableError($e);
        }

        return ob_get_clean();
    }

}
