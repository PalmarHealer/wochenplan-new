<?php

namespace App\Filament\Pages;

use App\Filament\Resources\LessonResource;
use App\Models\Color;
use App\Models\Layout;
use App\Models\Lesson;
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
        $this->day = $this->figureOutDay()->toDateString();

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

        $this->lessons = $rawLessons->map(function ($lesson) {
            $lessonArray = $lesson->toArray();
            $lessonArray['assigned_users'] = $lesson->assignedUsers->pluck('name', 'id')->toArray();
            $user = auth()->user();
            if (($user->can('view_lesson') && $lesson->assignedUsers()->where('user_id', $user->id)->exists()) || $user->can('view_any_lesson')) {
                $lessonArray['url'] = LessonResource::getUrl('edit', ['record' => $lesson->id]);
            }
            return $lessonArray;
        })->toArray();

    }
    public function replacePlaceholders(string $text): string
    {
        $dayName = $this->figureOutDay()->translatedFormat('D');
        $dayFull = $this->figureOutDay()->translatedFormat('d.m.Y');

        $context = [
            'mittagessen' => "Mittagessen placeholder found",
            'abwesenheit' => "Abwesenheits placeholder found",
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

