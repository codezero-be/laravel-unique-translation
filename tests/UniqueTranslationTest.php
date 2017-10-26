<?php

namespace CodeZero\UniqueTranslation\Tests;

use CodeZero\UniqueTranslation\Tests\Stubs\Model;
use CodeZero\UniqueTranslation\UniqueTranslationRule;
use Route;

class UniqueTranslationTest extends TestCase
{
    protected $rule = 'unique_translation';

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

        $this->model = Model::create([
            'slug' => [
                'en' => 'slug-en',
                'nl' => 'slug-nl',
            ],
            'name' => [
                'en' => 'name-en',
                'nl' => 'name-nl',
            ],
            'other_field' => 'foobar',
        ]);
    }

    /** @test */
    public function it_checks_if_the_translation_for_the_current_locale_is_unique()
    {
        $rules = [
            'slug' => "{$this->rule}:{$this->table}",
            'name' => new UniqueTranslationRule($this->table),
        ];

        $this->createRoute('test', $rules);

        $this->post('test', [
            'slug' => 'slug-en',
            'name' => 'name-en',
        ])->assertSessionHasErrors(['slug', 'name']);

        $this->post('test', [
            'slug' => 'slug-nl',
            'name' => 'name-nl',
        ])->assertStatus(200); // succeeds because current locale is "en"
    }

    /** @test */
    public function it_checks_if_the_translation_for_a_specific_locale_is_unique()
    {
        $rules = [
            'slug.*' => "{$this->rule}:{$this->table}",
            'name.*' => new UniqueTranslationRule($this->table),
        ];

        $this->createRoute('test', $rules);

        $this->post('test', [
            'slug' => ['en' => 'slug-en'],
            'name' => ['en' => 'name-en'],
        ])->assertSessionHasErrors(['slug.en', 'name.en']);

        $this->post('test', [
            'slug' => ['nl' => 'slug-nl'],
            'name' => ['nl' => 'name-nl'],
        ])->assertSessionHasErrors(['slug.nl', 'name.nl']);

        $this->post('test', [
            'slug' => ['en' => 'different-slug-en'],
            'name' => ['en' => 'different-name-en'],
        ])->assertStatus(200);

        $this->post('test', [
            'slug' => ['nl' => 'different-slug-en'],
            'name' => ['nl' => 'different-name-en'],
        ])->assertStatus(200);
    }

    /** @test */
    public function the_models_attribute_name_can_be_specified()
    {
        $rules = [
            'form_slug' => "{$this->rule}:{$this->table},slug",
            'form_name' => new UniqueTranslationRule($this->table, 'name'),
        ];

        $this->createRoute('test-single', $rules);

        $this->post('test-single', [
            'form_slug' => 'slug-en',
            'form_name' => 'name-en',
        ])->assertSessionHasErrors(['form_slug', 'form_name']);

        $rules = [
            'form_slug.*' => "{$this->rule}:{$this->table},slug",
            'form_name.*' => new UniqueTranslationRule($this->table, 'name'),
        ];

        $this->createRoute('test-array', $rules);

        $this->post('test-array', [
            'form_slug' => ['nl' => 'slug-nl'],
            'form_name' => ['nl' => 'name-nl'],
        ])->assertSessionHasErrors(['form_slug.nl', 'form_name.nl']);
    }

    /** @test */
    public function it_ignores_the_given_id()
    {
        $rules = [
            'slug' => "{$this->rule}:{$this->table},slug,{$this->model->id}",
            'name' => (new UniqueTranslationRule($this->table))->ignore($this->model->id),
        ];

        $this->createRoute('test-single', $rules);

        $this->post('test-single', [
            'slug' => 'slug-en',
            'name' => 'name-en',
        ])->assertStatus(200);

        $rules = [
            'slug.*' => "{$this->rule}:{$this->table},slug,{$this->model->id}",
            'name.*' => (new UniqueTranslationRule($this->table))->ignore($this->model->id),
        ];

        $this->createRoute('test-array', $rules);

        $this->post('test-array', [
            'slug' => ['nl' => 'slug-nl'],
            'name' => ['nl' => 'name-nl'],
        ])->assertStatus(200);
    }

    /** @test */
    public function it_ignores_a_specific_attribute_with_the_given_value()
    {
        $rules = [
            'slug' => "{$this->rule}:{$this->table},slug,{$this->model->other_field},other_field",
            'name' => (new UniqueTranslationRule($this->table))->ignore($this->model->other_field, 'other_field'),
        ];

        $this->createRoute('test-single', $rules);

        $this->post('test-single', [
            'slug' => 'slug-en',
            'name' => 'name-en',
        ])->assertStatus(200);

        $rules = [
            'slug.*' => "{$this->rule}:{$this->table},slug,{$this->model->other_field},other_field",
            'name.*' => (new UniqueTranslationRule($this->table))->ignore($this->model->other_field, 'other_field'),
        ];

        $this->createRoute('test-array', $rules);

        $this->post('test-array', [
            'slug' => ['nl' => 'slug-nl'],
            'name' => ['nl' => 'name-nl'],
        ])->assertStatus(200);
    }

    /** @test */
    public function it_returns_the_correct_error_message()
    {
        $rules = [
            'form_slug' => "{$this->rule}:{$this->table},slug",
            'form_name' => new UniqueTranslationRule($this->table, 'name'),
        ];

        $this->createRoute('test', $rules);

        $this->post('test', [
            'form_slug' => 'slug-en',
            'form_name' => 'name-en',
        ]);

        $errors = session('errors');
        $returnedSlugError = $errors->first('form_slug');
        $returnedNameError = $errors->first('form_name');
        $expectedSlugError = trans('validation.unique', ['attribute' => 'form_slug']);
        $expectedNameError = trans('validation.unique', ['attribute' => 'form_name']);

        $this->assertNotEmpty($returnedSlugError);
        $this->assertNotEmpty($returnedNameError);
        $this->assertEquals($expectedSlugError, $returnedSlugError);
        $this->assertEquals($expectedNameError, $returnedNameError);
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
}
