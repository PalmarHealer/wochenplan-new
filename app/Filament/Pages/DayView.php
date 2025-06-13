<?php

namespace App\Filament\Pages;

use App\Filament\Resources\LessonResource;
use App\Models\Color;
use App\Models\Layout;
use App\Models\Lesson;
use Carbon\Carbon;
use DateTime;
use Exception;
use Filament\Pages\Page;
use function Pest\Laravel\delete;

class DayView extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.pages.day-view';

    public function getTitle(): string
    {

        Carbon::setLocale('de');
        $input = request()->query('date');
        $format = 'd.m.Y';

        try {
            $date = Carbon::createFromFormat($format, $input);

            if ($date->format($format) !== $input) {
                throw new Exception('Invalid format');
            }
        } catch (Exception) {
            $date = Carbon::now();
        }

        return $date->translatedFormat('l \d\e\r d.m.Y');
    }

    public array $dayLayout = [];

    public array $colors = [];

    public array $lessons = [];

    public array $absences = [];

    public ?string $day = null;

    public function mount(): void
    {
        $this->day = request()->query('date');

        $dt = DateTime::createFromFormat('d.m.Y', $this->day);

        if ($dt && $dt->format('d.m.Y') === $this->day) $this->day = $dt->format('Y-m-d');
        else $this->day = date('Y-m-d');

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
            $lessonArray['url'] = LessonResource::getUrl('edit', ['record' => $lesson->id]);
            return $lessonArray;
        })->toArray();


    }
}

