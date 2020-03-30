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

namespace App\Http\Controllers;

use App\Http\Requests\Setup\CheckDatabaseRequest;
use App\Http\Requests\Setup\CheckMailRequest;
use App\Http\Requests\Setup\StoreSetupRequest;
use App\Jobs\Account\CreateAccount;
use App\Models\Account;
use App\Utils\SystemHealth;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Artisan;

/**
 * Class SetupController.
 */
class SetupController extends Controller
{
    /**
     * Main setup view.
     *
     * @return \Illuminate\View\View|\Illuminate\Contracts\View\Factory
     */
    public function index()
    {
        $check = SystemHealth::check();

        return view('setup.index', ['check' => $check]);
    }

    public function doSetup(StoreSetupRequest $request)
    {
        $check = SystemHealth::check();

        if ($check['system_status'] === false) {
            return; /* This should never be reached. */
        }

        $_ENV['APP_KEY'] = config('app.key');
        $_ENV['APP_URL'] = $request->input('url');
        $_ENV['APP_DEBUG'] = $request->input('debug') ? 'true' : 'false';
        $_ENV['REQUIRE_HTTPS'] = $request->input('https') ? 'true' : 'false';
        $_ENV['DB_TYPE'] = 'mysql';
        $_ENV['DB_HOST1'] = $request->input('host');
        $_ENV['DB_DATABASE1'] = $request->input('database');
        $_ENV['DB_USERNAME1'] = $request->input('db_username');
        $_ENV['DB_PASSWORD1'] = $request->input('db_password');
        $_ENV['MAIL_DRIVER'] = $request->input('mail_driver');
        $_ENV['MAIL_PORT'] = $request->input('mail_port');
        $_ENV['MAIL_ENCRYPTION'] = $request->input('encryption');
        $_ENV['MAIL_HOST'] = $request->input('mail_host');
        $_ENV['MAIL_USERNAME'] = $request->input('mail_username');
        $_ENV['MAIL_FROM_NAME'] = $request->input('mail_name');
        $_ENV['MAIL_FROM_ADDRESS'] = $request->input('mail_address');
        $_ENV['MAIL_PASSWORD'] = $request->input('mail_password');
        $_ENV['NINJA_ENVIRONMENT'] = 'selfhost';
        $_ENV['SELF_UPDATER_REPO_VENDOR'] = 'invoiceninja';
        $_ENV['SELF_UPDATER_REPO_NAME'] = 'invoiceninja';
        $_ENV['SELF_UPDATER_USE_BRANCH'] = 'v2';
        $_ENV['SELF_UPDATER_MAILTO_ADDRESS'] = $request->input('mail_address');
        $_ENV['SELF_UPDATER_MAILTO_NAME'] = $request->input('mail_name');
        $_ENV['DB_CONNECTION'] = 'db-ninja-01';

        $config = '';

        foreach ($_ENV as $key => $val) {
            if (is_array($val)) {
                continue;
            }
            if (preg_match('/\s/', $val)) {
                $val = "'{$val}'";
            }
            $config .= "{$key}={$val}\n";
        }

        /* Write the .env file */
        $filePath = base_path().'/.env';
        $fp = fopen($filePath, 'w');
        fwrite($fp, $config);
        fclose($fp);

        /* We need this in some environments that do not have STDIN defined */
        define('STDIN', fopen('php://stdin', 'r'));

        /* Make sure no stale connections are cached */
        \DB::purge('db-ninja-01');

        /* Run migrations */
        Artisan::call('optimize');
        Artisan::call('migrate', ['--force' => true]);
        Artisan::call('db:seed', ['--force' => true]);

        /* Create the first account. */
        if (Account::count() == 0) {
            $account = CreateAccount::dispatchNow($request->all());
        }

        return redirect('/');
    }

    /**
     * Return status based on check of database connection.
     *
     * @return Response
     */
    public function checkDB(CheckDatabaseRequest $request): Response
    {
        $status = SystemHealth::dbCheck($request);

        info($status);

        if (is_array($status) && $status['success'] === true) {
            return response([], 200);
        }

        return response([], 400);
    }

    /**
     * Return status based on check of SMTP connection.
     *
     * @return Response
     */
    public function checkMail(CheckMailRequest $request)
    {
        try {
            $response_array = SystemHealth::testMailServer($request);

            if (count($response_array) == 0) {
                return response([], 200);
            } else {
                return response()->json($response_array, 200);
            }
`        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}
