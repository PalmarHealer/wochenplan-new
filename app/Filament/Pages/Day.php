<?php

namespace App\Filament\Pages;

use App\Filament\Resources\LessonResource;
use App\Filament\Resources\LessonTemplateResource;
use App\Forms\Components\CustomRichEditor;
use App\Models\Absence;
use App\Models\Color;
use App\Models\Lesson;
use App\Models\LessonTemplate;
use App\Models\User;
use App\Services\DayLessonService;
use App\Services\LastSeenService;
use App\Services\LayoutService;
use App\Services\LunchService;
use App\Services\PdfExportService;
use Carbon\Carbon;
use Exception;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Response;
use Livewire\Attributes\Url;

class Day extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.pages.day';

    public function getHeading(): string
    {
        return $this->figureOutDay()->translatedFormat('l \d\e\r d.m.Y');
    }

    public function getTitle(): string
    {
        return 'Tagesansicht';
    }

    public array $dayLayout = [];

    public array $colors = [];

    public array $lessons = [];

    public array $absences = [];

    public string $lunch = '';

    public float $textSize = 100.0;

    #[Url(as: 'date')]
    public ?string $urlDay = null;

    public ?string $day = null;

    public bool $canCreateLesson = false;

    public bool $canCreateRecurring = false;

    public bool $canCreateTemplates = false;

    public ?string $lastSeenLoadedAt = null;

    public function mount(): void
    {
        $this->lastSeenLoadedAt = app(LastSeenService::class)->current();

        $rawDay = $this->figureOutDay();

        $this->day = $rawDay->toDateString();

        $this->lunch = app(LunchService::class)->getLunch($this->day);

        $this->canCreateTemplates = auth()->user()->can('update_lesson::template');

        $layoutService = app(LayoutService::class);
        $layout = $layoutService->getLayoutWithModelForDate($this->day);

        $this->dayLayout = $layout['data'];
        $this->textSize = $layout['model']?->text_size ?? 100.0;

        $this->colors = Color::all()->pluck('color', 'id')->toArray();

        $user = auth()->user();

        $this->canCreateLesson = $user->can('create', Lesson::class);
        $this->canCreateRecurring = $user->can('create', LessonTemplate::class);

        $resolvedLessons = app(DayLessonService::class)->lessonsForDate($rawDay)
            ->map(function (Lesson|LessonTemplate $lesson) use ($user) {
                $array = $lesson->toArray();
                $array['assigned_users'] = $lesson->assignedUsers->pluck('display_name', 'id')->toArray();
                $isAssigned = $lesson->assignedUsers->contains('id', $user->id);

                if ($lesson instanceof LessonTemplate) {
                    if (($user->can('create_lesson') && $isAssigned) || $user->can('view_any_lesson')) {
                        // Deleted templates still show on earlier days but cannot be edited for all weeks.
                        $array['url_template'] = $lesson->trashed() ? null : LessonTemplateResource::getUrl('edit', ['record' => $lesson->id, 'date' => $this->day]);
                        $array['url'] = LessonResource::getUrl('create', ['copy' => $lesson->id, 'date' => $this->day]);
                    }
                    $array['inline_template'] = $this->canCreateLesson ? $lesson->id : null;
                } else {
                    if (($user->can('view_lesson') && $isAssigned) || $user->can('view_any_lesson')) {
                        $array['url'] = LessonResource::getUrl('edit', ['record' => $lesson->id, 'date' => $this->day]);
                    }
                    $array['inline_lesson'] = $user->can('update', $lesson) ? $lesson->id : null;
                }

                return $array;
            });

        $rawAbsences = Absence::with(['user'])
            ->whereDate('start', '<=', $rawDay)
            ->whereDate('end', '>=', $rawDay)
            ->get();

        $this->absences = array_map(fn ($entry) => [
            'id' => $entry['user']['id'],
            'display_name' => $entry['user']['display_name'],
        ], $rawAbsences->toArray());

        $this->absences = array_values(array_unique($this->absences, SORT_REGULAR));

        usort($this->absences, fn ($a, $b) => $b['id'] <=> $a['id']);

        // Extract absent user IDs for checking in blade template
        $absentUserIds = array_column($this->absences, 'id');

        $merged = $resolvedLessons;

        // Add absent user IDs to each lesson
        $this->lessons = $merged->map(function ($lesson) use ($absentUserIds) {
            $lesson['absent_user_ids'] = $absentUserIds;

            return $lesson;
        })->values()->toArray();

        if (! env('DAY_VIEW_DISPLAY_ALL_ABSENCE_NOTES')) {
            $allUserIds = [];
            foreach ($this->lessons as $entry) {
                if (! empty($entry['assigned_users'])) {
                    foreach ($entry['assigned_users'] as $userID => $userName) {
                        $allUserIds[] = $userID;
                    }
                }
            }
            $this->absences = array_filter($this->absences, function ($absence) use ($allUserIds) {
                return in_array($absence['id'], $allUserIds);
            });

        }
    }

    public function replacePlaceholders(string $text): string
    {
        $dayName = $this->figureOutDay()->translatedFormat('D');
        $dayFull = $this->figureOutDay()->translatedFormat('d.m.Y');

        $absences = '';

        foreach ($this->absences as $key => $absence) {
            $absences .= $absence['display_name'].($key === array_key_last($this->absences) ? '' : ', ');
        }

        $context = [
            'mittagessen' => $this->lunch,
            'abwesenheit' => $absences,
            'tag' => str_replace('.', '', $dayName).' '.$dayFull,
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

    /**
     * Inline editing from the day view. Arguments: ['lesson' => id] edits that lesson,
     * ['template' => id] creates a lesson for this day only (the recurring template stays
     * untouched, like "Nur heute" on the full edit page).
     */
    public function editLessonAction(): Action
    {
        return Action::make('editLesson')
            ->modalHeading(fn (array $arguments) => isset($arguments['template'])
                ? 'Angebot nur für diesen Tag bearbeiten'
                : 'Angebot bearbeiten')
            ->modalDescription(fn (array $arguments) => isset($arguments['template'])
                ? 'Die Änderung gilt nur für diesen Tag. Das wiederholende Angebot bleibt unverändert.'
                : null)
            ->modalWidth(MaxWidth::ThreeExtraLarge)
            ->modalSubmitActionLabel('Speichern')
            ->fillForm(function (array $arguments) {
                $source = $this->inlineSource($arguments);

                return [
                    'name' => $source->name,
                    'description' => $source->description,
                    'notes' => $source->notes,
                    'color' => $source->color,
                    'disabled' => (bool) $source->disabled,
                    'assignedUsers' => $source->assignedUsers->pluck('id')->all(),
                ];
            })
            ->form(fn () => $this->lessonFormSchema(auth()->user()->can('view_any_lesson')))
            ->action(function (array $data, array $arguments) {
                $source = $this->inlineSource($arguments);
                $fields = [
                    'name' => $data['name'],
                    'description' => $data['description'],
                    'notes' => $data['notes'],
                    'color' => $data['color'],
                    'disabled' => $data['disabled'],
                    'updated_by' => auth()->id(),
                ];

                if ($source instanceof LessonTemplate) {
                    Gate::authorize('create', Lesson::class);

                    $lesson = Lesson::create($fields + [
                        'parent_id' => $source->id,
                        'date' => $this->day,
                        'room' => $source->room,
                        'lesson_time' => $source->lesson_time,
                        'created_by' => auth()->id(),
                    ]);
                    $lesson->assignedUsers()->sync($source->assignedUsers->pluck('id'));
                } else {
                    Gate::authorize('update', $source);

                    $lesson = $source;
                    $lesson->update($fields);
                }

                if (auth()->user()->can('view_any_lesson')) {
                    $lesson->assignedUsers()->sync($data['assignedUsers'] ?? []);
                }

                Notification::make()->title('Angebot gespeichert')->success()->send();

                $this->mount();
            })
            ->extraModalFooterActions(fn (array $arguments) => [
                Action::make('openFullEditor')
                    ->label('Alle Optionen')
                    ->color('gray')
                    ->url(isset($arguments['template'])
                        ? LessonResource::getUrl('create', ['copy' => $arguments['template'], 'date' => $this->day])
                        : LessonResource::getUrl('edit', ['record' => $arguments['lesson'] ?? 0, 'date' => $this->day])),
            ]);
    }

    /**
     * Creating from an empty slot. Arguments: room, time and recurring (true creates a
     * recurring lesson for this weekday from now on, false a lesson for this day only).
     */
    public function createLessonAction(): Action
    {
        return Action::make('createLesson')
            ->modalHeading(fn (array $arguments) => ! empty($arguments['recurring'])
                ? 'Neues Angebot für jede Woche'
                : 'Neues Angebot nur für diesen Tag')
            ->modalDescription(fn (array $arguments) => ! empty($arguments['recurring'])
                ? 'Das Angebot findet ab heute jeden '.$this->figureOutDay()->translatedFormat('l').' in diesem Slot statt.'
                : 'Das Angebot findet nur am '.$this->figureOutDay()->translatedFormat('d.m.Y').' statt.')
            ->modalWidth(MaxWidth::ThreeExtraLarge)
            ->modalSubmitActionLabel('Anlegen')
            ->authorize(fn (array $arguments) => auth()->user()->can('create', ! empty($arguments['recurring']) ? LessonTemplate::class : Lesson::class))
            ->form(fn (array $arguments) => $this->lessonFormSchema(! empty($arguments['recurring'])
                ? auth()->user()->can('view_any_lesson::template')
                : auth()->user()->can('view_any_lesson')))
            ->action(function (array $data, array $arguments) {
                $recurring = ! empty($arguments['recurring']);
                $model = $recurring ? LessonTemplate::class : Lesson::class;

                Gate::authorize('create', $model);

                $fields = [
                    'name' => $data['name'],
                    'description' => $data['description'],
                    'notes' => $data['notes'],
                    'color' => $data['color'],
                    'disabled' => $data['disabled'],
                    'room' => (int) $arguments['room'],
                    'lesson_time' => (int) $arguments['time'],
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ];

                $lesson = $model::create($fields + ($recurring
                    ? ['weekday' => $this->figureOutDay()->isoWeekday()]
                    : ['date' => $this->day]));

                if (array_key_exists('assignedUsers', $data)) {
                    $lesson->assignedUsers()->sync($data['assignedUsers'] ?? []);
                }

                Notification::make()->title('Angebot angelegt')->success()->send();

                $this->mount();
            })
            ->extraModalFooterActions(fn (array $arguments) => [
                Action::make('openFullCreator')
                    ->label('Alle Optionen')
                    ->color('gray')
                    ->url(! empty($arguments['recurring'])
                        ? LessonTemplateResource::getUrl('create', ['date' => $this->day])
                        : LessonResource::getUrl('create', ['date' => $this->day, 'room' => $arguments['room'] ?? null, 'time' => $arguments['time'] ?? null])),
            ]);
    }

    /**
     * Fields shared by the inline edit and create modals.
     */
    private function lessonFormSchema(bool $canAssignUsers): array
    {
        return [
            CustomRichEditor::make('name')
                ->label('Name')
                ->required()
                ->toolbarButtons(['bold', 'italic', 'strike', 'underline', 'undo', 'redo']),
            CustomRichEditor::make('description')
                ->label('Beschreibung')
                ->toolbarButtons(['bold', 'italic', 'strike', 'underline', 'bulletList', 'orderedList', 'undo', 'redo']),
            Select::make('assignedUsers')
                ->label('Personen')
                ->multiple()
                ->searchable()
                ->preload()
                ->options(fn () => User::query()
                    ->whereHas('roles')
                    ->whereDoesntHave('roles', fn ($query) => $query->where('name', 'super_admin'))
                    ->orderBy('name')
                    ->pluck('name', 'id'))
                ->visible($canAssignUsers),
            Select::make('color')
                ->label('Farbe')
                ->options(fn () => Color::query()->pluck('name', 'id')),
            TextInput::make('notes')
                ->label('Notizen'),
            Toggle::make('disabled')
                ->label('Fällt aus'),
        ];
    }

    private function inlineSource(array $arguments): Lesson|LessonTemplate
    {
        if (isset($arguments['template'])) {
            return LessonTemplate::withTrashed()->with('assignedUsers')->findOrFail($arguments['template']);
        }

        return Lesson::with('assignedUsers')->findOrFail($arguments['lesson'] ?? 0);
    }

    public function checkForUpdate(): void
    {
        $current = app(LastSeenService::class)->current();

        if ($this->lastSeenLoadedAt === null) {
            $this->lastSeenLoadedAt = $current;

            return;
        }

        try {
            if (Carbon::parse($current)->gt(Carbon::parse($this->lastSeenLoadedAt))) {
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
        $date = Carbon::parse($this->day);
        $isInPast = $date->isBefore(Carbon::today());

        if ($isInPast) {
            // Past dates: never regenerate from Day page, only serve existing PDF
            $base64Content = $pdfService->getExistingPdf($this->day);

            if (! $base64Content) {
                Notification::make()
                    ->title('PDF nicht verfügbar')
                    ->body('Für dieses Datum existiert keine PDF.')
                    ->danger()
                    ->send();

                return null;
            }
        } else {
            // Today or future dates: auto-regenerate if outdated
            $base64Content = $pdfService->getOrGeneratePdf($this->day);
        }

        $binaryContent = base64_decode($base64Content);
        $filename = $date->locale(config('app.locale'))->translatedFormat('l, d.m.Y').'.pdf';

        return Response::streamDownload(function () use ($binaryContent) {
            echo $binaryContent;
        }, $filename, ['Content-Type' => 'application/pdf']);
    }
}
