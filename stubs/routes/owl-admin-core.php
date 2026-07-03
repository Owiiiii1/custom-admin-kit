<?php

use Illuminate\Support\Facades\Route;

/*
| Core kit routes (v0.1). Load via ServiceProvider when this file exists in the host app.
| Dashboard/auth/domain routes are NOT part of core — register them in the host application.
*/

Route::get('/owl-admin/health', function () {
    return response()->json([
        'status' => 'ok',
        'kit' => config('owl-admin-kit.version', '0.1.0'),
        'preset' => 'core',
    ]);
})->name('owl-admin.health');
