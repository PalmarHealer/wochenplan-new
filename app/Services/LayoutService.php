<?php

namespace App\Services;

use App\Models\Layout;
use App\Models\LayoutDeviation;
use Carbon\Carbon;

class LayoutService
{
    /**
     * Return the layout array for a given date (Y-m-d|string|Carbon) by resolving its weekday.
     * ISO-8601 weekday: 1 = Monday ... 7 = Sunday. We only consider 1-5.
     */
    public function getLayoutForDate(string|Carbon $date): array
    {
        $carbon = $date instanceof Carbon ? $date : Carbon::parse($date);

        // 1) Check for a LayoutDeviation covering this date; newest wins on overlaps
        $deviation = LayoutDeviation::query()
            ->where('start', '<=', $carbon->format('d.m.Y'))
            ->where('end', '>=', $carbon->format('d.m.Y'))
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->with('layout')
            ->first();

        if ($deviation && $deviation->layout && ! empty($deviation->layout->layout)) {
            $decoded = is_array($deviation->layout->layout)
                ? $deviation->layout->layout
                : json_decode($deviation->layout->layout, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        // 2) Fall back to weekday-based layout
        $weekday = (int) $carbon->format('N');
        return $this->getLayoutByWeekday($weekday);
    }

    /**
     * Return the layout array for a given ISO-8601 weekday (1 = Monday ... 7 = Sunday).
     * If multiple layouts are configured for the same weekday, prefer the most recently updated.
     */
    public function getLayoutByWeekday(int $weekday): array
    {
        if ($weekday < 1 || $weekday > 7) {
            return [];
        }

        // Only Monday-Friday are relevant per requirements; others return empty.
        if ($weekday > 5) {
            return [];
        }

        $layout = Layout::query()
            ->whereJsonContains('weekdays', $weekday)
            ->orderByDesc('updated_at')
            ->value('layout');

        if (empty($layout)) {
            return [];
        }

        $decoded = is_array($layout) ? $layout : json_decode($layout, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Get the Layout model instance for a given weekday, if needed by callers.
     */
    public function getLayoutModelByWeekday(int $weekday): ?Layout
    {
        if ($weekday < 1 || $weekday > 5) {
            return null;
        }

        return Layout::query()
            ->whereJsonContains('weekdays', $weekday)
            ->orderByDesc('updated_at')
            ->first();
    }
}
