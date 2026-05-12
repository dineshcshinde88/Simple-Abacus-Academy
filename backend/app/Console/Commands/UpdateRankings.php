<?php

namespace App\Console\Commands;

use App\Services\PerformanceService;
use Illuminate\Console\Command;

class UpdateRankings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rankings:update {--period= : weekly or monthly}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update weekly or monthly student rankings';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $period = $this->option('period') ?: 'weekly';

        $service = app(PerformanceService::class);

        if ($period === 'weekly') {
            $this->info('Updating weekly rankings...');
            $service->updateWeeklyRankings();
            $this->info('Weekly rankings updated successfully.');
        } elseif ($period === 'monthly') {
            $this->info('Updating monthly rankings...');
            $service->updateMonthlyRankings();
            $this->info('Monthly rankings updated successfully.');
        } else {
            $this->error('Invalid period. Use "weekly" or "monthly".');
            return 1;
        }

        return 0;
    }
}