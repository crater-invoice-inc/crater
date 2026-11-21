<?php

namespace Crater\Space;

use Crater\Http\Requests\DatabaseEnvironmentRequest;
use Crater\Http\Requests\DiskEnvironmentRequest;
use Crater\Http\Requests\DomainEnvironmentRequest;
use Crater\Http\Requests\MailEnvironmentRequest;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EnvironmentManager
{
    /**
     * @var string
     */
    private $envPath;

    /**
     * Set the .env and .env.example paths.
     */
    public function __construct()
    {
        $this->envPath = base_path('.env');
    }

    /**
     * Save the database content to the .env file.
     *
     * @param DatabaseEnvironmentRequest $request
     * @return array
     */
    public function saveDatabaseVariables(DatabaseEnvironmentRequest $request)
    {
        $oldDatabaseData =
            'DB_CONNECTION='.config('database.default')."\n";

        $newDatabaseData =
            'DB_CONNECTION='.$request->database_connection."\n";

        if ($request->has('database_username') && $request->has('database_password')) {
            if (env('DB_USERNAME') && env('DB_HOST')) {
                $oldDatabaseData = $oldDatabaseData.
                    'DB_HOST='.config('database.connections.'.config('database.default').'.host')."\n".
                    'DB_PORT='.config('database.connections.'.config('database.default').'.port')."\n".
                    'DB_DATABASE='.config('database.connections.'.config('database.default').'.database')."\n".
                    'DB_USERNAME='.config('database.connections.'.config('database.default').'.username')."\n".
                    'DB_PASSWORD="'.config('database.connections.'.config('database.default').'.password')."\"\n\n";
            } else {
                $oldDatabaseData = $oldDatabaseData.
                    'DB_DATABASE='.config('database.connections.'.config('database.default').'.database')."\n\n";
            }

            $newDatabaseData = $newDatabaseData.
                'DB_HOST='.$request->database_hostname."\n".
                'DB_PORT='.$request->database_port."\n".
                'DB_DATABASE='.$request->database_name."\n".
                'DB_USERNAME='.$request->database_username."\n".
                'DB_PASSWORD="'.$request->database_password."\"\n\n";
        } else {
            if (env('DB_USERNAME') && env('DB_HOST')) {
                $oldDatabaseData = $oldDatabaseData.
                    'DB_HOST='.config('database.connections.'.config('database.default').'.host')."\n".
                    'DB_PORT='.config('database.connections.'.config('database.default').'.port')."\n".
                    'DB_DATABASE='.config('database.connections.'.config('database.default').'.database')."\n".
                    'DB_USERNAME='.config('database.connections.'.config('database.default').'.username')."\n".
                    'DB_PASSWORD="'.config('database.connections.'.config('database.default').'.password')."\"\n\n";
            } else {
                $oldDatabaseData = $oldDatabaseData.
                    'DB_DATABASE='.config('database.connections.'.config('database.default').'.database')."\n\n";
            }

            $newDatabaseData = $newDatabaseData.
                'DB_DATABASE='.$request->database_name."\n\n";
        }

        try {
            $conn = $this->checkDatabaseConnection($request);

            // $requirement = $this->checkVersionRequirements($request, $conn);

            // if ($requirement) {
            //     return [
            //         'error' => 'minimum_version_requirement',
            //         'requirement' => $requirement,
            //     ];
            // }

            if (\Schema::hasTable('users')) {
                return [
                    'error' => 'database_should_be_empty',
                ];
            }
        } catch (Exception $e) {
            return [
                'error_message' => $e->getMessage(),
            ];
        }

        try {
            file_put_contents($this->envPath, str_replace(
                $oldDatabaseData,
                $newDatabaseData,
                file_get_contents($this->envPath)
            ));

            file_put_contents($this->envPath, str_replace(
                'APP_URL='.config('app.url'),
                'APP_URL='.$request->app_url,
                file_get_contents($this->envPath)
            ));

            file_put_contents($this->envPath, str_replace(
                'SANCTUM_STATEFUL_DOMAINS='.env('SANCTUM_STATEFUL_DOMAINS'),
                'SANCTUM_STATEFUL_DOMAINS='.$request->app_domain,
                file_get_contents($this->envPath)
            ));


            file_put_contents($this->envPath, str_replace(
                'SESSION_DOMAIN='.config('session.domain'),
                'SESSION_DOMAIN='.explode(':', $request->app_domain)[0],
                file_get_contents($this->envPath)
            ));
        } catch (Exception $e) {
            return [
                'error' => 'database_variables_save_error',
            ];
        }

        return [
            'success' => 'database_variables_save_successfully',
        ];
    }

    /**
     *
     * @param DatabaseEnvironmentRequest $request
     * @return bool
     */
    private function checkDatabaseConnection(DatabaseEnvironmentRequest $request)
    {
        $connection = $request->database_connection;

        $settings = config("database.connections.$connection");
        $settings = config("database.connections.$connection");

        $connectionArray = array_merge($settings, [
            'driver' => $connection,
            'database' => $request->database_name,
        ]);

        if ($request->has('database_username') && $request->has('database_password')) {
            $connectionArray = array_merge($connectionArray, [
                'username' => $request->database_username,
                'password' => $request->database_password,
                'host' => $request->database_hostname,
                'port' => $request->database_port,
            ]);
        }

        config([
            'database' => [
                'migrations' => 'migrations',
                'default' => $connection,
                'connections' => [$connection => $connectionArray],
            ],
        ]);

        return DB::connection()->getPdo();
    }

    /**
     *
     * @param DatabaseEnvironmentRequest $request
     * @return bool
     */
    private function checkVersionRequirements(DatabaseEnvironmentRequest $request, $conn)
    {
        $connection = $request->database_connection;

        $checker = new RequirementsChecker();

        $phpSupportInfo = $checker->checkPHPVersion(
            config('crater.min_php_version')
        );

        if (! $phpSupportInfo['supported']) {
            return $phpSupportInfo;
        }

        $dbSupportInfo = [];

        switch ($connection) {
            case 'mysql':
                $dbSupportInfo = $checker->checkMysqlVersion($conn);

                break;

            case 'pgsql':
                $conn = pg_connect("host={$request->database_hostname} port={$request->database_port} dbname={$request->database_name} user={$request->database_username} password={$request->database_password}");
                $dbSupportInfo = $checker->checkPgsqlVersion(
                    $conn,
                    config('crater.min_pgsql_version')
                );

                break;

            case 'sqlite':
                $dbSupportInfo = $checker->checkSqliteVersion(
                    config('crater.min_sqlite_version')
                );

                break;

        }

        if (! $dbSupportInfo['supported']) {
            return $dbSupportInfo;
        }

        return false;
    }

    /**
    * Save the mail content to the .env file.
    *
    * @param Request $request
    * @return array
    */
    public function saveMailVariables(MailEnvironmentRequest $request)
    {
        $mailData = $this->getMailData($request);

        try {
            file_put_contents($this->envPath, str_replace(
                $mailData['old_mail_data'],
                $mailData['new_mail_data'],
                file_get_contents($this->envPath)
            ));

            if ($mailData['extra_old_mail_data']) {
                file_put_contents($this->envPath, str_replace(
                    $mailData['extra_old_mail_data'],
                    $mailData['extra_mail_data'],
                    file_get_contents($this->envPath)
                ));
            } else {
                file_put_contents(
                    $this->envPath,
                    "\n".$mailData['extra_mail_data'],
                    FILE_APPEND
                );
            }
        } catch (Exception $e) {
            return [
                'error' => 'mail_variables_save_error',
            ];
        }

        return [
            'success' => 'mail_variables_save_successfully',
        ];
    }

    private function getMailData($request)
    {
        $mailFromCredential = "";
        $extraMailData = "";
        $extraOldMailData = "";
        $oldMailData = "";
        $newMailData = "";

        if (env('MAIL_FROM_ADDRESS') !== null && env('MAIL_FROM_NAME') !== null) {
            $mailFromCredential =
                'MAIL_FROM_ADDRESS='.config('mail.from.address')."\n".
                'MAIL_FROM_NAME="'.config('mail.from.name')."\"\n\n";
        }

        switch ($request->mail_driver) {
            case 'smtp':

                $oldMailData =
                    'MAIL_DRIVER='.config('mail.driver')."\n".
                    'MAIL_HOST='.config('mail.host')."\n".
                    'MAIL_PORT='.config('mail.port')."\n".
                    'MAIL_USERNAME='.config('mail.username')."\n".
                    'MAIL_PASSWORD="'.config('mail.password')."\"\n".
                    'MAIL_ENCRYPTION='.config('mail.encryption')."\n\n".
                    $mailFromCredential;

                $newMailData =
                    'MAIL_DRIVER='.$request->mail_driver."\n".
                    'MAIL_HOST='.$request->mail_host."\n".
                    'MAIL_PORT='.$request->mail_port."\n".
                    'MAIL_USERNAME='.$request->mail_username."\n".
                    'MAIL_PASSWORD="'.$request->mail_password."\"\n".
                    'MAIL_ENCRYPTION='.$request->mail_encryption."\n\n".
                    'MAIL_FROM_ADDRESS='.$request->from_mail."\n".
                    'MAIL_FROM_NAME="'.$request->from_name."\"\n\n";

                break;

            case 'mailgun':
                $oldMailData =
                    'MAIL_DRIVER='.config('mail.driver')."\n".
                    'MAIL_HOST='.config('mail.host')."\n".
                    'MAIL_PORT='.config('mail.port')."\n".
                    'MAIL_USERNAME='.config('mail.username')."\n".
                    'MAIL_PASSWORD="'.config('mail.password')."\"\n".
                    'MAIL_ENCRYPTION='.config('mail.encryption')."\n\n".
                    $mailFromCredential;

                $newMailData =
                    'MAIL_DRIVER='.$request->mail_driver."\n".
                    'MAIL_HOST='.$request->mail_host."\n".
                    'MAIL_PORT='.$request->mail_port."\n".
                    'MAIL_USERNAME='.config('mail.username')."\n".
                    'MAIL_PASSWORD="'.config('mail.password')."\"\n".
                    'MAIL_ENCRYPTION='.$request->mail_encryption."\n\n".
                    'MAIL_FROM_ADDRESS='.$request->from_mail."\n".
                    'MAIL_FROM_NAME="'.$request->from_name."\"\n\n";

                $extraMailData =
                    'MAILGUN_DOMAIN='.$request->mail_mailgun_domain."\n".
                    'MAILGUN_SECRET='.$request->mail_mailgun_secret."\n".
                    'MAILGUN_ENDPOINT='.$request->mail_mailgun_endpoint."\n";

                if (env('MAILGUN_DOMAIN') !== null && env('MAILGUN_SECRET') !== null && env('MAILGUN_ENDPOINT') !== null) {
                    $extraOldMailData =
                        'MAILGUN_DOMAIN='.config('services.mailgun.domain')."\n".
                        'MAILGUN_SECRET='.config('services.mailgun.secret')."\n".
                        'MAILGUN_ENDPOINT='.config('services.mailgun.endpoint')."\n";
                }

                break;

            case 'ses':
                $oldMailData =
                    'MAIL_DRIVER='.config('mail.driver')."\n".
                    'MAIL_HOST='.config('mail.host')."\n".
                    'MAIL_PORT='.config('mail.port')."\n".
                    'MAIL_USERNAME='.config('mail.username')."\n".
                    'MAIL_PASSWORD="'.config('mail.password')."\"\n".
                    'MAIL_ENCRYPTION='.config('mail.encryption')."\n\n".
                    $mailFromCredential;

                $newMailData =
                    'MAIL_DRIVER='.$request->mail_driver."\n".
                    'MAIL_HOST='.$request->mail_host."\n".
                    'MAIL_PORT='.$request->mail_port."\n".
                    'MAIL_USERNAME='.config('mail.username')."\n".
                    'MAIL_PASSWORD="'.config('mail.password')."\"\n".
                    'MAIL_ENCRYPTION='.$request->mail_encryption."\n\n".
                    'MAIL_FROM_ADDRESS='.$request->from_mail."\n".
                    'MAIL_FROM_NAME="'.$request->from_name."\"\n\n";

                $extraMailData =
                    'SES_KEY='.$request->mail_ses_key."\n".
                    'SES_SECRET='.$request->mail_ses_secret."\n";

                if (env('SES_KEY') !== null && env('SES_SECRET') !== null) {
                    $extraOldMailData =
                        'SES_KEY='.config('services.ses.key')."\n".
                        'SES_SECRET='.config('services.ses.secret')."\n";
                }

                break;

            case 'mail':
                $oldMailData =
                    'MAIL_DRIVER='.config('mail.driver')."\n".
                    'MAIL_HOST='.config('mail.host')."\n".
                    'MAIL_PORT='.config('mail.port')."\n".
                    'MAIL_USERNAME='.config('mail.username')."\n".
                    'MAIL_PASSWORD="'.config('mail.password')."\"\n".
                    'MAIL_ENCRYPTION='.config('mail.encryption')."\n\n".
                    $mailFromCredential;

                $newMailData =
                    'MAIL_DRIVER='.$request->mail_driver."\n".
                    'MAIL_HOST='.config('mail.host')."\n".
                    'MAIL_PORT='.config('mail.port')."\n".
                    'MAIL_USERNAME='.config('mail.username')."\n".
                    'MAIL_PASSWORD="'.config('mail.password')."\"\n".
                    'MAIL_ENCRYPTION='.config('mail.encryption')."\n\n".
                    'MAIL_FROM_ADDRESS='.$request->from_mail."\n".
                    'MAIL_FROM_NAME="'.$request->from_name."\"\n\n";

                break;

            case 'sendmail':
                $oldMailData =
                    'MAIL_DRIVER='.config('mail.driver')."\n".
                    'MAIL_HOST='.config('mail.host')."\n".
                    'MAIL_PORT='.config('mail.port')."\n".
                    'MAIL_USERNAME='.config('mail.username')."\n".
                    'MAIL_PASSWORD="'.config('mail.password')."\"\n".
                    'MAIL_ENCRYPTION='.config('mail.encryption')."\n\n".
                    $mailFromCredential;

                $newMailData =
                    'MAIL_DRIVER='.$request->mail_driver."\n".
                    'MAIL_HOST='.config('mail.host')."\n".
                    'MAIL_PORT='.config('mail.port')."\n".
                    'MAIL_USERNAME='.config('mail.username')."\n".
                    'MAIL_PASSWORD="'.config('mail.password')."\"\n".
                    'MAIL_ENCRYPTION='.config('mail.encryption')."\n\n".
                    'MAIL_FROM_ADDRESS='.$request->from_mail."\n".
                    'MAIL_FROM_NAME="'.$request->from_name."\"\n\n";

                break;
        }

        return [
            'old_mail_data' => $oldMailData,
            'new_mail_data' => $newMailData,
            'extra_mail_data' => $extraMailData,
            'extra_old_mail_data' => $extraOldMailData,
        ];
    }

    /**
     * Save the disk content to the .env file.
     *
     * @param Request $request
     * @return array
     */
    public function saveDiskVariables(DiskEnvironmentRequest $request)
    {
        $diskData = $this->getDiskData($request);

        try {
            if (! $diskData['old_default_driver']) {
                file_put_contents($this->envPath, $diskData['default_driver'], FILE_APPEND);
            } else {
                file_put_contents($this->envPath, str_replace(
                    $diskData['old_default_driver'],
                    $diskData['default_driver'],
                    file_get_contents($this->envPath)
                ));
            }

            if (! $diskData['old_disk_data']) {
                file_put_contents($this->envPath, $diskData['new_disk_data'], FILE_APPEND);
            } else {
                file_put_contents($this->envPath, str_replace(
                    $diskData['old_disk_data'],
                    $diskData['new_disk_data'],
                    file_get_contents($this->envPath)
                ));
            }
        } catch (Exception $e) {
            return [
                'error' => 'disk_variables_save_error',
            ];
        }

        return [
            'success' => 'disk_variables_save_successfully',
        ];
    }

    private function getDiskData($request)
    {
        $oldDefaultDriver = "";
        $defaultDriver = "";
        $oldDiskData = "";
        $newDiskData = "";

        if ($request->default_driver) {
            if (env('FILESYSTEM_DRIVER') !== null) {
                $defaultDriver = "\n".'FILESYSTEM_DRIVER='.$request->default_driver."\n";

                $oldDefaultDriver =
                    "\n".'FILESYSTEM_DRIVER='.config('filesystems.default')."\n";
            } else {
                $defaultDriver =
                    "\n".'FILESYSTEM_DRIVER='.$request->default_driver."\n";
            }
        }

        switch ($request->selected_driver) {
            case 's3':
                if (env('AWS_KEY') !== null) {
                    $oldDiskData = "\n".
                        'AWS_KEY='.config('filesystems.disks.s3.key')."\n".
                        'AWS_SECRET="'.config('filesystems.disks.s3.secret')."\"\n".
                        'AWS_REGION='.config('filesystems.disks.s3.region')."\n".
                        'AWS_BUCKET='.config('filesystems.disks.s3.bucket')."\n".
                        'AWS_ROOT='.config('filesystems.disks.s3.root')."\n";
                }

                $newDiskData = "\n".
                    'AWS_KEY='.$request->aws_key."\n".
                    'AWS_SECRET="'.$request->aws_secret."\"\n".
                    'AWS_REGION='.$request->aws_region."\n".
                    'AWS_BUCKET='.$request->aws_bucket."\n".
                    'AWS_ROOT='.$request->aws_root."\n";

                break;

            case 'doSpaces':
                if (env('DO_SPACES_KEY') !== null) {
                    $oldDiskData = "\n".
                        'DO_SPACES_KEY='.config('filesystems.disks.doSpaces.key')."\n".
                        'DO_SPACES_SECRET="'.config('filesystems.disks.doSpaces.secret')."\"\n".
                        'DO_SPACES_REGION='.config('filesystems.disks.doSpaces.region')."\n".
                        'DO_SPACES_BUCKET='.config('filesystems.disks.doSpaces.bucket')."\n".
                        'DO_SPACES_ENDPOINT='.config('filesystems.disks.doSpaces.endpoint')."\n";
                    'DO_SPACES_ROOT='.config('filesystems.disks.doSpaces.root')."\n";
                }

                $newDiskData = "\n".
                    'DO_SPACES_KEY='.$request->do_spaces_key."\n".
                    'DO_SPACES_SECRET="'.$request->do_spaces_secret."\"\n".
                    'DO_SPACES_REGION='.$request->do_spaces_region."\n".
                    'DO_SPACES_BUCKET='.$request->do_spaces_bucket."\n".
                    'DO_SPACES_ENDPOINT='.$request->do_spaces_endpoint."\n";
                    'DO_SPACES_ROOT='.$request->do_spaces_root."\n\n";

                break;

            case 'dropbox':
                if (env('DROPBOX_TOKEN') !== null) {
                    $oldDiskData = "\n".
                        'DROPBOX_TOKEN='.config('filesystems.disks.dropbox.token')."\n".
                        'DROPBOX_KEY='.config('filesystems.disks.dropbox.key')."\n".
                        'DROPBOX_SECRET="'.config('filesystems.disks.dropbox.secret')."\"\n".
                        'DROPBOX_APP='.config('filesystems.disks.dropbox.app')."\n".
                        'DROPBOX_ROOT='.config('filesystems.disks.dropbox.root')."\n";
                }

                $newDiskData = "\n".
                    'DROPBOX_TOKEN='.$request->dropbox_token."\n".
                    'DROPBOX_KEY='.$request->dropbox_key."\n".
                    'DROPBOX_SECRET="'.$request->dropbox_secret."\"\n".
                    'DROPBOX_APP='.$request->dropbox_app."\n".
                    'DROPBOX_ROOT='.$request->dropbox_root."\n";

                break;
        }

        return [
            'old_disk_data' => $oldDiskData,
            'new_disk_data' => $newDiskData,
            'default_driver' => $defaultDriver,
            'old_default_driver' => $oldDefaultDriver,
        ];
    }

    /**
     * Save sanctum statful domain to the .env file.
     *
     * @param DomainEnvironmentRequest $request
     * @return array
     */
    public function saveDomainVariables(DomainEnvironmentRequest $request)
    {
        try {
            file_put_contents($this->envPath, str_replace(
                'SANCTUM_STATEFUL_DOMAINS='.env('SANCTUM_STATEFUL_DOMAINS'),
                'SANCTUM_STATEFUL_DOMAINS='.$request->app_domain,
                file_get_contents($this->envPath)
            ));

            file_put_contents($this->envPath, str_replace(
                'SESSION_DOMAIN='.config('session.domain'),
                'SESSION_DOMAIN='.explode(':', $request->app_domain)[0],
                file_get_contents($this->envPath)
            ));
        } catch (Exception $e) {
            return [
                'error' => 'domain_verification_failed'
            ];
        }

        return [
            'success' => 'domain_variable_save_successfully'
        ];
    }
}
