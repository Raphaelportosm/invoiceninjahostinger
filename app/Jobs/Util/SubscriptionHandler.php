<?php

namespace App\Jobs\Util;

use App\Models\Subscription;
use App\Transformers\ArraySerializer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;

class SubscriptionHandler implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $entity;

    private $event_id;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    
    public function __construct($event_id, $entity)
    {
        $this->event_id = $event_id;
        $this->entity = $entity;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $subscriptions = Subscription::where('company_id', $this->entity->company_id)
                                    ->where('event_id', $this->event_id)
                                    ->get();

        if(!$subscriptions || $subscriptions->count() == 0)
            return;

        $subscriptions->each(function($subscription) {
            $this->process($subscription);
        });
    }

    private function process($subscription)
    {
        // generate JSON data
        $manager = new Manager();
        $manager->setSerializer(new ArraySerializer());
        $manager->parseIncludes($include);

        $transformer = new $this->getTransformerClassName();

        $resource = new Item($this->entity, $transformer, $this->entity->getEntityType());
        $data = $manager->createData($resource)->toArray();

        $this->postData($subscription, $data, []);
    }

    private function getTransformerClassName()
    {
        return sprintf('App\\Transformers\\%sTransformer', class_basename($this->entity));
    }

    private function postData($subscription, $data, $headers = [])
    {
        $base_headers = [
            'Content-Length' => strlen($data),
            'Accept'         => 'application/json'
        ];

        $client = new \GuzzleHttp\Client(['headers' => array_merge($base_headers, $headers)]);
    
       //$response = $client->request('POST', $subscription->target_url, ['form_params' => $data]);
        
        $response = $client->post($subscription->target_url, [
                        GuzzleHttp\RequestOptions::JSON => $data // or 'json' => [...]
                    ]);

        if ($response->getStatusCode() == 410 || $response->getStatusCode() == 200) {
            $subscription->delete();
        }
    }
}
