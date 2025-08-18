<?php

namespace Daikazu\LaravelGlider\Commands;

use Illuminate\Console\Command;

class LaravelGliderCommand extends Command
{
    public $signature = 'laravel-glider';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
