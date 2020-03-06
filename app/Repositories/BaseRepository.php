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

namespace App\Repositories;

use App\Factory\InvoiceInvitationFactory;
use App\Factory\QuoteInvitationFactory;
use App\Jobs\Product\UpdateOrCreateProduct;
use App\Models\Client;
use App\Models\ClientContact;
use App\Models\Credit;
use App\Models\Invoice;
use App\Models\InvoiceInvitation;
use App\Models\Quote;
use App\Utils\Traits\MakesHash;
use ReflectionClass;

/**
 *
 */
class BaseRepository
{
    use MakesHash;
    /**
     * @return null
     */
    public function getClassName()
    {
        return null;
    }

    /**
     * @return mixed
     */
    private function getInstance()
    {
        $className = $this->getClassName();

        return new $className();
    }

    /**
     * @param $entity
     * @param $type
     *
     * @return string
     */
    private function getEventClass($entity, $type)
    {
        return 'App\Events\\' . ucfirst(class_basename($entity)) . 'Was' . $type;
    }

    /**
     * @param $entity
     */
    public function archive($entity)
    {
        if ($entity->trashed()) {
            return;
        }
        
        $entity->delete();

        $className = $this->getEventClass($entity, 'Archived');

        if (class_exists($className)) {
            event(new $className($entity));
        }
    }

    /**
     * @param $entity
     */
    public function restore($entity)
    {
        if (! $entity->trashed()) {
            return;
        }

        $fromDeleted = false;

        $entity->restore();

        if ($entity->is_deleted) {
            $fromDeleted = true;
            $entity->is_deleted = false;
            $entity->save();
        }

        $className = $this->getEventClass($entity, 'Restored');

        if (class_exists($className)) {
            event(new $className($entity, $fromDeleted));
        }
    }

    /**
     * @param $entity
     */
    public function delete($entity)
    {
        if ($entity->is_deleted) {
            return;
        }

        $entity->is_deleted = true;
        $entity->save();

        $entity->delete();

        $className = $this->getEventClass($entity, 'Deleted');

        if (class_exists($className)) {
            event(new $className($entity));
        }
    }

    /**
     * @param $ids
     * @param $action
     *
     * @return int
     */
    public function bulk($ids, $action)
    {
        if (! $ids) {
            return 0;
        }

        $ids = $this->transformKeys($ids);

        $entities = $this->findByPublicIdsWithTrashed($ids);

        foreach ($entities as $entity) {
            if (auth()->user()->can('edit', $entity)) {
                $this->$action($entity);
            }
        }

        return count($entities);
    }

    /**
     * @param $ids
     *
     * @return mixed
     */
    public function findByPublicIds($ids)
    {
        return $this->getInstance()->scope($ids)->get();
    }

    /**
     * @param $ids
     *
     * @return mixed
     */
    public function findByPublicIdsWithTrashed($ids)
    {
        return $this->getInstance()->scope($ids)->withTrashed()->get();
    }

    public function getInvitation($invitation, $resource)
	{

        if(!array_key_exists('key', $invitation))
            return false;

        $invitation_class = sprintf("App\\Models\\%sInvitation", $resource);

		$invitation = $invitation_class::whereRaw("BINARY `key`= ?", [$invitation['key']])->first();

        return $invitation;
	}

    /**
     * Alternative save used for Invoices, Quotes & Credits.
     */
    protected function alternativeSave($data, $model)
    {
        $class = new ReflectionClass($model);        

        $client = Client::find($data['client_id']);

        $state = [];
        $resource = explode('\\', $class->name)[2]; /** This will extract 'Invoice' from App\Models\Invoice */
        $lcfirst_resource_id = lcfirst($resource) . '_id';

        //if new, set defaults!
        if(!$model->id) {
            $methodName = "set" . $resource . "Defaults";
            $model = $client->{$methodName}($model);
        }


        if ($class->name == Invoice::class || $class->name == Quote::class) 
            $state['starting_amount'] = $model->amount;

        if (!$model->id) {
            $model->uses_inclusive_taxes = $client->getSetting('inclusive_taxes');
        }

        $model->fill($data);
        $model->save();

        $invitation_factory_class = sprintf("App\\Factory\\%sInvitationFactory", $resource);

        if (isset($data['client_contacts'])) {
            foreach ($data['client_contacts'] as $contact) {
                if ($contact['send_email'] == 1 && is_string($contact['id'])) {
                    $client_contact = ClientContact::find($this->decodePrimaryKey($contact['id']));
                    $client_contact->send_email = true;
                    $client_contact->save();
                }
            }
        }

        if (isset($data['invitations'])) {
            $invitations = collect($data['invitations']);

            /* Get array of Keys which have been removed from the invitations array and soft delete each invitation */
            $model->invitations->pluck('key')->diff($invitations->pluck('key'))->each(function ($invitation) {
                $this->getInvitation($invitation, $resource)->delete();
            });

            foreach ($data['invitations'] as $invitation) {

                //if no invitations are present - create one.
                if (! $this->getInvitation($invitation, $resource) ) {

                    if (isset($invitation['id'])) {
                        unset($invitation['id']);
                    }

                    //make sure we are creating an invite for a contact who belongs to the client only!
                    $contact = ClientContact::find($invitation['client_contact_id']);
                    
                    if($model->client_id == $contact->client_id);
                    {
                        $new_invitation = $invitation_factory_class::create($model->company_id, $model->user_id);
                        $new_invitation->{$lcfirst_resource_id} = $model->id;
                        $new_invitation->client_contact_id = $invitation['client_contact_id'];
                        $new_invitation->save();
                    }

                }
            }
        }

        $model->load('invitations');

		/* If no invitations have been created, this is our fail safe to maintain state*/
		if ($model->invitations->count() == 0) {
			$model->service()->createInvitations();
		}

		$state['finished_amount'] = $model->amount;

		$model = $model->service()->applyNumber()->save();
        
        if ($model->company->update_products !== false) {
            UpdateOrCreateProduct::dispatch($model->line_items, $model, $model->company);
        }

        if ($class->name == Invoice::class) {
            
            if (($state['finished_amount'] != $state['starting_amount']) && ($model->status_id != Invoice::STATUS_DRAFT)) {
                $model->ledger()->updateInvoiceBalance(($state['finished_amount'] - $state['starting_amount']));
            }

            $model = $model->calc()->getInvoice();

        }

        if ($class->name == Credit::class) {
            $model = $model->calc()->getCredit();
        }
        
        if ($class->name == Quote::class) {
            $model = $model->calc()->getQuote();
        }

        $model->save();

		return $model->fresh();
    }
}
