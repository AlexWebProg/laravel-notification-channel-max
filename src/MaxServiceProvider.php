<?php

namespace NotificationChannels\Max;

use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\ServiceProvider;

class MaxServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/max-notification.php',
            'max-notification'
        );

        $this->app->singleton(MaxApi::class, function ($app) {
            $config = $app['config']['max-notification'];
            $token = $config['token'] ?? null;

            if (empty($token)) {
                throw new \RuntimeException(
                    'MAX Bot token is not set. Please set MAX_BOT_TOKEN in your .env file.'
                );
            }

            $baseUrl = $config['base_url'] ?? 'https://platform-api2.max.ru';
            $verifySsl = $config['verify_ssl'] ?? true;
            $caCertificate = $config['ca_certificate'] ?? null;

            if ($verifySsl === false || $verifySsl === 'false') {
                // SSL verification disabled
                $sslOption = false;
            } elseif ($caCertificate) {
                // Custom CA certificate path
                $sslOption = $caCertificate;
            } else {
                // null = MaxApi will use bundled certificate
                $sslOption = null;
            }

            return new MaxApi($token, $baseUrl, $sslOption);
        });

        $this->app->singleton(MaxChannel::class, function ($app) {
            return new MaxChannel($app->make(MaxApi::class));
        });

        // Register as a named notification channel
        Notification::resolved(function (ChannelManager $service) {
            $service->extend('max', function ($app) {
                return $app->make(MaxChannel::class);
            });
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/max-notification.php' => config_path('max-notification.php'),
            ], 'max-notification-config');
        }
    }
}
