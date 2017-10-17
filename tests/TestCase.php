<?php

namespace CodeZero\UniqueTranslation\Tests;

use CodeZero\UniqueTranslation\UniqueTranslationServiceProvider;
use Illuminate\Database\Schema\Blueprint;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Setup the test environment.
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        config()->set('app.key', str_random(32));

        $this->setupDatabase();
    }

    /**
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
        $this->app['db']->getSchemaBuilder()->dropIfExists('test_models');

        $this->app['db']->getSchemaBuilder()->create('test_models', function (Blueprint $table) {
            $table->increments('id');
            $table->text('slug')->nullable();
            $table->string('other_field')->nullable();
        });

        $this->beforeApplicationDestroyed(function () {
            $this->app['db']->getSchemaBuilder()->drop('test_models');
        });
    }
}
