<?php
/**
 * Invoice Ninja (https://invoiceninja.com).
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2020. Invoice Ninja LLC (https://invoiceninja.com)
 *
 * @license https://opensource.org/licenses/AAL
 */

namespace App\Utils;

use App\Http\Requests\Setup\CheckDatabaseRequest;
use App\Http\Requests\Setup\CheckMailRequest;
use App\Libraries\MultiDB;
use App\Mail\TestMailServer;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Class SystemHealth.
 */
class SystemHealth
{
    private static $extensions = [
        'mysqli',
        'gd',
        'curl',
        'zip',
        'gmp',
        'openssl',
        'mbstring',
        'xml',
    ];

    private static $php_version = 7.3;

    /**
     * Check loaded extensions / PHP version / DB Connections.
     *
     * @return     array  Result set of checks
     */
    public static function check() : array
    {
        $system_health = true;

        if (in_array(false, Arr::dot(self::extensions()))) {
            $system_health = false;
        } elseif (phpversion() < self::$php_version) {
            $system_health = false;
        }

        return [
            'system_health' => (bool) $system_health,
            'extensions' => self::extensions(),
            'php_version' => [
                'minimum_php_version' => self::$php_version,
                'current_php_version' => phpversion(),
                'is_okay' => version_compare(phpversion(), self::$php_version, '>='),
            ],
            'env_writable' => self::checkEnvWritable(),
            //'dbs' => self::dbCheck(),
            //'mail' => self::testMailServer(),
        ];
    }

    private static function extensions() :array
    {
        $loaded_extensions = [];

        foreach (self::$extensions as $extension) {
            $loaded_extensions[] = [$extension => extension_loaded($extension)];
        }

        return $loaded_extensions;
    }

    public static function dbCheck($request = null): array
    {
        $result = ['success' => false];

        if ($request && $request instanceof CheckDatabaseRequest) {
            config(['database.connections.db-ninja-01.host'=> $request->input('host')]);
            config(['database.connections.db-ninja-01.database'=> $request->input('database')]);
            config(['database.connections.db-ninja-01.username'=> $request->input('username')]);
            config(['database.connections.db-ninja-01.password'=> $request->input('password')]);
            config(['database.default' => 'db-ninja-01']);

            DB::purge('db-ninja-01');
        }

        if (! config('ninja.db.multi_db_enabled')) {
            try {
                $pdo = DB::connection()->getPdo();
                $result[] = [DB::connection()->getDatabaseName() => true];
                $result['success'] = true;
            } catch (\Exception $e) {
                $result[] = [config('database.connections.'.config('database.default').'.database') => false];
                $result['success'] = false;
            }
        } else {
            foreach (MultiDB::$dbs as $db) {
                MultiDB::setDB($db);

                try {
                    $pdo = DB::connection()->getPdo();
                    $result[] = [DB::connection()->getDatabaseName() => true];
                    $result['success'] = true;
                } catch (\Exception $e) {
                    $result[] = [config('database.connections.'.config('database.default').'.database') => false];
                    $result['success'] = false;
                }
            }
        }

        return $result;
    }

    private static function checkDbConnection()
    {
        return DB::connection()->getPdo();
    }

    public static function testMailServer($request = null)
    {
        if ($request && $request instanceof CheckMailRequest) {
            config(['mail.driver' => $request->input('driver')]);
            config(['mail.host' => $request->input('host')]);
            config(['mail.port' => $request->input('port')]);
            config(['mail.from.address' => $request->input('from_address')]);
            config(['mail.from.name' => $request->input('from_name')]);
            config(['mail.encryption' => $request->input('encryption')]);
            config(['mail.username' => $request->input('username')]);
            config(['mail.password' => $request->input('password')]);
        }

        try {
            Mail::to(config('mail.from.address'))
            ->send(new TestMailServer('Email Server Works!', config('mail.from.address')));
        } catch (\Exception $e) {
            \Log::error($e->getMessage());

            return $e->getMessage();
        }

        if (count(Mail::failures()) > 0) {
            \Log::error(print_r(Mail::failures(), 1));

            return Mail::failures();
        }

        return response()->json(['message'=>'Success'], 200);
    }

    private static function checkEnvWritable()
    {
        return is_writable(base_path().'/.env');
    }
}
