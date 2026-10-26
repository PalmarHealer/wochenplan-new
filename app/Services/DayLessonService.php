<?php

namespace App\Services;

use App\Models\Lesson;
use App\Models\LessonTemplate;
use App\Models\Time;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

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
            ->activeOn($date)
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
     * Summary per slot for showing occupied cells in a layout like the day view does.
     * Name and description are the stored rich text HTML, rendered as in the day view.
     *
     * @param  Collection<string, Lesson|LessonTemplate>  $lessons
     * @return array<string, array{name: string, description: string, users: string, color: ?int, disabled: bool}>
     */
    public function slotSummaries(Collection $lessons): array
    {
        return $lessons
            ->map(fn ($lesson) => [
                'name' => Str::sanitizeHtml((string) $lesson->name),
                'description' => Str::sanitizeHtml((string) $lesson->description),
                'users' => $lesson->assignedUsers->pluck('display_name')->filter()->implode(', '),
                'color' => $lesson->color ? (int) $lesson->color : null,
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
        $visibleKeys = $this->visibleCells($date)
            ->map(fn ($cell) => $cell['room'].'-'.$cell['time'])
            ->flip();

        return $this->lessonsForDate($date)->filter(fn ($lesson, $key) => $visibleKeys->has($key));
    }

    /**
     * Start and end (minutes after midnight) of every time used in the layout of the date.
     * The end is the explicit end of the time, otherwise the next later start in that layout,
     * so the same time can be longer on a day with a different layout. Unknown values are null.
     *
     * @return array<int, array{start: ?int, end: ?int}>
     */
    public function timeRangesForDate(Carbon $date): array
    {
        $timeIds = $this->visibleCells($date)->pluck('time')->map(fn ($id) => (int) $id)->unique();
        $times = Time::whereIn('id', $timeIds)->get();

        $starts = $times->map(fn (Time $time) => $time->startMinutes())->filter(fn ($start) => $start !== null)->unique()->sort()->values();

        return $times
            ->mapWithKeys(function (Time $time) use ($starts) {
                $start = $time->startMinutes();
                $end = $time->endMinutes()
                    ?? ($start === null ? null : $starts->first(fn ($next) => $next > $start));

                return [$time->id => ['start' => $start, 'end' => $end]];
            })
            ->all();
    }

    /**
     * Visible lesson cells (with room and time) of the layout of the date.
     */
    private function visibleCells(Carbon $date): Collection
    {
        $layout = $this->layoutService->getLayoutWithModelForDate($date)['data'];

        return collect($layout)
            ->flatten(1)
            ->reject(fn ($cell) => ! is_array($cell) || isset($cell['hidden']) || empty($cell['room']) || empty($cell['time']));
    }
}
