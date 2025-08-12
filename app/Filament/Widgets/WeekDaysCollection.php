<?php

namespace App\Filament\Widgets;

use App\Services\LunchService;
use Filament\Widgets\Widget;
use Carbon\Carbon;

class WeekDaysCollection extends Widget
{
    protected static bool $isLazy = false;

    protected static string $view = 'filament.widgets.weekDaysCollection';

    protected int | string | array $columnSpan = 'full';

    protected function getViewData(): array
    {
        $baseDate = Carbon::now();

        if ($baseDate->isWeekend()) {
            $baseDate = $baseDate->next(1);
        }

        $startOfWeek = $baseDate->startOfWeek(1);

        $days = [];
        $dayNames = ['Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag'];

        for ($i = 0; $i < 5; $i++) {
            $currentDay = $startOfWeek->copy()->addDays($i);

            if ($currentDay->isSameDay(Carbon::now())) {
                $url = route('filament.admin.pages.day');
            } else {
                $url = route('filament.admin.pages.day', ['date' => $currentDay->format('d.m.Y')]);
            }

            $days[] = [
                'label' => $dayNames[$i],
                'date' => $currentDay->format('d.m.'),
                'lunch' => app(LunchService::class)->getLunch($currentDay),
                'url' => $url,
                'isToday' => $currentDay->isSameDay(Carbon::now())
            ];
        }

        return [
            'days' => $days,
        ];

    }
}
