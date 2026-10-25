<?php

namespace App\Filament\Pages;

use App\Models\Absence;
use App\Models\Color;
use App\Services\DayLessonService;
use App\Services\LayoutService;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Carbon\Carbon;
use Filament\Pages\Page;
use Livewire\Attributes\Url;

class ShiftPlan extends Page
{
    use HasPageShield;

    protected static ?string $navigationIcon = 'tabler-calendar-user';

    protected static ?string $navigationLabel = 'Schichtplan';

    protected static ?string $slug = 'schichtplan';

    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.pages.shift-plan';

    #[Url(as: 'woche')]
    public ?string $week = null;

    /** @var array<int, array{date: string, label: string, note: ?string}> */
    public array $days = [];

    /** @var array<int, array{id: int, name: string, days: array<string, array{absent: bool, entries: array}>}> */
    public array $rows = [];

    public function getTitle(): string
    {
        return 'Schichtplan';
    }

    public function getHeading(): string
    {
        $monday = $this->monday();

        return 'KW '.$monday->isoWeek().': '.$monday->format('d.m.').' – '.$monday->copy()->addDays(4)->format('d.m.Y');
    }

    public function mount(): void
    {
        $this->load();
    }

    public function changeWeek(int $offset): void
    {
        $this->week = $this->monday()->addWeeks($offset)->format('d.m.Y');
        $this->load();
    }

    public function currentWeek(): void
    {
        $this->week = null;
        $this->load();
    }

    private function monday(): Carbon
    {
        try {
            $date = $this->week ? Carbon::createFromFormat('d.m.Y', $this->week) : Carbon::today();
        } catch (\Exception) {
            $date = Carbon::today();
        }

        // On weekends, show the upcoming week.
        if ($date->isWeekend()) {
            $date = $date->next(Carbon::MONDAY);
        }

        return $date->startOfWeek(Carbon::MONDAY)->startOfDay();
    }

    private function load(): void
    {
        $dayLessons = app(DayLessonService::class);
        $layoutService = app(LayoutService::class);
        $colors = Color::all()->pluck('color', 'id');
        $monday = $this->monday();

        $this->days = [];
        $users = [];

        for ($i = 0; $i < 5; $i++) {
            $date = $monday->copy()->addDays($i);
            $key = $date->toDateString();
            $lessons = $dayLessons->visibleLessonsForDate($date);

            $this->days[] = [
                'date' => $key,
                'label' => $date->translatedFormat('D d.m.'),
                'url' => Day::getUrl(['date' => $date->format('d.m.Y')]),
                // Name of the layout when nothing is planned, e.g. "Feiertag" or "Ferien".
                'note' => $lessons->isEmpty() ? $layoutService->getLayoutModelForDate($date)?->name : null,
            ];

            foreach ($lessons as $lesson) {
                foreach ($lesson->assignedUsers as $user) {
                    $users[$user->id]['name'] = $user->display_name ?: $user->name;
                    $users[$user->id]['days'][$key][] = [
                        'time' => $lesson->times?->name,
                        'room' => $lesson->rooms?->name,
                        'name' => strip_tags((string) $lesson->name),
                        'color' => $colors[$lesson->color] ?? null,
                        'disabled' => (bool) $lesson->disabled,
                    ];
                }
            }
        }

        $absences = Absence::query()
            ->whereIn('user_id', array_keys($users))
            ->whereDate('start', '<=', $monday->copy()->addDays(4))
            ->whereDate('end', '>=', $monday)
            ->get();

        $this->rows = collect($users)
            ->map(function ($user, $id) use ($absences) {
                $days = [];

                foreach ($this->days as $day) {
                    $entries = collect($user['days'][$day['date']] ?? [])
                        ->sortBy('time', SORT_NATURAL)
                        ->values()
                        ->all();

                    $days[$day['date']] = [
                        'absent' => $absences->contains(fn ($absence) => $absence->user_id === $id
                            && $absence->start->toDateString() <= $day['date']
                            && $absence->end->toDateString() >= $day['date']),
                        'entries' => $entries,
                    ];
                }

                return ['id' => $id, 'name' => $user['name'], 'days' => $days];
            })
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }
}
