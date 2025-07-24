<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Spatie\Activitylog\Models\Activity;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
        // Load the helpers file manually
        require_once app_path('Http/helpers.php');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot()
    {
        Activity::saving(function (Activity $activity) {
            if (auth('sanctum')->check()) {
                $user = auth('sanctum')->user();
                $activity->causer_id = $user->id;
                $activity->causer_type = get_class($user);
            }
        });
    }
}
