<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function index(Request $request): Response
    {
        $users = User::query()
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'created_at'])
            ->map(static fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'created_at' => optional($user->created_at)->toIso8601String(),
            ])
            ->all();

        return Inertia::render('Settings/Index', [
            'users' => $users,
        ]);
    }

    public function updateLanguage(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'locale' => ['required', 'in:en,ru,uk'],
        ]);

        $request->session()->put('locale', $validated['locale']);

        return back();
    }
}
