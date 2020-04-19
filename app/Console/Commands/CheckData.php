<?php

namespace App\Console\Commands;

use App;
use App\Libraries\CurlUtils;
use App\Models\Account;
use App\Models\ClientContact;
use App\Models\Contact;
use App\Models\Invitation;
use App\Models\Invoice;
use App\Models\InvoiceInvitation;
use App\Utils\Ninja;
use Carbon;
use DB;
use Exception;
use Illuminate\Console\Command;
use Mail;
use Symfony\Component\Console\Input\InputOption;
use Utils;

/*

##################################################################
WARNING: Please backup your database before running this script
##################################################################

If you have any questions please email us at contact@invoiceninja.com

Usage:

php artisan ninja:check-data

Options:

--client_id:<value>

    Limits the script to a single client

--fix=true

    By default the script only checks for errors, adding this option
    makes the script apply the fixes.

--fast=true

    Skip using phantomjs

*/

/**
 * Class CheckData.
 */
class CheckData extends Command
{
    /**
     * @var string
     */
    protected $name = 'ninja:check-data';

    /**
     * @var string
     */
    protected $description = 'Check/fix data';

    protected $log = '';
    protected $isValid = true;

    public function handle()
    {
        $this->logMessage(date('Y-m-d h:i:s') . ' Running CheckData...');

        if ($database = $this->option('database')) {
            config(['database.default' => $database]);
        }

        if (! $this->option('client_id')) {
            $this->checkPaidToDate();
        }

        $this->checkInvoiceBalances();
        $this->checkClientBalances();
        $this->checkContacts();
        //$this->checkLogoFiles();

        if (! $this->option('client_id')) {
            $this->checkOAuth();
            //$this->checkInvitations();

            $this->checkFailedJobs();
        }

        $this->logMessage('Done: ' . strtoupper($this->isValid ? Account::RESULT_SUCCESS : Account::RESULT_FAILURE));
        $errorEmail = config('ninja.error_email');

        if ($errorEmail) {
            Mail::raw($this->log, function ($message) use ($errorEmail, $database) {
                $message->to($errorEmail)
                        ->from(config('ninja.error_email'))
                        ->subject("Check-Data: " . strtoupper($this->isValid ? Account::RESULT_SUCCESS : Account::RESULT_FAILURE) . " [{$database}]");
            });
        } elseif (! $this->isValid) {
            throw new Exception("Check data failed!!\n" . $this->log);
        }
    }

    private function logMessage($str)
    {
        $str = date('Y-m-d h:i:s') . ' ' . $str;
        $this->info($str);
        $this->log .= $str . "\n";
    }

    private function checkOAuth()
    {
        // check for duplicate oauth ids
        $users = DB::table('users')
                    ->whereNotNull('oauth_user_id')
                    ->groupBy('users.oauth_user_id')
                    ->havingRaw('count(users.id) > 1')
                    ->get(['users.oauth_user_id']);

        $this->logMessage($users->count() . ' users with duplicate oauth ids');

        if ($users->count() > 0) {
            $this->isValid = false;
        }

        if ($this->option('fix') == 'true') {
            foreach ($users as $user) {
                $first = true;
                $this->logMessage('checking ' . $user->oauth_user_id);
                $matches = DB::table('users')
                            ->where('oauth_user_id', '=', $user->oauth_user_id)
                            ->orderBy('id')
                            ->get(['id']);

                foreach ($matches as $match) {
                    if ($first) {
                        $this->logMessage('skipping ' . $match->id);
                        $first = false;
                        continue;
                    }
                    $this->logMessage('updating ' . $match->id);

                    DB::table('users')
                        ->where('id', '=', $match->id)
                        ->where('oauth_user_id', '=', $user->oauth_user_id)
                        ->update([
                            'oauth_user_id' => null,
                            'oauth_provider_id' => null,
                        ]);
                }
            }
        }
    }

