<?php

namespace App\Providers;

use App\Models\Absence;
use App\Models\ActivityLog;
use App\Models\Color;
use App\Models\DayPdf;
use App\Models\Layout;
use App\Models\LayoutDeviation;
use App\Models\Lesson;
use App\Models\LessonTemplate;
use App\Models\Role;
use App\Models\Room;
use App\Models\Time;
use App\Models\User;
use App\Policies\AbsencePolicy;
use App\Policies\ActivityLogPolicy;
use App\Policies\ColorPolicy;
use App\Policies\DayPdfPolicy;
use App\Policies\LayoutDeviationPolicy;
use App\Policies\LayoutPolicy;
use App\Policies\LessonPolicy;
use App\Policies\LessonTemplatePolicy;
use App\Policies\RolePolicy;
use App\Policies\RoomPolicy;
use App\Policies\TimePolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {

        $this->registerPolicies();
    }

    protected $policies = [
        Absence::class => AbsencePolicy::class,
        ActivityLog::class => ActivityLogPolicy::class,
        DayPdf::class => DayPdfPolicy::class,
        Lesson::class => LessonPolicy::class,
        LessonTemplate::class => LessonTemplatePolicy::class,
        Layout::class => LayoutPolicy::class,
        LayoutDeviation::class => LayoutDeviationPolicy::class,
        Color::class => ColorPolicy::class,
        Role::class => RolePolicy::class,
        Room::class => RoomPolicy::class,
        Time::class => TimePolicy::class,
        User::class => UserPolicy::class,

    ];
}
