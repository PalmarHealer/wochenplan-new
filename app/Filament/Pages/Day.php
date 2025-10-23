<?php

namespace App\Filament\Pages;

use App\Filament\Resources\LessonResource;
use App\Filament\Resources\LessonTemplateResource;
use App\Models\Absence;
use App\Models\Color;
use App\Models\Lesson;
use App\Models\LessonTemplate;
use App\Services\LayoutService;
use App\Services\LunchService;
use App\Services\LastSeenService;
use App\Services\PdfExportService;
use Carbon\Carbon;
use Exception;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Response;

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

    public string $lunch = "";

    public float $textSize = 100.0;

    #[\Livewire\Attributes\Url(as: 'date')]
    public ?string $urlDay = null;

    public ?string $day = null;

    public bool $canCreate = false;

    public bool $canCreateTemplates = false;

    public ?string $lastSeenLoadedAt = null;

    public function mount(): void
    {
        $this->lastSeenLoadedAt = app(LastSeenService::class)->current();

        $rawDay = $this->figureOutDay();

        $this->day = $rawDay->toDateString();

        $this->lunch = app(LunchService::class)->getLunch($this->day);

        $this->canCreate = auth()->user()->can('create_lesson') || auth()->user()->can('update_lesson');

        $this->canCreateTemplates = auth()->user()->can('update_lesson::template');

        $layoutService = app(LayoutService::class);
        $layout = $layoutService->getLayoutWithModelForDate($this->day);

        $this->dayLayout = $layout['data'];
        $this->textSize = $layout['model']?->text_size ?? 100.0;

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

        $templateLessons = $filteredTemplates->mapWithKeys(function ($template) use ($rawDay) {
            $key = $template->room . '-' . $template->lesson_time;
            $array = $template->toArray();
            $array['assigned_users'] = $template->assignedUsers->pluck('display_name', 'id')->toArray();
            $user = auth()->user();
            if (($user->can('create_lesson') && $template->assignedUsers()->where('user_id', $user->id)->exists()) || $user->can('view_any_lesson')) {
                $array['url_template'] = LessonTemplateResource::getUrl('edit', ['record' => $template->id, 'date' => $this->day]);
                $array['url'] = LessonResource::getUrl('create', ['copy' => $template->id, 'date' => $this->day]);
            }
            return [$key => $array];
        });

        $lessonLessons = $rawLessons->mapWithKeys(function ($lesson) use ($rawDay) {
            $key = $lesson->room . '-' . $lesson->lesson_time;
            $array = $lesson->toArray();
            $array['assigned_users'] = $lesson->assignedUsers->pluck('display_name', 'id')->toArray();
            $user = auth()->user();
            if (($user->can('view_lesson') && $lesson->assignedUsers()->where('user_id', $user->id)->exists()) || $user->can('view_any_lesson')) {
                $array['url'] = LessonResource::getUrl('edit', ['record' => $lesson->id, 'date' => $this->day]);
            }
            return [$key => $array];
        });

        $rawAbsences = Absence::with(['user'])
            ->whereDate('start', '<=', $rawDay)
            ->whereDate('end', '>=', $rawDay)
            ->get();

        $this->absences = array_map(fn($entry) => [
            'id' => $entry['user']['id'],
            'display_name' => $entry['user']['display_name']
        ], $rawAbsences->toArray());

        $this->absences = array_values(array_unique($this->absences, SORT_REGULAR));

        usort($this->absences, fn($a, $b) => $b['id'] <=> $a['id']);

        // Extract absent user IDs for checking in blade template
        $absentUserIds = array_column($this->absences, 'id');

        if (empty($templateLessons->values()->toArray())) $merged = $lessonLessons;
        else $merged = $templateLessons->merge($lessonLessons);

        // Add absent user IDs to each lesson
        $this->lessons = $merged->map(function ($lesson) use ($absentUserIds) {
            $lesson['absent_user_ids'] = $absentUserIds;
            return $lesson;
        })->values()->toArray();

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
            'mittagessen' => $this->lunch,
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

    public function checkForUpdate(): void
    {
        $current = app(LastSeenService::class)->current();

        if ($this->lastSeenLoadedAt === null) {
            $this->lastSeenLoadedAt = $current;
            return;
        }

        try {
            if (\Carbon\Carbon::parse($current)->gt(\Carbon\Carbon::parse($this->lastSeenLoadedAt))) {
                $this->lastSeenLoadedAt = $current;
                $this->mount();
            }
        } catch (\Throwable $e) {
            $this->lastSeenLoadedAt = $current;
            $this->mount();
        }
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

    public function downloadPdf()
    {
        $pdfService = app(PdfExportService::class);
        $base64Content = $pdfService->getOrGeneratePdf($this->day);
        $binaryContent = base64_decode($base64Content);

        $date = Carbon::parse($this->day);
        $filename = $date->locale(config('app.locale'))->translatedFormat('l, d.m.Y') . '.pdf';

        return Response::streamDownload(function () use ($binaryContent) {
            echo $binaryContent;
        }, $filename, ['Content-Type' => 'application/pdf']);
    }

}

