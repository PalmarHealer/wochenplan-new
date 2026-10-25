<?php

namespace App\Services;

use App\Models\Lesson;
use App\Models\LessonTemplate;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DayLessonService
{
    public function __construct(
        private LayoutService $layoutService,
    ) {}

    /**
     * Lessons that are effective on the given date, keyed by "room-lesson_time".
     *
     * Same resolution as the day view: recurring templates of that weekday, minus the
     * ones overridden by a concrete lesson, then concrete lessons on top (a lesson in
     * the same room and time slot replaces the template).
     *
     * @return Collection<string, Lesson|LessonTemplate>
     */
    public function lessonsForDate(Carbon $date): Collection
    {
        $lessons = Lesson::with(['assignedUsers', 'rooms', 'times'])
            ->whereDate('date', $date->toDateString())
            ->get();

        $overriddenTemplateIds = $lessons->pluck('parent_id')->filter()->unique();

        $templates = LessonTemplate::with(['assignedUsers', 'rooms', 'times'])
            ->where('weekday', $date->format('N'))
            ->where('created_at', '<=', $date)
            ->where(function ($query) use ($date) {
                $query->whereNull('deleted_at')
                    ->orWhere('deleted_at', '>=', $date);
            })
            ->get()
            ->reject(fn ($template) => $overriddenTemplateIds->contains($template->id));

        $key = fn ($lesson) => $lesson->room.'-'.$lesson->lesson_time;

        // toBase(): Eloquent's merge() would merge by model id and drop the slot keys.
        return $templates->toBase()->keyBy($key)->merge($lessons->toBase()->keyBy($key));
    }

    /**
     * Recurring templates that are currently active on a weekday, keyed by "room-lesson_time".
     *
     * @return Collection<string, LessonTemplate>
     */
    public function templatesForWeekday(int $weekday): Collection
    {
        return LessonTemplate::with(['assignedUsers'])
            ->where('weekday', $weekday)
            ->get()
            ->toBase()
            ->keyBy(fn ($template) => $template->room.'-'.$template->lesson_time);
    }

    /**
     * Compact, display-ready summary per slot for showing occupied cells in a layout.
     *
     * @param  Collection<string, Lesson|LessonTemplate>  $lessons
     * @return array<string, array{name: string, users: string, disabled: bool}>
     */
    public function slotSummaries(Collection $lessons): array
    {
        return $lessons
            ->map(fn ($lesson) => [
                'name' => trim(html_entity_decode(strip_tags((string) $lesson->name))),
                'users' => $lesson->assignedUsers->pluck('display_name')->filter()->implode(', '),
                'disabled' => (bool) $lesson->disabled,
            ])
            ->all();
    }

    /**
     * Like lessonsForDate(), but only the lessons that have a visible cell in the
     * layout of that date. Lessons outside the layout (e.g. on a holiday layout) are
     * not shown in the day view either.
     *
     * @return Collection<string, Lesson|LessonTemplate>
     */
    public function visibleLessonsForDate(Carbon $date): Collection
    {
        $layout = $this->layoutService->getLayoutWithModelForDate($date)['data'];

        $visibleKeys = collect($layout)
            ->flatten(1)
            ->reject(fn ($cell) => isset($cell['hidden']) || empty($cell['room']) || empty($cell['time']))
            ->map(fn ($cell) => $cell['room'].'-'.$cell['time'])
            ->flip();

        return $this->lessonsForDate($date)->filter(fn ($lesson, $key) => $visibleKeys->has($key));
    }
}
