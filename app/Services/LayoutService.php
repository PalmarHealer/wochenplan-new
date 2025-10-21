<?php

namespace App\Services;

use App\Models\Layout;
use App\Models\LayoutDeviation;
use Carbon\Carbon;

class LayoutService
{
    /**
     * Return both the layout array and the Layout model for a given date in a single query.
     * Returns ['data' => array, 'model' => Layout|null]
     */
    public function getLayoutWithModelForDate(string|Carbon $date): array
    {
        $carbonDate = $date instanceof Carbon ? $date : Carbon::parse($date);

        // 1) Check for a LayoutDeviation covering this date; newest wins on overlaps
        $deviation = LayoutDeviation::query()
            ->whereDate('start', '<=', $carbonDate)
            ->whereDate('end', '>=', $carbonDate)
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->with('layout')
            ->first();

        if ($deviation && $deviation->layout) {
            $layoutData = $deviation->layout->layout;
            $decoded = is_array($layoutData) ? $layoutData : json_decode($layoutData, true);

            return [
                'data' => is_array($decoded) ? $decoded : [],
                'model' => $deviation->layout,
            ];
        }

        // 2) Fall back to weekday-based layout
        $weekday = (int) $carbonDate->format('N');

        if ($weekday < 1 || $weekday > 5) {
            return ['data' => [], 'model' => null];
        }

        $layoutModel = Layout::query()
            ->whereJsonContains('weekdays', $weekday)
            ->orderByDesc('updated_at')
            ->first();

        if (!$layoutModel) {
            return ['data' => [], 'model' => null];
        }

        $decoded = is_array($layoutModel->layout)
            ? $layoutModel->layout
            : json_decode($layoutModel->layout, true);

        return [
            'data' => is_array($decoded) ? $decoded : [],
            'model' => $layoutModel,
        ];
    }

    /**
     * Return the layout array for a given date (Y-m-d|string|Carbon) by resolving its weekday.
     * ISO-8601 weekday: 1 = Monday ... 7 = Sunday. We only consider 1-5.
     */
    public function getLayoutForDate(string|Carbon $date): array
    {
        return $this->getLayoutWithModelForDate($date)['data'];
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

    /**
     * Get the Layout model instance for a given date, checking deviations first.
     */
    public function getLayoutModelForDate(string|Carbon $date): ?Layout
    {
        return $this->getLayoutWithModelForDate($date)['model'];
    }
}
