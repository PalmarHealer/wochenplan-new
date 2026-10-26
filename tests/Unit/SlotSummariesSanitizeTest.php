<?php

use App\Models\Lesson;
use App\Services\DayLessonService;

it('sanitizes lesson rich text for the slot picker', function () {
    $lesson = new Lesson(['name' => '<strong>Acker</strong><img src=x onerror=alert(1)>', 'description' => '<script>alert(1)</script>Beete']);
    $lesson->setRelation('assignedUsers', collect());

    $summary = app(DayLessonService::class)->slotSummaries(collect(['1-1' => $lesson]))['1-1'];

    expect($summary['name'])->toContain('<strong>Acker</strong>')->not->toContain('onerror')
        ->and($summary['description'])->not->toContain('script')->toContain('Beete');
});
