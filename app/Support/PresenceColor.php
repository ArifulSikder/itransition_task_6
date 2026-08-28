<?php

namespace App\Support;

use Illuminate\Support\Collection;

class PresenceColor
{
    /**
     * @var list<string>
     */
    public const PALETTE = [
        '#22d3ee',
        '#a78bfa',
        '#f472b6',
        '#34d399',
        '#fbbf24',
        '#fb7185',
        '#60a5fa',
        '#c084fc',
        '#2dd4bf',
        '#f97316',
    ];

    /**
     * @param  Collection<int, string>  $used
     */
    public function next(Collection $used): string
    {
        foreach (self::PALETTE as $color) {
            if (! $used->contains($color)) {
                return $color;
            }
        }

        return self::PALETTE[$used->count() % count(self::PALETTE)];
    }
}
