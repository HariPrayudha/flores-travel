<?php

namespace App\Providers;

use App\Notifications\Channels\ExpoPushChannel;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\ServiceProvider;

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
        Notification::extend('expo-push', function ($app) {
            return new ExpoPushChannel();
        });
    }
}
