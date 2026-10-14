<?php

use App\Support\Portal;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $user = auth()->user();

    if (Portal::isSeparated() && Portal::isAdmin()) {
        if ($user && ! $user->isAdmin()) {
            return redirect()->away(Portal::baseUrl(Portal::CANDIDATE).'/jobs');
        }

        return $user
            ? redirect()->route('admin.jobs.index')
            : redirect()->route('login');
    }

    if ($user?->isAdmin() && Portal::isSeparated()) {
        return redirect()->away(Portal::baseUrl(Portal::ADMIN).'/admin/jobs');
    }

    if (! $user) {
        return redirect()->route('login');
    }

    return redirect()->route('user.jobs.index');
})->name('home');

require __DIR__.'/auth.php';
require __DIR__.'/user.php';
require __DIR__.'/admin.php';
