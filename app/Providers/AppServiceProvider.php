<?php

namespace App\Providers;

use App\Services\Mailbox\MailboxService;
use Carbon\Carbon;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Carbon::setLocale(config('app.locale'));

        View::composer('layouts.user', function ($view): void {
            $user = auth()->user();
            $view->with(
                'unreadMailboxCount',
                $user ? app(MailboxService::class)->unreadCount($user) : 0,
            );
        });
    }
}
