<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\Trip\TripStatusEnum;
use Database\Seeders\TripSeeder;
use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as CommandAlias;

class SeedTripsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'trips:seed
                            {--status= : Trip status value (2=PENDING_RIDER, 3=ACCEPTED_RIDER, 4=ON_TRIP, 5=COMPLETED, 6=CANCELED_BY_CUSTOMER, 7=CANCELLED_BY_RIDER)}
                            {--count=3 : Number of trips to create (default: 3)}
                            {--all : Create trips for all statuses}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed trips with specific status or all statuses';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $seeder = new TripSeeder;
        $seeder->setCommand($this);

        // If --all flag is provided, create trips for all statuses
        if ($this->option('all')) {
            $this->info('Creating trips for all statuses...');
            $seeder->createTripsForAllStatuses();

            return CommandAlias::SUCCESS;
        }

        // Get status option
        $statusValue = $this->option('status');

        if ($statusValue === null) {
            $this->error('Please provide --status option or use --all flag');
            $this->info('Available statuses:');
            $this->info('  2 = PENDING_RIDER');
            $this->info('  3 = ACCEPTED_RIDER');
            $this->info('  4 = ON_TRIP');
            $this->info('  5 = COMPLETED');
            $this->info('  6 = CANCELED_BY_CUSTOMER');
            $this->info('  7 = CANCELLED_BY_RIDER');
            $this->newLine();
            $this->info('Examples:');
            $this->info('  php artisan trips:seed --status=3 --count=5');
            $this->info('  php artisan trips:seed --all');

            return CommandAlias::FAILURE;
        }

        // Validate and convert status value to enum
        try {
            $status = TripStatusEnum::from((int) $statusValue);
        } catch (\ValueError $e) {
            $this->error("Invalid status value: {$statusValue}");
            $this->info('Valid status values: 2, 3, 4, 5, 6, 7');

            return CommandAlias::FAILURE;
        }

        // Validate DRAFT status (not allowed for seeding)
        if ($status === TripStatusEnum::DRAFT) {
            $this->error('DRAFT status is not allowed for seeding');

            return CommandAlias::FAILURE;
        }

        // Get count option
        $count = (int) $this->option('count');

        if ($count < 1) {
            $this->error('Count must be at least 1');

            return CommandAlias::FAILURE;
        }

        // Create trips
        $this->info("Creating {$count} trips with status: {$status->getLabel()}...");
        $seeder->createTripsForStatus($status, $count);
        $this->info('✓ Trips created successfully!');

        return CommandAlias::SUCCESS;
    }
}
