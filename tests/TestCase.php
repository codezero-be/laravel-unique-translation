<?php

namespace CodeZero\UniqueTranslation\Tests;

use CodeZero\UniqueTranslation\Tests\Stubs\Model;
use CodeZero\UniqueTranslation\UniqueTranslationServiceProvider;
use Illuminate\Database\Schema\Blueprint;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Route;

abstract class TestCase extends BaseTestCase
{
    /**
     * Database table for the test models.
     *
     * @var string
     */
    protected $table = 'test_models';

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
        $this->app['db']->getSchemaBuilder()->dropIfExists($this->table);

        $this->app['db']->getSchemaBuilder()->create($this->table, function (Blueprint $table) {
            $table->increments('id');
            $table->text('slug')->nullable();
            $table->string('other_field')->nullable();
        });

        $this->beforeApplicationDestroyed(function () {
            $this->app['db']->getSchemaBuilder()->drop($this->table);
        });
    }

    /**
     * Create a test route.
     *
     * @param string $url
     * @param array $rules
     *
     * @return void
     */
    protected function createRoute($url, $rules)
    {
        Route::post($url, function () use ($rules) {
            return request()->validate($rules);
        });
    }

    /**
     * Create a test model.
     *
     * @return Model
     */
    protected function createModel()
    {
        return Model::create([
            'slug' => [
                'en' => 'slug-en',
                'nl' => 'slug-nl',
            ],
            'other_field' => 'foobar',
        ]);
    }
}
