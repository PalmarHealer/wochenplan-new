<?php

namespace App\Providers;

use App\Listeners\LogFailedLogin;
use App\Listeners\LogSuccessfulLogin;
use App\Listeners\LogSuccessfulLogout;
use App\Models\Absence;
use App\Models\Color;
use App\Models\DayPdf;
use App\Models\Layout;
use App\Models\LayoutDeviation;
use App\Models\Lesson;
use App\Models\LessonTemplate;
use App\Models\Room;
use App\Models\Time;
use App\Models\User;
use App\Observers\AbsenceObserver;
use App\Observers\ActivityLogObserver;
use App\Observers\LessonObserver;
use App\Observers\LessonTemplateObserver;
use App\Observers\TouchesLastSeenObserver;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use SocialiteProviders\Azure\Provider;
use SocialiteProviders\Manager\SocialiteWasCalled;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register model observers to update last_seen
        Lesson::observe(TouchesLastSeenObserver::class);
        LessonTemplate::observe(TouchesLastSeenObserver::class);
        Absence::observe(TouchesLastSeenObserver::class);
        Color::observe(TouchesLastSeenObserver::class);
        DayPdf::observe(TouchesLastSeenObserver::class);
        // Layout-related changes should also trigger updates
        if (class_exists(Layout::class)) {
            Layout::observe(TouchesLastSeenObserver::class);
        }
        if (class_exists(LayoutDeviation::class)) {
            LayoutDeviation::observe(TouchesLastSeenObserver::class);
        }

        // Register observers to mark PDFs as outdated
        Lesson::observe(LessonObserver::class);
        LessonTemplate::observe(LessonTemplateObserver::class);
        Absence::observe(AbsenceObserver::class);

        // Register activity logging observers for all important models
        $loggedModels = [
            User::class,
            Lesson::class,
            LessonTemplate::class,
            Absence::class,
            Layout::class,
            LayoutDeviation::class,
            Color::class,
            Room::class,
            Time::class,
            DayPdf::class,
        ];

        foreach ($loggedModels as $model) {
            if (class_exists($model)) {
                $model::observe(ActivityLogObserver::class);
            }
        }

        // Keep existing runtime behavior
        if (App::runningInConsole()) {
            return;
        }

        // Force HTTPS scheme only if configured (for apps behind reverse proxy, set FORCE_HTTPS=false)
        if (config('app.force_https', true)) {
            URL::forceScheme('https');
        }

        Event::listen(function (SocialiteWasCalled $event) {
            $event->extendSocialite('azure', Provider::class);
        });

        // Register authentication event listeners for activity logging
        Event::listen(Login::class, LogSuccessfulLogin::class);
        Event::listen(Logout::class, LogSuccessfulLogout::class);
        Event::listen(Failed::class, LogFailedLogin::class);

        // Root redirect is now handled by routes/web.php
    }
}
