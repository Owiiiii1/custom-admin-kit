<?php

namespace OwlSolutions\CustomAdminKit\Support;

class PublishMapResolver
{
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

        return array_values(array_filter(
            $map,
            fn (array $entry): bool => ($entry['preset'] ?? null) === $preset
        ));
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
