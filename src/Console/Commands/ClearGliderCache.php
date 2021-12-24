<?php

namespace Daikazu\LaravelGlider\Console\Commands;

use Daikazu\LaravelGlider\Imaging\GlideServer;
use File;
use Illuminate\Console\Command;

class ClearGliderCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'glider:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear Glider image cache';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        $cachePath = GlideServer::cachePath();

        File::deleteDirectory($cachePath, true);

        $this->info('Your Glider image cache is now empty.');

        return 0;
    }
}

