<?php

namespace App\Providers;

use App\Models\Absence;
use App\Models\Color;
use App\Models\Layout;
use App\Models\LayoutDeviation;
use App\Models\Lesson;
use App\Models\LessonTemplate;
use App\Observers\TouchesLastSeenObserver;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\Azure\Provider;

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
        // Layout-related changes should also trigger updates
        if (class_exists(Layout::class)) {
            Layout::observe(TouchesLastSeenObserver::class);
        }
        if (class_exists(LayoutDeviation::class)) {
            LayoutDeviation::observe(TouchesLastSeenObserver::class);
        }

        // Keep existing runtime behavior
        if (App::runningInConsole()) {
            return;
        }
        URL::forceScheme('https');

        Event::listen(function (SocialiteWasCalled $event) {
            $event->extendSocialite('azure', Provider::class);
        });

        if (Request::is('/')) {
            Redirect::to('/dashboard')->send();
        }
    }
}
