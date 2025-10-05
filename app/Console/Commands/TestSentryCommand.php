<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TestSentryCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:sentry';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Sentry error tracking integration';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Test Sentry integration
        $this->info('Testing Sentry integration...');

        try {
            throw new \Exception('This is a test exception for Sentry!');
        } catch (\Exception $e) {
            \Sentry\captureException($e);
            $this->info('Test exception sent to Sentry successfully!');
        }

        // Test Sentry message
        \Sentry\captureMessage('Test message from Laravel application', \Sentry\Severity::info());
        $this->info('Test message sent to Sentry successfully!');

        return 0;
    }
}
