<?php

namespace App\Jobs\Mail;

use App\Libraries\Google\Google;
use App\Libraries\MultiDB;
use App\Models\User;
use App\Providers\MailServiceProvider;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Config;

class BaseMailerJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    public function setMailDriver(string $driver)
    {
        switch ($driver) {
            case 'default':
                break;
            case 'gmail':
                $this->setGmailMailer();
                break;
            default:
                break;
        }

    }

    public function setGmailMailer()
    {
        $sending_user = $this->entity->client->getSetting('gmail_sending_user_id');

        $user = User::find($sending_user);

        $google = (new Google())->init();
        $google->getClient()->setAccessToken(json_encode($user->oauth_user_token));

        if ($google->getClient()->isAccessTokenExpired()) {
            $google->refreshToken($user);
        }

        /* 
         *  Now that our token is refresh and valid we can boot the 
         *  mail driver at runtime and also set the token which will persist
         *  just for this request.
        */
       
        Config::set('mail.driver', 'gmail');
        Config::set('services.gmail.token', $user->oauth_user_token->access_token);

        (new MailServiceProvider(app()))->register();

    }

}