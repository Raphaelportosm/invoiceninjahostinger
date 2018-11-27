<?php

namespace App\Repositories;

use App\Models\Client;
use App\Repositories\ClientContactRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * 
 */
class ClientRepository extends BaseRepository
{
	protected $clientContactRepo;
	
    public function __construct(ClientContactRepository $clientContactRepo)
    {
        $this->clientContactRepo = $clientContactRepo;
    }

	public function save(Request $request, Client $client) : ?Client
	{
		Log::error(print_r($request->input(),1));
        $client->fill($request->input());
        $client->save();

        $this->clientContactRepo->save($request->input('contacts'), $client);

        return $client;
	}

}