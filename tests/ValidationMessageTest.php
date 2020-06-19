<?php

namespace CodeZero\UniqueTranslation\Tests;

use CodeZero\UniqueTranslation\Tests\Stubs\Model;
use CodeZero\UniqueTranslation\UniqueTranslationRule;
use Illuminate\Support\Facades\Validator;

class ValidationMessageTest extends TestCase
{
    /** @test */
    public function it_returns_a_default_error_message_when_validating_a_single_translation()
    {
        Model::create([
            'slug' => ['en' => 'existing-slug-en'],
            'name' => ['en' => 'existing-name-en'],
        ]);

        $formAttributes = [
            'form_slug' => 'existing-slug-en',
            'form_name' => 'existing-name-en',
        ];

        $rules = [
            'form_slug' => "{$this->rule}:{$this->table},slug",
            'form_name' => UniqueTranslationRule::for($this->table, 'name'),
        ];

        $expectedSlugError = trans('validation.unique', ['attribute' => 'form slug']);
        $expectedNameError = trans('validation.unique', ['attribute' => 'form name']);

        $this->assertNotEmpty($expectedSlugError);
        $this->assertNotEmpty($expectedNameError);

        $validation = Validator::make($formAttributes, $rules);

        $this->assertEquals([
            'form_slug' => [$expectedSlugError],
            'form_name' => [$expectedNameError],
        ], $validation->errors()->messages());
    }

    /** @test */
    public function it_returns_a_default_error_message_when_validating_an_array()
    {
        Model::create([
            'slug' => ['en' => 'existing-slug-en'],
            'name' => ['en' => 'existing-name-en'],
        ]);

        $formAttributes = [
            'form_slug' => ['en' => 'existing-slug-en'],
            'form_name' => ['en' => 'existing-name-en'],
        ];

        $rules = [
            'form_slug.*' => "{$this->rule}:{$this->table},slug",
            'form_name.*' => UniqueTranslationRule::for($this->table, 'name'),
        ];

        $expectedSlugError = trans('validation.unique', ['attribute' => 'form_slug.en']);
        $expectedNameError = trans('validation.unique', ['attribute' => 'form_name.en']);

        $this->assertNotEmpty($expectedSlugError);
        $this->assertNotEmpty($expectedNameError);

        $validation = Validator::make($formAttributes, $rules);

        $this->assertEquals([
            'form_slug.en' => [$expectedSlugError],
            'form_name.en' => [$expectedNameError],
        ], $validation->errors()->messages());
    }

    /** @test */
    public function it_returns_a_custom_error_message_when_validating_a_single_translation()
    {
        Model::create([
            'slug' => ['en' => 'existing-slug-en'],
            'name' => ['en' => 'existing-name-en'],
        ]);

        $formAttributes = [
            'form_slug' => 'existing-slug-en',
            'form_name' => 'existing-name-en',
        ];

        $rules = [
            'form_slug' => "{$this->rule}:{$this->table},slug",
            'form_name' => UniqueTranslationRule::for($this->table, 'name'),
        ];

        $messages = [
            "form_slug.{$this->rule}" => 'Custom slug message for :attribute.',
            "form_name.{$this->rule}" => 'Custom name message for :attribute.',
        ];

        $expectedSlugError = 'Custom slug message for form slug.';
        $expectedNameError = 'Custom name message for form name.';

        $validation = Validator::make($formAttributes, $rules, $messages);

        $this->assertEquals([
            'form_slug' => [$expectedSlugError],
            'form_name' => [$expectedNameError],
        ], $validation->errors()->messages());
    }

    /** @test */
    public function it_returns_a_custom_error_message_when_validating_an_array()
    {
        Model::create([
            'slug' => ['en' => 'existing-slug-en'],
            'name' => ['en' => 'existing-name-en'],
        ]);

        $formAttributes = [
            'form_slug' => ['en' => 'existing-slug-en'],
            'form_name' => ['en' => 'existing-name-en'],
        ];

        $rules = [
            'form_slug.*' => "{$this->rule}:{$this->table},slug",
            'form_name.*' => UniqueTranslationRule::for($this->table, 'name'),
        ];

        $messages = [
            "form_slug.*.{$this->rule}" => 'Custom slug message for :attribute.',
            "form_name.*.{$this->rule}" => 'Custom name message for :attribute.',
        ];

        $expectedSlugError = 'Custom slug message for form_slug.en.';
        $expectedNameError = 'Custom name message for form_name.en.';

        $validation = Validator::make($formAttributes, $rules, $messages);

        $this->assertEquals([
            'form_slug.en' => [$expectedSlugError],
            'form_name.en' => [$expectedNameError],
        ], $validation->errors()->messages());
    }

    /** @test */
    public function it_returns_a_default_error_message_when_validating_a_nova_translation()
    {
        Model::create([
            'slug' => ['en' => 'existing-slug-en'],
            'name' => ['en' => 'existing-name-en'],
        ]);

        $formAttributes = [
            'translations_form_slug_en' => 'existing-slug-en',
            'translations_form_name_en' => 'existing-name-en',
        ];

        $rules = [
            'translations_form_slug_en' => "{$this->rule}:{$this->table},slug",
            'translations_form_name_en' => UniqueTranslationRule::for($this->table, 'name'),
        ];

        $expectedSlugError = trans('validation.unique', ['attribute' => 'translations form slug en']);
        $expectedNameError = trans('validation.unique', ['attribute' => 'translations form name en']);

        $this->assertNotEmpty($expectedSlugError);
        $this->assertNotEmpty($expectedNameError);

        $validation = Validator::make($formAttributes, $rules);

        $this->assertEquals([
            'translations_form_slug_en' => [$expectedSlugError],
            'translations_form_name_en' => [$expectedNameError],
        ], $validation->errors()->messages());
    }
}
