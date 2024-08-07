<?php
namespace App\Services\Import\Quickbooks;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use App\Services\Import\Quickbooks\Auth;
use App\Repositories\Import\Quickbooks\Contracts\RepositoryInterface;
use App\Services\Import\QuickBooks\Contracts\SdkInterface as QuickbooksInterface;

final class Service
{    
    private QuickbooksInterface $sdk;

    public function __construct(QuickbooksInterface $quickbooks) {
        $this->sdk = $quickbooks;
    }

    public function getOAuth() : Auth
    {
        return new Auth($this->sdk);
    }

    public function getAccessToken() : array
    {
        // TODO: Cache token and 
        $token = $this->sdk->getAccessToken();
        $access_token = $token->getAccessToken();
        $refresh_token = $token->getRefreshToken();
        $access_token_expires = $token->getAccessTokenExpiresAt();
        $refresh_token_expires = $token->getRefreshTokenExpiresAt();       
        //TODO: Cache token object. Update $sdk instance?
        return compact('access_token', 'refresh_token','access_token_expires', 'refresh_token_expires');
    }

    public function getRefreshToken() : array
    {
        // TODO: Check if token is Cached otherwise fetch a new one and Cache token and expire
        return  $this->getAccessToken();
    }
    /**
     * fetch QuickBooks invoice records
     * @param int $max The maximum records to fetch. Default 100
     * @return Illuminate\Support\Collection;
     */
    public function fetchInvoices(int $max = 100): Collection
    {
        return $this->transformer->transform($this->fetchRecords( 'Invoice', $max), 'Invoice');
    }

    protected function fetchRecords(string $entity, $max = 100) : Collection {
        return (self::RepositoryFactory($entity))->get($max);
    }

    private static function RepositoryFactory(string $entity) : RepositoryInterface
    {
        return app("\\App\\Repositories\\Import\Quickbooks\\{$entity}Repository");
    }

    /**
     * fetch QuickBooks customer records
     * @param int $max The maximum records to fetch. Default 100
     * @return Illuminate\Support\Collection;
     */
    public function fetchCustomers(int $max = 100): Collection
    {
        return $this->fetchRecords('Customer', $max) ;
    }

    public function totalRecords(string $entity) : int
    {
        return (self::RepositoryFactory($entity))->count();
    }
}