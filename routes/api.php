<?php

use App\Http\Controllers\Api\Admin\OrganizationSyncController;
use App\Http\Controllers\MailIntakeController;
use App\Http\Middleware\VerifyAdminSyncApiKey;
use Illuminate\Support\Facades\Route;

Route::post('posta/{token}', [MailIntakeController::class, 'receive'])
    ->where('token', '[A-Za-z0-9]{20,64}')
    ->middleware('throttle:30,1')
    ->name('api.mail.intake');

Route::middleware([VerifyAdminSyncApiKey::class, 'throttle:admin-sync'])
    ->prefix('admin')
    ->name('api.admin.')
    ->group(function () {
        Route::get('organizations', [OrganizationSyncController::class, 'index'])
            ->name('organizations.index');

        Route::patch('organizations/{organization}', [OrganizationSyncController::class, 'update'])
            ->name('organizations.update');
    });
