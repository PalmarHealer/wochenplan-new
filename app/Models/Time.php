<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Time extends Model
{
    protected $fillable = [
        'name',
        'starts_at',
        'ends_at',
    ];

    /**
     * Start in minutes after midnight: the explicit start, otherwise a leading "H:MM" in the name.
     */
    public function startMinutes(): ?int
    {
        return self::toMinutes($this->starts_at)
            ?? self::toMinutes(trim(html_entity_decode(strip_tags((string) $this->name))));
    }

    public function endMinutes(): ?int
    {
        return self::toMinutes($this->ends_at);
    }

    public static function toMinutes(?string $value): ?int
    {
        if ($value === null || ! preg_match('/^(\d{1,2})[:.](\d{2})/', $value, $match)) {
            return null;
        }

        [$hours, $minutes] = [(int) $match[1], (int) $match[2]];

        return $hours < 24 && $minutes < 60 ? $hours * 60 + $minutes : null;
    }

    public static function formatMinutes(int $minutes): string
    {
        return intdiv($minutes, 60).':'.str_pad((string) ($minutes % 60), 2, '0', STR_PAD_LEFT);
    }
}
