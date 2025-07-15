<?php

namespace App\Providers;

use Laravel\Passport\Passport;
use Illuminate\Support\ServiceProvider;


use Illuminate\Support\Facades\Storage;
use League\Flysystem\Filesystem;
use League\Flysystem\AzureBlobStorage\AzureBlobStorageAdapter;
use MicrosoftAzure\Storage\Blob\BlobRestProxy;
use Illuminate\Filesystem\FilesystemAdapter;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Passport::hashClientSecrets();
        Passport::enablePasswordGrant();
        Passport::tokensExpireIn(now()->addHours(1));
        Passport::refreshTokensExpireIn(now()->addDays(1));

        $this->defineAzureConstants();

        Storage::extend('azure', function ($app, $config) {
            $connectionString = sprintf(
                'DefaultEndpointsProtocol=https;AccountName=%s;AccountKey=%s;EndpointSuffix=core.windows.net',
                $config['name'],
                $config['key']
            );

            $client = BlobRestProxy::createBlobService($connectionString);
            $adapter = new AzureBlobStorageAdapter($client, $config['container']);

            return new FilesystemAdapter(
                new Filesystem($adapter),
                $adapter,
                $config
            );
        });
    }

    private function defineAzureConstants()
    {
        // Define missing CURL constants for PHP 8.4 compatibility
        $constants = [
            'CURLOPT_SSLVERSION' => 32,
            'CURL_SSLVERSION_TLSv1_2' => 6,
            'CURLOPT_SSL_VERIFYHOST' => 81,
            'CURLOPT_SSL_VERIFYPEER' => 64,
            'CURLOPT_CAINFO' => 10246,
            'CURLOPT_TIMEOUT' => 13,
            'CURLOPT_CONNECTTIMEOUT' => 78,
        ];

        foreach ($constants as $constant => $value) {
            // Define global constant if not exists
            if (!defined($constant)) {
                define($constant, $value);
            }

            // Define namespaced constant for Azure SDK
            $namespacedConstant = 'MicrosoftAzure\\Storage\\Common\\Internal\\' . $constant;
            if (!defined($namespacedConstant)) {
                define($namespacedConstant, $value);
            }
        }
    }
}