    private function checkContacts()
    {
        // check for contacts with the contact_key value set
        $contacts = DB::table('client_contacts')
                        ->whereNull('contact_key')
                        ->orderBy('id')
                        ->get(['id']);
        $this->logMessage($contacts->count() . ' contacts without a contact_key');

        if ($contacts->count() > 0) {
            $this->isValid = false;
        }

        if ($this->option('fix') == 'true') {
            foreach ($contacts as $contact) {
                DB::table('client_contacts')
                    ->where('id', '=', $contact->id)
                    ->whereNull('contact_key')
                    ->update([
                        'contact_key' => str_random(config('ninja.key_length')),
                    ]);
            }
        }

        // check for missing contacts
        $clients = DB::table('clients')
                    ->leftJoin('client_contacts', function($join) {
                        $join->on('client_contacts.client_id', '=', 'clients.id')
                            ->whereNull('client_contacts.deleted_at');
                    })
                    ->groupBy('clients.id', 'clients.user_id', 'clients.company_id')
                    ->havingRaw('count(client_contacts.id) = 0');

        if ($this->option('client_id')) {
            $clients->where('clients.id', '=', $this->option('client_id'));
        }

        $clients = $clients->get(['clients.id', 'clients.user_id', 'clients.company_id']);
        $this->logMessage($clients->count() . ' clients without any contacts');

        if ($clients->count() > 0) {
            $this->isValid = false;
        }

        if ($this->option('fix') == 'true') {
            foreach ($clients as $client) {
                $contact = new ClientContact();
                $contact->company_id = $client->company_id;
                $contact->user_id = $client->user_id;
                $contact->client_id = $client->id;
                $contact->is_primary = true;
                $contact->send_invoice = true;
                $contact->contact_key = str_random(config('ninja.key_length'));
                $contact->save();
            }
        }

        // check for more than one primary contact
        $clients = DB::table('clients')
                    ->leftJoin('client_contacts', function($join) {
                        $join->on('client_contacts.client_id', '=', 'clients.id')
                            ->where('client_contacts.is_primary', '=', true)
                            ->whereNull('client_contacts.deleted_at');
                    })
                    ->groupBy('clients.id')
                    ->havingRaw('count(client_contacts.id) != 1');

        if ($this->option('client_id')) {
            $clients->where('clients.id', '=', $this->option('client_id'));
        }

        $clients = $clients->get(['clients.id', DB::raw('count(client_contacts.id)')]);
        $this->logMessage($clients->count() . ' clients without a single primary contact');

        if ($clients->count() > 0) {
            $this->isValid = false;
        }
    }

    private function checkFailedJobs()
    {
        if (config('ninja.testvars.travis')) {
            return;
        }

        $queueDB = config('queue.connections.database.connection');
        $count = DB::connection($queueDB)->table('failed_jobs')->count();

        if ($count > 25) {
            $this->isValid = false;
        }

        $this->logMessage($count . ' failed jobs');
    }

    private function checkInvitations()
    {
        $invoices = DB::table('invoices')
                    ->leftJoin('invoice_invitations', function ($join) {
                        $join->on('invoice_invitations.invoice_id', '=', 'invoices.id')
                             ->whereNull('invoice_invitations.deleted_at');
                    })
                    ->groupBy('invoices.id', 'invoices.user_id', 'invoices.company_id', 'invoices.client_id')
                    ->havingRaw('count(invoice_invitations.id) = 0')
                    ->get(['invoices.id', 'invoices.user_id', 'invoices.company_id', 'invoices.client_id']);

        $this->logMessage($invoices->count() . ' invoices without any invitations');

        if ($invoices->count() > 0) {
            $this->isValid = false;
        }

        if ($this->option('fix') == 'true') {
            foreach ($invoices as $invoice) {
                $invitation = new InvoiceInvitation();
                $invitation->company_id = $invoice->company_id;
                $invitation->user_id = $invoice->user_id;
                $invitation->invoice_id = $invoice->id;
                $invitation->contact_id = ClientContact::whereClientId($invoice->client_id)->whereIsPrimary(true)->first()->id;
                $invitation->invitation_key = str_random(config('ninja.key_length'));
                $invitation->save();
            }
        }
    }

