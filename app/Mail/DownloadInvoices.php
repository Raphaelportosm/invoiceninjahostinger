<?php

namespace App\Mail;

use App\Utils\Ninja;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DownloadInvoices extends Mailable
{
    use Queueable, SerializesModels;

    public $file_path;

    public function __construct($file_path)
    {
        $this->file_path = $file_path;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {

        return $this->from(config('mail.from.address')) //todo this needs to be fixed to handle the hosted version
            ->subject(ctrans('texts.download_documents',['size'=>'']))
            ->markdown('email.admin.download_files', [
                'file_path' => $this->file_path
            ]);
    }
}
