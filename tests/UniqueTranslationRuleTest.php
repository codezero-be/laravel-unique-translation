<?php

namespace CodeZero\UniqueTranslation\Tests;

use CodeZero\UniqueTranslation\UniqueTranslationRule;

class UniqueTranslationRuleTest extends TestCase
{
    /**
     * @var \CodeZero\UniqueTranslation\Tests\Stubs\Model
     */
    protected $model;

    /**
     * Setup the test environment.
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        app()->setLocale('en');

        $this->model = $this->createModel();
    }

    /** @test */
    public function it_checks_if_the_translation_for_the_current_locale_is_unique()
    {
        $rules = ['slug' => new UniqueTranslationRule($this->table)];

        $this->createRoute('test', $rules);

        $this->post('test', ['slug' => 'slug-en'])
            ->assertSessionHasErrors('slug');

        $this->post('test', ['slug' => 'slug-nl'])
            ->assertStatus(200);
    }

    /** @test */
    public function it_checks_if_the_translation_for_a_specific_locale_is_unique()
    {
        $rules = ['slug.*' => new UniqueTranslationRule($this->table)];

        $this->createRoute('test', $rules);

        $this->post('test', ['slug' => ['en' => 'slug-en']])
            ->assertSessionHasErrors('slug.en');

        $this->post('test', ['slug' => ['nl' => 'slug-nl']])
            ->assertSessionHasErrors('slug.nl');

        $this->post('test', ['slug' => ['en' => 'different-slug-en']])
            ->assertStatus(200);

        $this->post('test', ['slug' => ['nl' => 'different-slug-en']])
            ->assertStatus(200);
    }

    /** @test */
    public function the_models_attribute_name_can_be_specified()
    {
        $rules = ['form_slug' => new UniqueTranslationRule($this->table, 'slug')];

        $this->createRoute('test-single', $rules);

        $this->post('test-single', ['form_slug' => 'slug-en'])
            ->assertSessionHasErrors('form_slug');

        $rules = ['form_slug.*' => new UniqueTranslationRule($this->table, 'slug')];

        $this->createRoute('test-array', $rules);

        $this->post('test-array', ['form_slug' => ['nl' => 'slug-nl']])
            ->assertSessionHasErrors('form_slug.nl');
    }

    /** @test */
    public function it_ignores_the_given_id()
    {
        $rules = ['slug' => (new UniqueTranslationRule($this->table))->ignore($this->model->id)];

        $this->createRoute('test-single', $rules);

        $this->post('test-single', ['slug' => 'slug-en'])
            ->assertStatus(200);

        $rules = ['slug.*' => (new UniqueTranslationRule($this->table))->ignore($this->model->id)];

        $this->createRoute('test-array', $rules);

        $this->post('test-array', ['slug' => ['nl' => 'slug-nl']])
            ->assertStatus(200);
    }

    /** @test */
    public function it_ignores_a_specific_attribute_with_the_given_value()
    {
        $rules = ['slug' => (new UniqueTranslationRule($this->table))->ignore($this->model->other_field, 'other_field')];

        $this->createRoute('test-single', $rules);

        $this->post('test-single', ['slug' => 'slug-en'])
            ->assertStatus(200);

        $rules = ['slug.*' => (new UniqueTranslationRule($this->table))->ignore($this->model->other_field, 'other_field')];

        $this->createRoute('test-array', $rules);

        $this->post('test-array', ['slug' => ['nl' => 'slug-nl']])
            ->assertStatus(200);
    }

    /** @test */
    public function it_returns_the_correct_error_message()
    {
        $rules = ['form_slug' => new UniqueTranslationRule($this->table, 'slug')];

        $this->createRoute('test', $rules);

        $this->post('test', ['form_slug' => 'slug-en']);

        $errors = session('errors');
        $returnedMessage = $errors->first('form_slug');
        $expectedMessage = trans('validation.unique', ['attribute' => 'form_slug']);

        $this->assertNotEmpty($returnedMessage);
        $this->assertEquals($expectedMessage, $returnedMessage);
    }
}
