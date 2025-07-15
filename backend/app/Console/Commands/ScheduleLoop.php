<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ScheduleLoop extends Command
{
    protected $signature = 'schedule:loop';
    protected $description = 'Run Laravel scheduler continuously';

    public function handle()
    {
        $this->info('Starting Laravel scheduler...');

        while (true) {
            $this->call('schedule:run');
            $this->info('Schedule executed at: ' . now());
            sleep(60);
        }
    }
}
