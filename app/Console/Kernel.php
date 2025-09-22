<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        // custom commands go here
           \App\Console\Commands\UpdatePropertyAssessment::class,
           \App\Console\Commands\PropertyAssessmentSaveYearly::class,
    ];

    protected function schedule(Schedule $schedule)
    {
        // define scheduled tasks here
    }

    protected function commands()
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}
