<?php

namespace CodeZero\UniqueTranslation\Tests;

use CodeZero\UniqueTranslation\Tests\Stubs\TestModel;
use Illuminate\Contracts\Validation\Factory as Validator;

class UniqueTranslationValidatorTest extends TestCase
{
    protected $rule = 'unique_translation';
    protected $table = 'test_models';

    /**
     * @var \CodeZero\UniqueTranslation\Tests\Stubs\TestModel
     */
    protected $model;

    /**
     * @var \Illuminate\Contracts\Validation\Factory
     */
    protected $validator;

    /**
     * Setup the test environment.
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        app()->setLocale('en');

        $this->validator = app(Validator::class);

        $this->model = TestModel::create([
            'slug' => [
                'en' => 'post-slug-en',
                'nl' => 'post-slug-nl',
            ],
            'other_field' => 'foobar',
        ]);
    }

    /** @test */
    public function it_checks_if_the_translation_for_the_current_locale_is_unique()
    {
        $rules = ['slug' => "{$this->rule}:{$this->table}"];

        $validation = $this->validate(['slug' => 'post-slug-en'], $rules);
        $this->assertFalse($validation->passes());

        $validation = $this->validate(['slug' => 'post-slug-nl'], $rules);
        $this->assertTrue($validation->passes());
    }

    /** @test */
    public function it_checks_if_the_translation_for_a_specific_locale_is_unique()
    {
        $rules = ['slug.*' => "{$this->rule}:{$this->table}"];

        $validation = $this->validate(['slug' => ['en' => 'post-slug-en']], $rules);
        $this->assertFalse($validation->passes());

        $validation = $this->validate(['slug' => ['nl' => 'post-slug-nl']], $rules);
        $this->assertFalse($validation->passes());

        $validation = $this->validate(['slug' => ['en' => 'different-post-slug-en']], $rules);
        $this->assertTrue($validation->passes());

        $validation = $this->validate(['slug' => ['nl' => 'different-post-slug-en']], $rules);
        $this->assertTrue($validation->passes());
    }

    /** @test */
    public function the_models_attribute_name_can_be_specified()
    {
        $rules = ['form_slug' => "{$this->rule}:{$this->table},slug"];

        $validation = $this->validate(['form_slug' => 'post-slug-en'], $rules);
        $this->assertFalse($validation->passes());

        $rules = ['form_slug.*' => "{$this->rule}:{$this->table},slug"];

        $validation = $this->validate(['form_slug' => ['nl' => 'post-slug-nl']], $rules);
        $this->assertFalse($validation->passes());
    }

    /** @test */
    public function it_ignores_the_given_id()
    {
        $rules = ['slug' => "{$this->rule}:{$this->table},slug,{$this->model->id}"];

        $validation = $this->validate(['slug' => 'post-slug-en'], $rules);
        $this->assertTrue($validation->passes());

        $rules = ['slug.*' => "{$this->rule}:{$this->table},slug,{$this->model->id}"];

        $validation = $this->validate(['slug' => ['nl' => 'post-slug-nl']], $rules);
        $this->assertTrue($validation->passes());
    }

    /** @test */
    public function it_ignores_a_specific_attribute_with_the_given_value()
    {
        $rules = ['slug' => "{$this->rule}:{$this->table},slug,{$this->model->other_field},other_field"];

        $validation = $this->validate(['slug' => 'post-slug-en'], $rules);
        $this->assertTrue($validation->passes());

        $rules = ['slug.*' => "{$this->rule}:{$this->table},slug,{$this->model->other_field},other_field"];

        $validation = $this->validate(['slug' => ['nl' => 'post-slug-nl']], $rules);
        $this->assertTrue($validation->passes());
    }

    /** @test */
    public function it_returns_the_correct_error_message()
    {
        $rules = ['form_slug' => "{$this->rule}:{$this->table},slug"];

        $validation = $this->validate(['form_slug' => 'post-slug-en'], $rules);
        $message = $validation->messages()->first('form_slug');

        $this->assertContains('form_slug', $message);
    }

    /**
     * Validate the data against the given rules with Laravel's Validator.
     *
     * @param array $data
     * @param array $rules
     *
     * @return \Illuminate\Validation\Validator
     */
    protected function validate($data, $rules)
    {
        return $this->validator->make($data, $rules);
    }
}
