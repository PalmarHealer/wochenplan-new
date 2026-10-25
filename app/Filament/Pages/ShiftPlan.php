<?php

namespace App\Filament\Pages;

use App\Models\Absence;
use App\Models\Color;
use App\Services\DayLessonService;
use App\Services\LayoutService;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Carbon\Carbon;
use Filament\Actions\Action;
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

    /** 'meins' or 'alle'; users without view_all_shifts always get 'meins'. */
    #[Url(as: 'ansicht')]
    public ?string $scope = null;

    /** @var array<int, array{date: string, label: string, note: ?string}> */
    public array $days = [];

    /** @var array<int, array{id: int, name: string, days: array<string, array{absent: bool, entries: array}>}> */
    public array $rows = [];

    public function getTitle(): string
    {
        return $this->showsAll() ? 'Schichtplan' : 'Mein Schichtplan';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('help')
                ->label('Hilfe')
                ->icon('tabler-help')
                ->color('gray')
                ->url(FAQ::getUrl().'#shift-plan')
                ->visible(fn () => FAQ::canAccess()),
        ];
    }

    public function canSeeAll(): bool
    {
        return (bool) auth()->user()?->can('view_all_shifts');
    }

    public function showsAll(): bool
    {
        return $this->canSeeAll() && $this->scope !== 'meins';
    }

    public function setScope(string $scope): void
    {
        $this->scope = $scope === 'meins' ? 'meins' : null;
        $this->load();
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
            $ranges = $dayLessons->timeRangesForDate($date);

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
                    $range = $ranges[(int) $lesson->lesson_time] ?? ['start' => null, 'end' => null];
                    $users[$user->id]['days'][$key][] = [
                        'time' => $lesson->times?->name,
                        'start' => $range['start'],
                        'end' => $range['end'],
                        'room' => $lesson->rooms?->name,
                        'name' => strip_tags((string) $lesson->name),
                        'color' => $colors[$lesson->color] ?? null,
                        'disabled' => (bool) $lesson->disabled,
                    ];
                }
            }
        }

        if (! $this->showsAll()) {
            $users = array_intersect_key($users, [auth()->id() => true]);
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
                        ->sortBy([['start', 'asc'], ['time', 'asc']])
                        ->values()
                        ->all();

                    $absent = $absences->contains(fn ($absence) => $absence->user_id === $id
                        && $absence->start->toDateString() <= $day['date']
                        && $absence->end->toDateString() >= $day['date']);

                    $days[$day['date']] = [
                        'absent' => $absent,
                        'entries' => $entries,
                        ...self::presence($absent ? [] : $entries),
                    ];
                }

                return [
                    'id' => $id,
                    'name' => $user['name'],
                    'days' => $days,
                    'minutes' => array_sum(array_column($days, 'minutes')),
                ];
            })
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }

    /**
     * When someone is there on a day (first start to last end) and how long they work:
     * the covered time of all lessons that take place, overlaps counted once.
     *
     * @return array{from: ?int, until: ?int, minutes: int}
     */
    public static function presence(array $entries): array
    {
        $intervals = collect($entries)
            ->reject(fn ($entry) => $entry['disabled'] || $entry['start'] === null)
            ->map(fn ($entry) => [$entry['start'], max($entry['end'] ?? $entry['start'], $entry['start'])])
            ->sortBy(fn ($interval) => $interval[0])
            ->values();

        if ($intervals->isEmpty()) {
            return ['from' => null, 'until' => null, 'minutes' => 0];
        }

        $minutes = 0;
        $coveredUntil = null;

        foreach ($intervals as [$start, $end]) {
            $minutes += max(0, $end - max($start, $coveredUntil ?? $start));
            $coveredUntil = max($coveredUntil ?? $end, $end);
        }

        return ['from' => $intervals->min(0), 'until' => $intervals->max(1), 'minutes' => $minutes];
    }
}
