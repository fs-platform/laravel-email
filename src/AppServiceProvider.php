<?php

namespace Smbear\WebPowerEmail;

use Illuminate\Support\ServiceProvider;
use Smbear\WebPowerEmail\Services\EmailOperationService;
use Smbear\WebPowerEmail\Services\WebPowerEmailService;

class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('web-power-email',function (){
           return new WebPowerEmailService();
        });

        $this->app->bind('web-power-operation-email', function() {
            return new EmailOperationService();
        });

        $this->mergeConfigFrom(__DIR__.'/../config/config.php','webpower');
    }

    public function boot()
    {
        if ($this->app->runningInConsole()) {

            $this->publishes([
                __DIR__.'/../config/config.php' => config_path('webpower.php'),
            ]);
        }
    }
}