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
            'name' => UniqueTranslationRule::for($this->table),
        ];

        $this->createRoute('test', $rules);

        $this->post('test', [
            'slug' => 'slug-en',
            'name' => 'name-en',
        ])->assertSessionHasErrors(['slug', 'name', 'slug.en', 'name.en']);

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
            'name.*' => UniqueTranslationRule::for($this->table),
        ];

        $this->createRoute('test', $rules);

        $this->post('test', [
            'slug' => ['en' => 'slug-en'],
            'name' => ['en' => 'name-en'],
        ])->assertSessionHasErrors(['slug', 'name', 'slug.en', 'name.en']);

        $this->post('test', [
            'slug' => ['nl' => 'slug-nl'],
            'name' => ['nl' => 'name-nl'],
        ])->assertSessionHasErrors(['slug', 'name', 'slug.nl', 'name.nl']);

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
            'form_name' => UniqueTranslationRule::for($this->table, 'name'),
        ];

        $this->createRoute('test-single', $rules);

        $this->post('test-single', [
            'form_slug' => 'slug-en',
            'form_name' => 'name-en',
        ])->assertSessionHasErrors(['form_slug', 'form_name', 'form_slug.en', 'form_name.en']);

        $rules = [
            'form_slug.*' => "{$this->rule}:{$this->table},slug",
            'form_name.*' => UniqueTranslationRule::for($this->table, 'name'),
        ];

        $this->createRoute('test-array', $rules);

        $this->post('test-array', [
            'form_slug' => ['nl' => 'slug-nl'],
            'form_name' => ['nl' => 'name-nl'],
        ])->assertSessionHasErrors(['form_slug', 'form_name', 'form_slug.nl', 'form_name.nl']);
    }

    /** @test */
    public function it_ignores_the_given_id()
    {
        $rules = [
            'slug' => "{$this->rule}:{$this->table},slug,{$this->model->id}",
            'name' => UniqueTranslationRule::for($this->table)->ignore($this->model->id),
        ];

        $this->createRoute('test-single', $rules);

        $this->post('test-single', [
            'slug' => 'slug-en',
            'name' => 'name-en',
        ])->assertStatus(200);

        $rules = [
            'slug.*' => "{$this->rule}:{$this->table},slug,{$this->model->id}",
            'name.*' => UniqueTranslationRule::for($this->table)->ignore($this->model->id),
        ];

        $this->createRoute('test-array', $rules);

        Model::create([
            'slug' => [
                'en' => 'another-slug-en',
                'nl' => 'another-slug-nl',
            ],
            'name' => [
                'en' => 'another-name-en',
                'fr' => null,
            ],
        ]);

        $this->post('test-array', [
            'slug' => ['en' => 'slug-en', 'nl' => 'slug-nl'],
            'name' => ['en' => 'name-en', 'fr' => null],
        ])->assertStatus(200);
    }

    /** @test */
    public function it_ignores_a_specific_attribute_with_the_given_value()
    {
        $rules = [
            'slug' => "{$this->rule}:{$this->table},slug,{$this->model->other_field},other_field",
            'name' => UniqueTranslationRule::for($this->table)->ignore($this->model->other_field, 'other_field'),
        ];

        $this->createRoute('test-single', $rules);

        $this->post('test-single', [
            'slug' => 'slug-en',
            'name' => 'name-en',
        ])->assertStatus(200);

        $rules = [
            'slug.*' => "{$this->rule}:{$this->table},slug,{$this->model->other_field},other_field",
            'name.*' => UniqueTranslationRule::for($this->table)->ignore($this->model->other_field, 'other_field'),
        ];

        $this->createRoute('test-array', $rules);

        $this->post('test-array', [
            'slug' => ['nl' => 'slug-nl'],
            'name' => ['nl' => 'name-nl'],
        ])->assertStatus(200);
    }

    /** @test */
    public function it_returns_a_default_error_message_when_validating_a_single_translation()
    {
        $rules = [
            'form_slug' => "{$this->rule}:{$this->table},slug",
            'form_name' => UniqueTranslationRule::for($this->table, 'name'),
        ];

        $this->createRoute('test', $rules);

        $this->post('test', [
            'form_slug' => 'slug-en',
            'form_name' => 'name-en',
        ]);

        $expectedSlugError = trans('validation.unique', ['attribute' => 'form slug']);
        $expectedNameError = trans('validation.unique', ['attribute' => 'form name']);

        $errors = session('errors');

        $returnedSlugError = $errors->first('form_slug');
        $returnedNameError = $errors->first('form_name');

        $this->assertNotEmpty($returnedSlugError);
        $this->assertNotEmpty($returnedNameError);

        $this->assertEquals($expectedSlugError, $returnedSlugError);
        $this->assertEquals($expectedNameError, $returnedNameError);

        $returnedSlugArrayError = $errors->first('form_slug.en');
        $returnedNameArrayError = $errors->first('form_name.en');

        $this->assertNotEmpty($returnedSlugArrayError);
        $this->assertNotEmpty($returnedNameArrayError);

        $this->assertEquals($expectedSlugError, $returnedSlugArrayError);
        $this->assertEquals($expectedNameError, $returnedNameArrayError);
    }

    /** @test */
    public function it_returns_a_default_error_message_when_validating_an_array()
    {
        $rules = [
            'form_slug.*' => "{$this->rule}:{$this->table},slug",
            'form_name.*' => UniqueTranslationRule::for($this->table, 'name'),
        ];

        $this->createRoute('test', $rules);

        $this->post('test', [
            'form_slug' => ['en' => 'slug-en'],
            'form_name' => ['en' => 'name-en'],
        ]);

        $expectedSlugError = trans('validation.unique', ['attribute' => 'form slug']);
        $expectedNameError = trans('validation.unique', ['attribute' => 'form name']);

        $errors = session('errors');

        $returnedSlugError = $errors->first('form_slug');
        $returnedNameError = $errors->first('form_name');

        $this->assertNotEmpty($returnedSlugError);
        $this->assertNotEmpty($returnedNameError);

        $this->assertEquals($expectedSlugError, $returnedSlugError);
        $this->assertEquals($expectedNameError, $returnedNameError);

        $returnedSlugArrayError = $errors->first('form_slug.en');
        $returnedNameArrayError = $errors->first('form_name.en');

        $this->assertNotEmpty($returnedSlugArrayError);
        $this->assertNotEmpty($returnedNameArrayError);

        $this->assertEquals($expectedSlugError, $returnedSlugArrayError);
        $this->assertEquals($expectedNameError, $returnedNameArrayError);
    }

    /** @test */
    public function it_returns_a_custom_error_message_when_validating_a_single_translation()
    {
        $rules = [
            'form_slug' => "{$this->rule}:{$this->table},slug",
            'form_name' => UniqueTranslationRule::for($this->table, 'name'),
        ];

        $messages = [
            "form_slug.{$this->rule}" => 'Custom slug message for :attribute.',
            "form_name.{$this->rule}" => 'Custom name message for :attribute.',
        ];

        $this->createRoute('test', $rules, $messages);

        $this->post('test', [
            'form_slug' => 'slug-en',
            'form_name' => 'name-en',
        ]);

        $expectedSlugError = 'Custom slug message for form slug.';
        $expectedNameError = 'Custom name message for form name.';

        $errors = session('errors');

        $returnedSlugError = $errors->first('form_slug');
        $returnedNameError = $errors->first('form_name');

        $this->assertNotEmpty($returnedSlugError);
        $this->assertNotEmpty($returnedNameError);

        $this->assertEquals($expectedSlugError, $returnedSlugError);
        $this->assertEquals($expectedNameError, $returnedNameError);

        $returnedSlugArrayError = $errors->first('form_slug.en');
        $returnedNameArrayError = $errors->first('form_name.en');

        $this->assertNotEmpty($returnedSlugArrayError);
        $this->assertNotEmpty($returnedNameArrayError);

        $this->assertEquals($expectedSlugError, $returnedSlugArrayError);
        $this->assertEquals($expectedNameError, $returnedNameArrayError);
    }

    /** @test */
    public function it_returns_a_custom_error_message_when_validating_an_array()
    {
        $rules = [
            'form_slug.*' => "{$this->rule}:{$this->table},slug",
            'form_name.*' => UniqueTranslationRule::for($this->table, 'name'),
        ];

        $messages = [
            "form_slug.*.{$this->rule}" => 'Custom slug message for :attribute.',
            "form_name.*.{$this->rule}" => 'Custom name message for :attribute.',
        ];

        $this->createRoute('test', $rules, $messages);

        $this->post('test', [
            'form_slug' => ['en' => 'slug-en'],
            'form_name' => ['en' => 'name-en'],
        ]);

        $expectedSlugError = 'Custom slug message for form slug.';
        $expectedNameError = 'Custom name message for form name.';

        $errors = session('errors');

        $returnedSlugError = $errors->first('form_slug');
        $returnedNameError = $errors->first('form_name');

        $this->assertNotEmpty($returnedSlugError);
        $this->assertNotEmpty($returnedNameError);

        $this->assertEquals($expectedSlugError, $returnedSlugError);
        $this->assertEquals($expectedNameError, $returnedNameError);

        $returnedSlugArrayError = $errors->first('form_slug.en');
        $returnedNameArrayError = $errors->first('form_name.en');

        $this->assertNotEmpty($returnedSlugArrayError);
        $this->assertNotEmpty($returnedNameArrayError);

        $this->assertEquals($expectedSlugError, $returnedSlugArrayError);
        $this->assertEquals($expectedNameError, $returnedNameArrayError);
    }

    /**
     * Create a test route.
     *
     * @param string $url
     * @param array $rules
     * @param array $messages
     *
     * @return void
     */
    protected function createRoute($url, $rules, $messages = [])
    {
        Route::post($url, function () use ($rules, $messages) {
            return request()->validate($rules, $messages);
        });
    }
}
