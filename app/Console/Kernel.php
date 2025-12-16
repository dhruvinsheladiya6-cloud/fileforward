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
        Commands\Subscriptions\RenewalFreePlan::class,
        Commands\Subscriptions\RenewalReminder::class,
        Commands\Subscriptions\ExpiryReminder::class,
        Commands\Subscriptions\DeleteExpired::class,
        Commands\DeleteUnpaidTransactions::class,
        Commands\DeleteChunks::class,
        Commands\DeleteExpiredFiles::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        if (licenseType(2)) {
            $schedule->command('subscriptions:renewal-free')->everyMinute();
            $schedule->command('subscription:renewal-reminder')->cron('0 0 */2 * *');
            $schedule->command('subscription:expiry-reminder')->cron('0 0 */3 * *');
            $schedule->command('subscriptions:delete-expired')->everyMinute();
            $schedule->command('transactions:unpaid-delete')->cron('25 * * * *');
        }
        $schedule->command('uploads:delete-chunks')->cron('0 0 * * *');
        $schedule->command('uploads:delete-expired')->everyMinute();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