    private function checkPaidToDate()
    {
        //Check the client paid to date value matches the sum of payments by the client
    }

    private function checkInvoiceBalances()
    {
        // $invoices = DB::table('invoices')
        //             ->leftJoin('payments', function($join) {
        //                 $join->on('payments.invoice_id', '=', 'invoices.id')
        //                     ->where('payments.payment_status_id', '!=', 2)
        //                     ->where('payments.payment_status_id', '!=', 3)
        //                     ->where('payments.is_deleted', '=', 0);
        //             })
        //             ->where('invoices.updated_at', '>', '2017-10-01')
        //             ->groupBy('invoices.id')
        //             ->havingRaw('(invoices.amount - invoices.balance) != coalesce(sum(payments.amount - payments.refunded), 0)')
        //             ->get(['invoices.id', 'invoices.amount', 'invoices.balance', DB::raw('coalesce(sum(payments.amount - payments.refunded), 0)')]);

        // $this->logMessage($invoices->count() . ' invoices with incorrect balances');

        // if ($invoices->count() > 0) {
        //     $this->isValid = false;
        // }
    }

    private function checkClientBalances()
    {
        // find all clients where the balance doesn't equal the sum of the outstanding invoices
        // $clients = DB::table('clients')
        //             ->join('invoices', 'invoices.client_id', '=', 'clients.id')
        //             ->join('accounts', 'accounts.id', '=', 'clients.company_id')
        //             ->where('accounts.id', '!=', 20432)
        //             ->where('clients.is_deleted', '=', 0)
        //             ->where('invoices.is_deleted', '=', 0)
        //             ->where('invoices.is_public', '=', 1)
        //             ->where('invoices.invoice_type_id', '=', INVOICE_TYPE_STANDARD)
        //             ->where('invoices.is_recurring', '=', 0)
        //             ->havingRaw('abs(clients.balance - sum(invoices.balance)) > .01 and clients.balance != 999999999.9999');

        // if ($this->option('client_id')) {
        //     $clients->where('clients.id', '=', $this->option('client_id'));
        // }

        // $clients = $clients->groupBy('clients.id', 'clients.balance')
        //         ->orderBy('accounts.company_id', 'DESC')
        //         ->get(['accounts.company_id', 'clients.company_id', 'clients.id', 'clients.balance', 'clients.paid_to_date', DB::raw('sum(invoices.balance) actual_balance')]);
        // $this->logMessage($clients->count() . ' clients with incorrect balance/activities');

        // if ($clients->count() > 0) {
        //     $this->isValid = false;
        // }

        // foreach ($clients as $client) {
        //     $this->logMessage("=== Company: {$client->company_id} Account:{$client->company_id} Client:{$client->id} Balance:{$client->balance} Actual Balance:{$client->actual_balance} ===");

        //}
    }

    private function checkLogoFiles()
    {
        // $accounts = DB::table('accounts')
        //             ->where('logo', '!=', '')
        //             ->orderBy('id')
        //             ->get(['logo']);

        // $countMissing = 0;

        // foreach ($accounts as $account) {
        //     $path = public_path('logo/' . $account->logo);
        //     if (! file_exists($path)) {
        //         $this->logMessage('Missing file: ' . $account->logo);
        //         $countMissing++;
        //     }
        // }

        // if ($countMissing > 0) {
        //     $this->isValid = false;
        // }

        // $this->logMessage($countMissing . ' missing logo files');
    }

    /**
     * @return array
     */
    protected function getArguments()
    {
        return [];
    }

    /**
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['fix', null, InputOption::VALUE_OPTIONAL, 'Fix data', null],
            ['fast', null, InputOption::VALUE_OPTIONAL, 'Fast', null],
            ['client_id', null, InputOption::VALUE_OPTIONAL, 'Client id', null],
            ['database', null, InputOption::VALUE_OPTIONAL, 'Database', null],
        ];
    }
}
