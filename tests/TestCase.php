<?php

namespace Daikazu\LaravelGlider\Tests;

use Daikazu\LaravelGlider\LaravelGliderServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    /**
     * The latest response instance.
     *
     * This property is defined in Testbench v10+ but not v9.
     * We define it here for backward compatibility with both versions.
     *
     * @var \Illuminate\Testing\TestResponse|null
     */
    public static $latestResponse = null;

    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Daikazu\\LaravelGlider\\Database\\Factories\\' . class_basename($modelName) . 'Factory'
        );
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');

        /*
         foreach (\Illuminate\Support\Facades\File::allFiles(__DIR__ . '/database/migrations') as $migration) {
            (include $migration->getRealPath())->up();
         }
         */
    }

    protected function getPackageProviders($app)
    {
        return [
            LaravelGliderServiceProvider::class,
        ];
    }
}
