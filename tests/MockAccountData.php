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

namespace Tests;

use App\DataMapper\ClientSettings;
use App\DataMapper\CompanySettings;
use App\DataMapper\DefaultSettings;
use App\Factory\ClientFactory;
use App\Factory\InvoiceFactory;
use App\Factory\InvoiceItemFactory;
use App\Factory\InvoiceToRecurringInvoiceFactory;
use App\Jobs\Company\UpdateCompanyLedgerWithInvoice;
use App\Models\Client;
use App\Models\CompanyGateway;
use App\Models\CompanyToken;
use App\Models\Credit;
use App\Models\GroupSetting;
use App\Models\Invoice;
use App\Models\Quote;
use App\Models\RecurringInvoice;
use App\Utils\Traits\GeneratesCounter;
use App\Utils\Traits\MakesHash;
use App\Helpers\Invoice\InvoiceSum;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Class MockAccountData
 * @package Tests\Unit
 */
trait MockAccountData
{

	use MakesHash;
	use GeneratesCounter;

	public $account;

	public $company;

	public $user;

	public $client;

    public $token;

	public function makeTestData()
	{
        $this->account = factory(\App\Models\Account::class)->create();
        $this->company = factory(\App\Models\Company::class)->create([
            'account_id' => $this->account->id,
            'domain' => 'ninja.test:8000',
        ]);

        $this->account->default_company_id = $this->company->id;
        $this->account->save();

        $this->user = factory(\App\Models\User::class)->create([
        //    'account_id' => $account->id,
            'confirmation_code' => $this->createDbHash(config('database.default'))
        ]);

        $this->token = \Illuminate\Support\Str::random(64);

        $company_token = CompanyToken::create([
            'user_id' => $this->user->id,
            'company_id' => $this->company->id,
            'account_id' => $this->account->id,
            'name' => 'test token',
            'token' => $this->token,
        ]);

        $this->user->companies()->attach($this->company->id, [
            'account_id' => $this->account->id,
            'is_owner' => 1,
            'is_admin' => 1,
            'is_locked' => 0,
            'permissions' => json_encode([]),
            'settings' => json_encode(DefaultSettings::userSettings()),
        ]);

        $this->client = ClientFactory::create($this->company->id, $this->user->id);
        $this->client->save();

        $gs = new GroupSetting;
        $gs->name = 'Test';
        $gs->company_id = $this->client->company_id;
        $gs->settings = ClientSettings::buildClientSettings($this->company->settings, $this->client->settings);
        $gs->save();

        $this->client->group_settings_id = $gs->id;
        $this->client->save();



        $this->invoice = InvoiceFactory::create($this->company->id,$this->user->id);//stub the company and user_id
        $this->invoice->client_id = $this->client->id;

		$this->invoice->line_items = $this->buildLineItems();
		
		$this->settings = $this->client->getMergedSettings();

		$this->settings->custom_taxes1 = false;
		$this->settings->custom_taxes2 = false;
		$this->settings->inclusive_taxes = false;
		$this->settings->precision = 2;

		$this->invoice->settings = $this->settings;

		$this->invoice_calc = new InvoiceSum($this->invoice, $this->settings);
		$this->invoice_calc->build();

		$this->invoice = $this->invoice_calc->getInvoice();

        $this->invoice->save();

        UpdateCompanyLedgerWithInvoice::dispatchNow($this->invoice, $this->invoice->amount);

        $recurring_invoice = InvoiceToRecurringInvoiceFactory::create($this->invoice);
        $recurring_invoice->next_send_date = Carbon::now();
        $recurring_invoice->status_id = RecurringInvoice::STATUS_ACTIVE;
        $recurring_invoice->remaining_cycles = 2;
        $recurring_invoice->start_date = Carbon::now();
        $recurring_invoice->save();
        
        $recurring_invoice->invoice_number = $this->getNextInvoiceNumber($this->invoice->client);
        $recurring_invoice->save();

        $recurring_invoice = InvoiceToRecurringInvoiceFactory::create($this->invoice);
        $recurring_invoice->next_send_date = Carbon::now()->addMinutes(2);
        $recurring_invoice->status_id = RecurringInvoice::STATUS_ACTIVE;
        $recurring_invoice->remaining_cycles = 2;
        $recurring_invoice->start_date = Carbon::now();
        $recurring_invoice->save();
        
        $recurring_invoice->invoice_number = $this->getNextInvoiceNumber($this->invoice->client);
        $recurring_invoice->save();

        $recurring_invoice = InvoiceToRecurringInvoiceFactory::create($this->invoice);
        $recurring_invoice->next_send_date = Carbon::now()->addMinutes(10);
        $recurring_invoice->status_id = RecurringInvoice::STATUS_ACTIVE;
        $recurring_invoice->remaining_cycles = 2;
        $recurring_invoice->start_date = Carbon::now();
        $recurring_invoice->save();
        
        $recurring_invoice->invoice_number = $this->getNextInvoiceNumber($this->invoice->client);
        $recurring_invoice->save();

        $recurring_invoice = InvoiceToRecurringInvoiceFactory::create($this->invoice);
        $recurring_invoice->next_send_date = Carbon::now()->addMinutes(15);
        $recurring_invoice->status_id = RecurringInvoice::STATUS_ACTIVE;
        $recurring_invoice->remaining_cycles = 2;
        $recurring_invoice->start_date = Carbon::now();
        $recurring_invoice->save();
        
        $recurring_invoice->invoice_number = $this->getNextInvoiceNumber($this->invoice->client);
        $recurring_invoice->save();


        $recurring_invoice = InvoiceToRecurringInvoiceFactory::create($this->invoice);
        $recurring_invoice->next_send_date = Carbon::now()->addMinutes(20);
        $recurring_invoice->status_id = RecurringInvoice::STATUS_ACTIVE;
        $recurring_invoice->remaining_cycles = 2;
        $recurring_invoice->start_date = Carbon::now();
        $recurring_invoice->save();
        
        $recurring_invoice->invoice_number = $this->getNextInvoiceNumber($this->invoice->client);
        $recurring_invoice->save();

        $recurring_invoice = InvoiceToRecurringInvoiceFactory::create($this->invoice);
        $recurring_invoice->next_send_date = Carbon::now()->addDays(10);
        $recurring_invoice->status_id = RecurringInvoice::STATUS_ACTIVE;
        $recurring_invoice->remaining_cycles = 2;
        $recurring_invoice->start_date = Carbon::now();
        $recurring_invoice->save();
        
        $recurring_invoice->invoice_number = $this->getNextInvoiceNumber($this->invoice->client);
        $recurring_invoice->save();

        $gs = new GroupSetting;
        $gs->company_id = $this->company->id;
        $gs->user_id = $this->user->id;
        $gs->settings = ClientSettings::buildClientSettings(CompanySettings::defaults(), ClientSettings::defaults());
        $gs->name = 'Default Client Settings';
        $gs->save();

        if(config('ninja.testvars.stripe'))
        {

            $cg = new CompanyGateway;
            $cg->company_id = $this->company->id;
            $cg->user_id = $this->user->id;
            $cg->gateway_key = 'd14dd26a37cecc30fdd65700bfb55b23';
            $cg->require_cvv = true;
            $cg->show_billing_address = true;
            $cg->show_shipping_address = true;
            $cg->update_details = true;
            $cg->config = encrypt(config('ninja.testvars.stripe'));
            $cg->priority_id = 1;
            $cg->save();


            $cg = new CompanyGateway;
            $cg->company_id = $this->company->id;
            $cg->user_id = $this->user->id;
            $cg->gateway_key = 'd14dd26a37cecc30fdd65700bfb55b23';
            $cg->require_cvv = true;
            $cg->show_billing_address = true;
            $cg->show_shipping_address = true;
            $cg->update_details = true;
            $cg->config = encrypt(config('ninja.testvars.stripe'));
            $cg->priority_id = 2;
            $cg->save();
        }

	}


	private function buildLineItems()
	{
		$line_items = [];

		$item = InvoiceItemFactory::create();
		$item->qty = 1;
		$item->cost =10;

		$line_items[] = $item;

		return $line_items;

	}
}