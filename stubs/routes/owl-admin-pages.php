<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use OwlSolutions\CustomAdminKit\Support\AdminRouteMiddleware;

/*
| Core admin pages (v0.2). Loaded from routes/web.php via:
| require __DIR__.'/owl-admin-pages.php';
*/

Route::middleware(AdminRouteMiddleware::stack())->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    Route::get('/settings', function () {
        return Inertia::render('Settings/Index');
    })->name('settings.index');

    Route::get('/app-settings', function () {
        return Inertia::render('AppSettings/Index');
    })->name('app-settings.index');

    Route::get('/statistics/logs', function () {
        return Inertia::render('Statistics/Logs');
    })->name('statistics.logs');
});
