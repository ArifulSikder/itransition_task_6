<?php

namespace App\Actions;

use App\Models\Participant;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ResolveDisplayName
{
    public function handle(string $requestedName): string
    {
        $base = Str::of($requestedName)->squish()->toString();

        return Cache::lock('participant-name:'.Str::lower($base), 5)->block(5, function () use ($base) {
            $taken = Participant::query()
                ->recentlyActive()
                ->where('name', 'like', $base.'%')
                ->pluck('name')
                ->filter(fn (string $name): bool => $this->isReservedName($base, $name))
                ->values();

            if (! $taken->contains($base)) {
                return $base;
            }

            $highest = $taken
                ->map(fn (string $name): int => $this->suffixNumber($base, $name))
                ->max() ?? 1;

            return $base.' '.($highest + 1);
        });
    }

    private function isReservedName(string $base, string $name): bool
    {
        if ($name === $base) {
            return true;
        }

        return (bool) preg_match('/^'.preg_quote($base, '/').' \d+$/u', $name);
    }

    private function suffixNumber(string $base, string $name): int
    {
        if ($name === $base) {
            return 1;
        }

        if (preg_match('/^'.preg_quote($base, '/').' (\d+)$/u', $name, $matches) === 1) {
            return (int) $matches[1];
        }

        return 1;
    }
}
