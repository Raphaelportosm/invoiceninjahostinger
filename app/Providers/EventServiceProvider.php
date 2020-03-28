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

namespace App\Providers;

use App\Events\Client\ClientWasCreated;
use App\Events\Company\CompanyWasDeleted;
use App\Events\Contact\ContactLoggedIn;
use App\Events\Credit\CreditWasMarkedSent;
use App\Events\Invoice\InvoiceWasCreated;
use App\Events\Invoice\InvoiceWasEmailed;
use App\Events\Invoice\InvoiceWasMarkedSent;
use App\Events\Invoice\InvoiceWasPaid;
use App\Events\Invoice\InvoiceWasUpdated;
use App\Events\Misc\InvitationWasViewed;
use App\Events\Payment\PaymentWasCreated;
use App\Events\Payment\PaymentWasDeleted;
use App\Events\Payment\PaymentWasRefunded;
use App\Events\Payment\PaymentWasVoided;
use App\Events\User\UserLoggedIn;
use App\Events\User\UserWasCreated;
use App\Events\User\UserWasDeleted;
use App\Listeners\Activity\CreatedClientActivity;
use App\Listeners\Activity\PaymentCreatedActivity;
use App\Listeners\Activity\PaymentDeletedActivity;
use App\Listeners\Activity\PaymentRefundedActivity;
use App\Listeners\Activity\PaymentVoidedActivity;
use App\Listeners\Contact\UpdateContactLastLogin;
use App\Listeners\Document\DeleteCompanyDocuments;
use App\Listeners\Invoice\CreateInvoiceActivity;
use App\Listeners\Invoice\CreateInvoiceHtmlBackup;
use App\Listeners\Invoice\CreateInvoiceInvitation;
use App\Listeners\Invoice\CreateInvoicePdf;
use App\Listeners\Invoice\InvoiceEmailActivity;
use App\Listeners\Invoice\InvoiceEmailFailedActivity;
use App\Listeners\Invoice\InvoiceEmailedNotification;
use App\Listeners\Invoice\UpdateInvoiceActivity;
use App\Listeners\Invoice\UpdateInvoiceInvitations;
use App\Listeners\Misc\InvitationViewedListener;
use App\Listeners\Payment\PaymentNotification;
use App\Listeners\SendVerificationNotification;
use App\Listeners\SetDBListener;
use App\Listeners\User\DeletedUserActivity;
use App\Listeners\User\UpdateUserLastLogin;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        \Codedge\Updater\Events\UpdateAvailable::class => [
        \Codedge\Updater\Listeners\SendUpdateAvailableNotification::class
            ], // [3]
            \Codedge\Updater\Events\UpdateSucceeded::class => [
                \Codedge\Updater\Listeners\SendUpdateSucceededNotification::class
            ],
        UserWasCreated::class => [
            SendVerificationNotification::class,
        ],
        UserWasDeleted::class => [
            DeletedUserActivity::class,
        ],
        UserLoggedIn::class => [
            UpdateUserLastLogin::class,
        ],
        ContactLoggedIn::class => [
            UpdateContactLastLogin::class,
        ],
        // Clients
        ClientWasCreated::class => [
            CreatedClientActivity::class,
           // 'App\Listeners\SubscriptionListener@createdClient',
        ],
        PaymentWasCreated::class => [
            PaymentCreatedActivity::class,
            PaymentNotification::class,
        ],
        PaymentWasDeleted::class => [
            PaymentDeletedActivity::class,
        ],
        PaymentWasRefunded::class => [
            PaymentRefundedActivity::class,
        ],
        PaymentWasVoided::class => [
            PaymentVoidedActivity::class,
        ],
        'App\Events\ClientWasArchived' => [
            'App\Listeners\ActivityListener@archivedClient',
        ],
        'App\Events\ClientWasUpdated' => [
            'App\Listeners\SubscriptionListener@updatedClient',
        ],
        'App\Events\ClientWasDeleted' => [
            'App\Listeners\ActivityListener@deletedClient',
            'App\Listeners\SubscriptionListener@deletedClient',
            'App\Listeners\HistoryListener@deletedClient',
        ],
        'App\Events\ClientWasRestored' => [
            'App\Listeners\ActivityListener@restoredClient',
        ],

        CreditWasMarkedSent::class => [
        ],

        //Invoices
        InvoiceWasMarkedSent::class => [
            CreateInvoiceHtmlBackup::class,
        ],
        InvoiceWasUpdated::class => [
            UpdateInvoiceActivity::class,
            CreateInvoicePdf::class,
        ],
        InvoiceWasCreated::class => [
            CreateInvoiceActivity::class,
        //    CreateInvoicePdf::class,
        ],
        InvoiceWasPaid::class => [
            CreateInvoiceHtmlBackup::class,
        ],
        InvoiceWasEmailed::class => [
            InvoiceEmailActivity::class,
            InvoiceEmailedNotification::class,
        ],
        InvoiceWasEmailedAndFailed::class => [
            InvoiceEmailFailedActivity::class,
        ],

        InvitationWasViewed::class => [
            InvitationViewedListener::class
        ],

        CompanyWasDeleted::class => [
            DeleteCompanyDocuments::class,
        ],
    ];

    /**
     * The subscriber classes to register.
     *
     * @var array
     */
    protected $subscribe = [

    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    // public function boot()
    // {
    //     parent::boot();
    // }

    public function boot()
     {
         parent::boot();
         //$events->subscribe('*');
        // \Event::listen('event.*', function ($eventName, array $data) {
        //     \Log::error("Event Service Provider");
        // });


     }
}
