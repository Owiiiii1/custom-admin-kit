<?php

namespace OwlSolutions\CustomAdminKit\Support;

class PublishMapResolver
{
    private const INHERITED_PRESETS = [
        'admin' => ['core'],
    ];

    /**
     * @return list<array<string, mixed>>
     */
    public function entries(?string $preset = null): array
    {
        /** @var list<array<string, mixed>> $map */
        $map = config('owl-admin-kit.publish_map', []);

        if ($preset === null) {
            return $map;
        }

        $allowed = array_merge(self::INHERITED_PRESETS[$preset] ?? [], [$preset]);
        $priorities = array_flip($allowed);

        $filtered = array_values(array_filter(
            $map,
            static fn (array $entry): bool => in_array(($entry['preset'] ?? null), $allowed, true)
        ));

        usort(
            $filtered,
            static function (array $left, array $right) use ($priorities): int {
                $leftPreset = (string) ($left['preset'] ?? '');
                $rightPreset = (string) ($right['preset'] ?? '');

                return ($priorities[$leftPreset] ?? 0) <=> ($priorities[$rightPreset] ?? 0);
            }
        );

        $deduped = [];
        foreach ($filtered as $entry) {
            $target = (string) ($entry['target'] ?? '');
            $dedupeKey = $target !== '' ? $target : (string) ($entry['stub'] ?? '');
            $deduped[$dedupeKey] = $entry;
        }

        return array_values($deduped);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function copyEntriesForPreset(string $preset): array
    {
        return array_values(array_filter(
            $this->entries($preset),
            fn (array $entry): bool => ($entry['mode'] ?? '') === 'copy'
                && ! empty($entry['stub'])
                && ! empty($entry['target'])
        ));
    }

    /**
     * @return array<string, string>
     */
    public function copyMapForPreset(string $preset): array
    {
        $map = [];

        foreach ($this->copyEntriesForPreset($preset) as $entry) {
            $map[(string) $entry['stub']] = (string) $entry['target'];
        }

        return $map;
    }

    public function isPresetAvailable(string $preset): bool
    {
        return in_array($preset, config('owl-admin-kit.presets', ['core']), true);
    }

    public function unavailablePresetMessage(string $preset): ?string
    {
        $messages = config('owl-admin-kit.unavailable_presets', []);

        return $messages[$preset] ?? null;
    }
}
