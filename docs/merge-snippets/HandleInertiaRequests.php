<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'owlAdmin' => fn () => config('owl-admin.branding', [
                'brand_name' => config('owl-admin.brand_name', config('owl-admin.name', 'Service Admin')),
                'logo_path' => config('owl-admin.logo_path', '/images/company-logo.svg'),
                'ai' => (function (): array {
                    $fallback = [
                        'connected' => false,
                        'provider' => null,
                        'provider_label' => null,
                        'model' => null,
                        'status_label' => 'AI: not connected',
                    ];

                    try {
                        if (! class_exists(\App\Models\AiProviderSetting::class)) {
                            return $fallback;
                        }

                        if (! \Illuminate\Support\Facades\Schema::hasTable('ai_provider_settings')) {
                            return $fallback;
                        }

                        $active = \App\Models\AiProviderSetting::query()
                            ->where('is_active', true)
                            ->where('is_connected', true)
                            ->first();

                        if ($active === null) {
                            return $fallback;
                        }

                        $providerLabel = $active->label ?: ucfirst((string) $active->provider);

                        return [
                            'connected' => true,
                            'provider' => $active->provider,
                            'provider_label' => $providerLabel,
                            'model' => $active->active_model,
                            'status_label' => sprintf(
                                'AI: connected — %s / %s',
                                $providerLabel,
                                $active->active_model ?? 'unknown'
                            ),
                        ];
                    } catch (\Throwable) {
                        return $fallback;
                    }
                })(),
            ]),
        ]);
    }
}
