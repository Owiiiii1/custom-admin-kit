<?php

use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\CalendarController;
use App\Http\Controllers\Admin\JobController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Admin\StaffMemberController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicBookingController;
use App\Http\Controllers\PublicLocaleController;
use App\Http\Controllers\Settings\UserController as SettingsUserController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use OwlSolutions\CustomAdminKit\Support\AdminRouteMiddleware;

Route::get('/booking', [PublicBookingController::class, 'create'])->name('booking.create');
Route::post('/booking', [PublicBookingController::class, 'store'])->name('booking.store');
Route::get('/booking/thank-you', [PublicBookingController::class, 'thankYou'])->name('booking.thank-you');
Route::get('/booking/locale/{locale}', [PublicLocaleController::class, 'switch'])
    ->name('booking.locale.switch')
    ->whereIn('locale', ['en', 'ru', 'uk']);
Route::post('/booking/locale', [PublicLocaleController::class, 'update'])->name('booking.locale.update');

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(AdminRouteMiddleware::stack())->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::get('/customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');

    Route::get('/orders', [JobController::class, 'index'])->name('orders.index');
    Route::post('/orders', [JobController::class, 'store'])->name('orders.store');
    Route::post('/orders/{job}/accept', [JobController::class, 'accept'])->name('orders.accept');
    Route::post('/orders/{job}/cancel', [JobController::class, 'cancel'])->name('orders.cancel');
    Route::post('/orders/{job}/assign-staff', [JobController::class, 'assign'])->name('orders.assign-staff');
    Route::post('/orders/{job}/in-progress', [JobController::class, 'markInProgress'])->name('orders.in-progress');
    Route::post('/orders/{job}/complete', [JobController::class, 'complete'])->name('orders.complete');

    Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar.index');

    Route::get('/staff', [StaffMemberController::class, 'index'])->name('staff.index');
    Route::get('/staff/create', [StaffMemberController::class, 'create'])->name('staff.create');
    Route::post('/staff', [StaffMemberController::class, 'store'])->name('staff.store');
    Route::get('/staff/{staffMember}/edit', [StaffMemberController::class, 'edit'])->name('staff.edit');
    Route::patch('/staff/{staffMember}', [StaffMemberController::class, 'update'])->name('staff.update');
    Route::delete('/staff/{staffMember}', [StaffMemberController::class, 'destroy'])->name('staff.destroy');

    Route::get('/services', [ServiceController::class, 'index'])->name('services.index');
    Route::get('/services/create', [ServiceController::class, 'create'])->name('services.create');
    Route::post('/services', [ServiceController::class, 'store'])->name('services.store');
    Route::get('/services/{service}/edit', [ServiceController::class, 'edit'])->name('services.edit');
    Route::patch('/services/{service}', [ServiceController::class, 'update'])->name('services.update');
    Route::delete('/services/{service}', [ServiceController::class, 'destroy'])->name('services.destroy');

    Route::get('/statistics/logs', function () {
        return Inertia::render('Statistics/Logs');
    })->name('statistics.logs');

    Route::get('/settings', function (Request $request) {
        $users = [];

        if ($request->user()?->access_full) {
            $users = User::query()
                ->orderBy('name')
                ->get([
                    'id',
                    'name',
                    'email',
                    'phone',
                    'access_full',
                    'access_billing',
                    'access_staff',
                    'created_at',
                ])
                ->map(fn (User $user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'access_full' => (bool) $user->access_full,
                    'access_billing' => (bool) $user->access_billing,
                    'access_staff' => (bool) $user->access_staff,
                    'created_at' => optional($user->created_at)->toIso8601String(),
                ])
                ->all();
        }

        return Inertia::render('Settings/Index', [
            'users' => $users,
        ]);
    })->name('settings.index');

    Route::middleware('full_access')->group(function () {
        Route::post('/settings/users', [SettingsUserController::class, 'store'])
            ->name('settings.users.store');
        Route::patch('/settings/users/{user}', [SettingsUserController::class, 'update'])
            ->name('settings.users.update');
        Route::delete('/settings/users/{user}', [SettingsUserController::class, 'destroy'])
            ->name('settings.users.destroy');

        Route::get('/app-settings', function () {
            return Inertia::render('AppSettings/Index');
        })->name('app-settings.index');
    });

    Route::post('/settings/language', function (Request $request) {
        $validated = $request->validate([
            'locale' => ['required', 'in:en,ru,uk'],
        ]);

        $request->user()->forceFill([
            'locale' => $validated['locale'],
        ])->save();

        $request->session()->put('locale', $validated['locale']);

        return back();
    })->name('settings.language.update');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('/profile/update', [ProfileController::class, 'update'])->name('profile.update.post');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
