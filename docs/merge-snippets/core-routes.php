<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use OwlSolutions\CustomAdminKit\Support\AdminRouteMiddleware;

/*
| Core admin routes — merge into host routes/web.php (v0.1).
| Middleware: web + auth; verified only when OWL_ADMIN_EMAIL_VERIFICATION=true.
*/

Route::middleware(AdminRouteMiddleware::stack())->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    Route::get('/statistics/logs', function () {
        return Inertia::render('Statistics/Logs');
    })->name('statistics.logs');

    Route::get('/settings', function () {
        return Inertia::render('Settings/Index');
    })->name('settings.index');

    Route::get('/app-settings', function () {
        return Inertia::render('AppSettings/Index');
    })->name('app-settings.index');
});
