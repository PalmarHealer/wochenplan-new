<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Layout extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'description',
        'notes',
        'layout',
        'weekdays',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'weekdays' => 'array',
        ];
    }

    /**
     * Ensure only one layout owns a given weekday (1-5) at a time.
     * - Sanitize incoming weekdays on saving.
     * - After saving, remove overlapping weekdays from other layouts quietly.
     */
    protected static function booted(): void
    {
        static::saving(function (Layout $layout) {
            $days = $layout->weekdays;

            if (is_string($days)) {
                $decoded = json_decode($days, true);
                $days = is_array($decoded) ? $decoded : [];
            }

            if (! is_array($days)) {
                $days = [];
            }

            $days = array_values(array_unique(array_filter(array_map(function ($d) {
                $i = (int) $d;
                return ($i >= 1 && $i <= 5) ? $i : null;
            }, $days), fn ($v) => ! is_null($v))));

            $layout->weekdays = ! empty($days) ? $days : null;
        });

        static::saved(function (Layout $layout) {
            $days = $layout->weekdays;
            if (! is_array($days) || empty($days)) {
                return;
            }

            $others = self::query()
                ->where('id', '!=', $layout->id)
                ->where(function ($q) use ($days) {
                    foreach ($days as $d) {
                        $q->orWhereJsonContains('weekdays', $d);
                    }
                })
                ->get();

            foreach ($others as $other) {
                $otherDays = is_array($other->weekdays) ? $other->weekdays : [];
                $newDays = array_values(array_diff($otherDays, $days));
                $other->weekdays = ! empty($newDays) ? $newDays : null;
                $other->saveQuietly();
            }
        });
    }
}
