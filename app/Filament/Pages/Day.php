<?php

namespace App\Filament\Pages;

use App\Filament\Resources\LessonResource;
use App\Models\Absence;
use App\Models\Color;
use App\Models\Layout;
use App\Models\Lesson;
use App\Models\LessonTemplate;
use App\Services\LunchService;
use Carbon\Carbon;
use Exception;
use Filament\Pages\Page;

class Day extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.pages.day';

    public function getHeading(): string {
        return $this->figureOutDay()->translatedFormat('l \d\e\r d.m.Y');
    }

    public function getTitle(): string
    {
        return "Tagesansicht";
    }

    public array $dayLayout = [];

    public array $colors = [];

    public array $lessons = [];

    public array $absences = [];

    #[\Livewire\Attributes\Url(as: 'date')]
    public ?string $urlDay = null;

    public ?string $day = null;

    public bool $canCreate = false;

    public function mount(): void
    {
        $rawDay = $this->figureOutDay();

        $this->day = $rawDay->toDateString();

        $this->canCreate = auth()->user()->can('view_lesson') || auth()->user()->can('view_any_lesson');

        $layout = Layout::where('active', true)
            ->limit(1)
            ->pluck('layout')
            ->first();

        $this->dayLayout = json_decode($layout, true);

        $this->colors = Color::all()->pluck('color', 'id')->toArray();

        $rawLessons = Lesson::with(['assignedUsers'])
            ->whereDate('date', $this->day)
            ->get();

        $parentIds = $rawLessons->pluck('parent_id')->filter()->unique();

        $rawLessonTemplates = LessonTemplate::with(['assignedUsers'])
            ->where('weekday', $rawDay->format('N'))
            ->where('created_at', '<=', $rawDay)
            ->where(function ($query) use ($rawDay) {
                $query->whereNull('deleted_at')
                    ->orWhere('deleted_at', '>=', $rawDay);
            })
            ->get();

        $filteredTemplates = $rawLessonTemplates->reject(function ($template) use ($parentIds) {
            return $parentIds->contains($template->id);
        });

        $templateLessons = $filteredTemplates->mapWithKeys(function ($template) {
            $key = $template->room . '-' . $template->lesson_time;
            $array = $template->toArray();
            $array['assigned_users'] = $template->assignedUsers->pluck('display_name', 'id')->toArray();;
            $user = auth()->user();
            if (($user->can('create_lesson') && $template->assignedUsers()->where('user_id', $user->id)->exists()) || $user->can('view_any_lesson')) {
                $array['url'] = LessonResource::getUrl('create', ['copy' => $template->id, 'date' => $this->day]);
            }
            return [$key => $array];
        });

        $lessonLessons = $rawLessons->mapWithKeys(function ($lesson) {
            $key = $lesson->room . '-' . $lesson->lesson_time;
            $array = $lesson->toArray();
            $array['assigned_users'] = $lesson->assignedUsers->pluck('display_name', 'id')->toArray();
            $user = auth()->user();
            if (($user->can('view_lesson') && $lesson->assignedUsers()->where('user_id', $user->id)->exists()) || $user->can('view_any_lesson')) {
                $array['url'] = LessonResource::getUrl('edit', ['record' => $lesson->id, 'date' => $this->day]);
            }
            return [$key => $array];
        });

        if (empty($templateLessons->values()->toArray())) $merged = $lessonLessons;
        else $merged = $templateLessons->merge($lessonLessons);

        $this->lessons = $merged->values()->toArray();

        $rawAbsences = Absence::with(['user'])
            ->where('start', '<=', $rawDay->format('d.m.Y'))
            ->where('end', '>=', $rawDay->format('d.m.Y'))
            ->get();

        $this->absences = array_map(fn($entry) => [
            'id' => $entry['user']['id'],
            'display_name' => $entry['user']['display_name']
        ], $rawAbsences->toArray());

        $this->absences = array_values(array_unique($this->absences, SORT_REGULAR));

        usort($this->absences, fn($a, $b) => $b['id'] <=> $a['id']);

        if (!env('DAY_VIEW_DISPLAY_ALL_ABSENCE_NOTES')) {
            $allUserIds = [];
            foreach ($this->lessons as $entry) {
                if (!empty($entry['assigned_users'])) {
                    foreach ($entry['assigned_users'] as $userID => $userName) {
                        $allUserIds[] = $userID;
                    }
                }
            }
            $this->absences = array_filter($this->absences, function($absence) use ($allUserIds) {
                return in_array($absence['id'], $allUserIds);
            });

        }
    }
    public function replacePlaceholders(string $text): string
    {
        $dayName = $this->figureOutDay()->translatedFormat('D');
        $dayFull = $this->figureOutDay()->translatedFormat('d.m.Y');

        $absences = "";

        foreach ($this->absences as $key => $absence) {
            $absences .= $absence['display_name'] . ($key === array_key_last($this->absences) ? '' : ', ');
        }

        $context = [
            'mittagessen' => app(LunchService::class)->getLunch($this->day),
            'abwesenheit' => $absences,
            'tag' => str_replace('.', '', $dayName) . " " . $dayFull,
        ];

        return preg_replace_callback('/%([a-zA-Z0-9_]+)%/', function ($matches) use ($context) {
            return $context[$matches[1]] ?? $matches[0];
        }, $text);
    }

    public function changeDay(int $offset): void
    {
        $current = $this->figureOutDay();
        $newDate = $current->copy()->addDays($offset);

        while ($newDate->isSaturday() || $newDate->isSunday()) {
            $newDate->addDays($offset > 0 ? 1 : -1);
        }

        $this->urlDay = $newDate->format('d.m.Y');

        $this->mount();
    }

    private function figureOutDay(): ?Carbon
    {
        Carbon::setLocale('de');

        $input = $this->urlDay;
        $format = 'd.m.Y';

        try {
            $date = Carbon::createFromFormat($format, $input);
            if ($date->format($format) !== $input) {
                throw new Exception('Invalid format');
            }
        } catch (Exception) {
            try {
                $date = Carbon::createFromFormat('Y-m-d', $input);
            } catch (Exception) {
                $date = Carbon::now();
            }
        }

        return $date;
    }

}

