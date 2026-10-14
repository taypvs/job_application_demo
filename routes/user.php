<?php

use App\Http\Controllers\User\ApplicationController;
use App\Http\Controllers\User\ChatController;
use App\Http\Controllers\User\CvController;
use App\Http\Controllers\User\JobController;
use App\Http\Controllers\User\MailboxController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('/cvs/{cv}/download', [CvController::class, 'download'])->name('cvs.download');
});

Route::middleware(['portal:candidate', 'auth', 'role:candidate'])->group(function () {
    Route::get('/jobs', [JobController::class, 'index'])->name('user.jobs.index');
    Route::get('/jobs/{jobPosting}', [JobController::class, 'show'])->name('user.jobs.show');
    Route::post('/jobs/{jobPosting}/apply', [JobController::class, 'apply'])->name('user.jobs.apply');
    Route::post('/jobs/{jobPosting}/analyze', [JobController::class, 'analyze'])->name('user.jobs.analyze');

    Route::get('/cv', [CvController::class, 'index'])->name('user.cv.index');
    Route::post('/cv', [CvController::class, 'store'])->name('user.cv.store');

    Route::get('/applications', [ApplicationController::class, 'index'])->name('user.applications.index');

    Route::get('/mailbox', [MailboxController::class, 'index'])->name('user.mailbox.index');
    Route::get('/mailbox/{mailboxMessage}', [MailboxController::class, 'show'])->name('user.mailbox.show');

    Route::get('/chat', [ChatController::class, 'index'])->name('user.chat.index');
    Route::post('/chat', [ChatController::class, 'store'])->middleware('throttle:30,1')->name('user.chat.store');
});
