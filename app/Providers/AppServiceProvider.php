<?php

namespace App\Providers;

use App\Events\InterventionAssigned;
use App\Listeners\NotifyClientOfAssignment;
use App\Listeners\NotifyTechnicianOfAssignment;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

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
        Event::listen(InterventionAssigned::class, [
            NotifyTechnicianOfAssignment::class,
            NotifyClientOfAssignment::class,
        ]);
    }
}
