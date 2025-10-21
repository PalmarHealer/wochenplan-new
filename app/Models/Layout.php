<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Layout extends Model
{
    protected $fillable = [
        'name',
        'description',
        'notes',
        'layout',
        'weekdays',
        'text_size',
    ];
    protected function casts(): array
    {
        return [
            'weekdays' => 'array',
        ];
    }
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
