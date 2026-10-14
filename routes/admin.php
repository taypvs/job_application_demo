<?php

use App\Http\Controllers\Admin\ApplicationController;
use App\Http\Controllers\Admin\DocumentController;
use App\Http\Controllers\Admin\InterviewController;
use App\Http\Controllers\Admin\JobController;
use Illuminate\Support\Facades\Route;

Route::middleware(['portal:admin', 'auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/jobs', [JobController::class, 'index'])->name('jobs.index');
    Route::get('/jobs/create', [JobController::class, 'create'])->name('jobs.create');
    Route::post('/jobs', [JobController::class, 'store'])->name('jobs.store');
    Route::get('/jobs/{jobPosting}/edit', [JobController::class, 'edit'])->name('jobs.edit');
    Route::put('/jobs/{jobPosting}', [JobController::class, 'update'])->name('jobs.update');

    Route::get('/applications', [ApplicationController::class, 'index'])->name('applications.index');
    Route::get('/applications/{application}', [ApplicationController::class, 'show'])->name('applications.show');
    Route::post('/applications/{application}/schedule', [ApplicationController::class, 'schedule'])->name('applications.schedule');
    Route::post('/applications/{application}/decision', [ApplicationController::class, 'decide'])->name('applications.decide');

    Route::get('/interviews', [InterviewController::class, 'index'])->name('interviews.index');

    Route::get('/documents', [DocumentController::class, 'index'])->name('documents.index');
    Route::post('/documents', [DocumentController::class, 'store'])->name('documents.store');
    Route::delete('/documents/{knowledgeDocument}', [DocumentController::class, 'destroy'])->name('documents.destroy');
});
