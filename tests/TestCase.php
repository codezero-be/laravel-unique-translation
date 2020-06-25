<?php

namespace CodeZero\UniqueTranslation\Tests;

use CodeZero\UniqueTranslation\UniqueTranslationServiceProvider;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Database table for the test models.
     *
     * @var string
     */
    protected $table = 'test_models';

    /**
     * Name of the validation rule.
     *
     * @var string
     */
    protected $rule = 'unique_translation';

    /**
     * Setup the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('app.key', Str::random(32));

        App::setLocale('en');

        $this->setupDatabase();
    }

    /**
     * Get the packages service providers.
     *
     * @param \Illuminate\Foundation\Application $app
     *
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            UniqueTranslationServiceProvider::class,
        ];
    }

    /**
     * Setup the test database.
     *
     * @return void
     */
    protected function setupDatabase()
    {
        $this->app['db']->getSchemaBuilder()->dropIfExists($this->table);

        $this->app['db']->getSchemaBuilder()->create($this->table, function (Blueprint $table) {
            $table->increments('id');
            $table->json('slug')->nullable();
            $table->text('name')->nullable();
            $table->string('other_field')->nullable();
        });

        $this->beforeApplicationDestroyed(function () {
            $this->app['db']->getSchemaBuilder()->drop($this->table);
        });
    }
}
