<?php

namespace CodeZero\UniqueTranslation\Tests;

use CodeZero\UniqueTranslation\Tests\Stubs\Model;
use CodeZero\UniqueTranslation\UniqueTranslationRule;
use Validator;

class ValidationMessageTest extends TestCase
{
    /** @test */
    public function it_returns_a_default_error_message_when_validating_a_single_translation()
    {
        Model::create([
            'slug' => ['en' => 'existing-slug-en'],
            'name' => ['en' => 'existing-name-en'],
        ]);

        $rules = [
            'form_slug' => "{$this->rule}:{$this->table},slug",
            'form_name' => UniqueTranslationRule::for($this->table, 'name'),
        ];

        $validation = Validator::make([
            'form_slug' => 'existing-slug-en',
            'form_name' => 'existing-name-en',
        ], $rules);

        $expectedSlugError = trans('validation.unique', ['attribute' => 'form slug']);
        $expectedNameError = trans('validation.unique', ['attribute' => 'form name']);

        $this->assertNotEmpty($expectedSlugError);
        $this->assertNotEmpty($expectedNameError);

        $this->assertEquals($expectedSlugError, $validation->errors()->first('form_slug'));
        $this->assertEquals($expectedNameError, $validation->errors()->first('form_name'));

        $this->assertEquals($expectedSlugError, $validation->errors()->first('form_slug.en'));
        $this->assertEquals($expectedNameError, $validation->errors()->first('form_name.en'));
    }

    /** @test */
    public function it_returns_a_default_error_message_when_validating_an_array()
    {
        Model::create([
            'slug' => ['en' => 'existing-slug-en'],
            'name' => ['en' => 'existing-name-en'],
        ]);

        $rules = [
            'form_slug.*' => "{$this->rule}:{$this->table},slug",
            'form_name.*' => UniqueTranslationRule::for($this->table, 'name'),
        ];

        $validation = Validator::make([
            'form_slug' => ['en' => 'existing-slug-en'],
            'form_name' => ['en' => 'existing-name-en'],
        ], $rules);

        $expectedSlugError = trans('validation.unique', ['attribute' => 'form slug']);
        $expectedNameError = trans('validation.unique', ['attribute' => 'form name']);

        $this->assertNotEmpty($expectedSlugError);
        $this->assertNotEmpty($expectedNameError);

        $this->assertEquals($expectedSlugError, $validation->errors()->first('form_slug'));
        $this->assertEquals($expectedNameError, $validation->errors()->first('form_name'));

        $this->assertEquals($expectedSlugError, $validation->errors()->first('form_slug.en'));
        $this->assertEquals($expectedNameError, $validation->errors()->first('form_name.en'));
    }

    /** @test */
    public function it_returns_a_custom_error_message_when_validating_a_single_translation()
    {
        Model::create([
            'slug' => ['en' => 'existing-slug-en'],
            'name' => ['en' => 'existing-name-en'],
        ]);

        $rules = [
            'form_slug' => "{$this->rule}:{$this->table},slug",
            'form_name' => UniqueTranslationRule::for($this->table, 'name'),
        ];

        $messages = [
            "form_slug.{$this->rule}" => 'Custom slug message for :attribute.',
            "form_name.{$this->rule}" => 'Custom name message for :attribute.',
        ];

        $validation = Validator::make([
            'form_slug' => 'existing-slug-en',
            'form_name' => 'existing-name-en',
        ], $rules, $messages);

        $expectedSlugError = 'Custom slug message for form slug.';
        $expectedNameError = 'Custom name message for form name.';

        $this->assertEquals($expectedSlugError, $validation->errors()->first('form_slug'));
        $this->assertEquals($expectedNameError, $validation->errors()->first('form_name'));

        $this->assertEquals($expectedSlugError, $validation->errors()->first('form_slug.en'));
        $this->assertEquals($expectedNameError, $validation->errors()->first('form_name.en'));
    }

    /** @test */
    public function it_returns_a_custom_error_message_when_validating_an_array()
    {
        Model::create([
            'slug' => ['en' => 'existing-slug-en'],
            'name' => ['en' => 'existing-name-en'],
        ]);

        $rules = [
            'form_slug.*' => "{$this->rule}:{$this->table},slug",
            'form_name.*' => UniqueTranslationRule::for($this->table, 'name'),
        ];

        $messages = [
            "form_slug.*.{$this->rule}" => 'Custom slug message for :attribute.',
            "form_name.*.{$this->rule}" => 'Custom name message for :attribute.',
        ];

        $validation = Validator::make([
            'form_slug' => ['en' => 'existing-slug-en'],
            'form_name' => ['en' => 'existing-name-en'],
        ], $rules, $messages);

        $expectedSlugError = 'Custom slug message for form slug.';
        $expectedNameError = 'Custom name message for form name.';

        $this->assertEquals($expectedSlugError, $validation->errors()->first('form_slug'));
        $this->assertEquals($expectedNameError, $validation->errors()->first('form_name'));

        $this->assertEquals($expectedSlugError, $validation->errors()->first('form_slug.en'));
        $this->assertEquals($expectedNameError, $validation->errors()->first('form_name.en'));
    }
}
