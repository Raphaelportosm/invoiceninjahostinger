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

namespace App\Listeners\Invoice;

use App\Models\Activity;
use App\Models\ClientContact;
use App\Models\InvoiceInvitation;
use App\Notifications\Admin\EntitySentNotification;
use App\Notifications\Admin\InvoiceSentNotification;
use App\Repositories\ActivityRepository;
use App\Utils\Traits\MakesHash;
use App\Utils\Traits\Notifications\UserNotifies;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class InvoiceEmailedNotification implements ShouldQueue
{

    use UserNotifies;

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
        $invitation = $event->invitation;

        foreach($invitation->company->company_users as $company_user)
        {
            $user = $company_user->user;

            $notification = new EntitySentNotification($invitation, 'invoice');

            $notification->method = $this->findUserNotificationTypes($invitation, $company_user, 'invoice', ['all_notifications', 'invoice_sent']);

            $user->notify($notification);
           
        }

        // if(isset($invitation->company->slack_webhook_url)){

        //     Notification::route('slack', $invitation->company->slack_webhook_url)
        //         ->notify(new EntitySentNotification($invitation, $invitation->company, true));

        // }
    }


}
