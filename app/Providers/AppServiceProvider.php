<?php

namespace App\Providers;

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

    public function boot(): void
    {
        view()->composer('layouts.navbar.index', function ($view) {
            if (auth()->check()) {
                $unreadNotifications = \App\Models\Notification::where('user_id', auth()->id())
                    ->whereNull('read_at')
                    ->latest()
                    ->limit(10)
                    ->get();
                $unreadCount = \App\Models\Notification::where('user_id', auth()->id())
                    ->whereNull('read_at')
                    ->count();
                $soundEnabled = auth()->user()->notification_settings['sound_enabled'] ?? true;

                $view->with(compact('unreadNotifications', 'unreadCount', 'soundEnabled'));
            }
        });
    }
}
