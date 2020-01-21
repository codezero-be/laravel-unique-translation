<?php

namespace CodeZero\UniqueTranslation\Tests;

use CodeZero\UniqueTranslation\Tests\Stubs\Model;
use CodeZero\UniqueTranslation\UniqueTranslationRule;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Validator;

class UniqueTranslationTest extends TestCase
{
    /** @test */
    public function it_checks_if_the_translation_for_the_current_locale_is_unique()
    {
        Model::create([
            'slug' => ['en' => 'existing-slug-en', 'nl' => 'existing-slug-nl'],
            'name' => ['en' => 'existing-name-en', 'nl' => 'existing-name-nl'],
        ]);

        $rules = [
            'slug' => "{$this->rule}:{$this->table}",
            'name' => UniqueTranslationRule::for($this->table),
        ];

        // The following validation fails, because the
        // current locale is "en", so we actually set
        // ['en' => 'existing-slug-en'] etc.

        $validation = Validator::make([
            'slug' => 'existing-slug-en',
            'name' => 'existing-name-en',
        ], $rules);

        $this->assertTrue($validation->fails());
        $this->assertEquals(['slug', 'slug.en', 'name', 'name.en'], $validation->errors()->keys());

        // The following validation passes, because the
        // current locale is "en", so we actually set
        // ['en' => 'existing-slug-nl'] etc.

        $validation = Validator::make([
            'slug' => 'existing-slug-nl',
            'name' => 'existing-name-nl',
        ], $rules);

        $this->assertTrue($validation->passes());
        $this->assertEmpty($validation->errors()->keys());
    }

    /** @test */
    public function it_checks_if_the_translation_for_a_specific_locale_is_unique()
    {
        Model::create([
            'slug' => ['en' => 'existing-slug-en', 'nl' => 'existing-slug-nl'],
            'name' => ['en' => 'existing-name-en', 'nl' => 'existing-name-nl'],
        ]);

        $rules = [
            'slug.*' => "{$this->rule}:{$this->table}",
            'name.*' => UniqueTranslationRule::for($this->table),
        ];

        $validation = Validator::make([
            'slug' => ['en' => 'existing-slug-en'],
            'name' => ['en' => 'existing-name-en'],
        ], $rules);

        $this->assertTrue($validation->fails());
        $this->assertEquals(['slug', 'slug.en', 'name', 'name.en'], $validation->errors()->keys());

        $validation = Validator::make([
            'slug' => ['en' => 'different-slug-en'],
            'name' => ['en' => 'different-name-en'],
        ], $rules);

        $this->assertTrue($validation->passes());
        $this->assertEmpty($validation->errors()->keys());

        $validation = Validator::make([
            'slug' => ['nl' => 'existing-slug-nl'],
            'name' => ['nl' => 'existing-name-nl'],
        ], $rules);

        $this->assertTrue($validation->fails());
        $this->assertEquals(['slug', 'slug.nl', 'name', 'name.nl'], $validation->errors()->keys());

        $validation = Validator::make([
            'slug' => ['nl' => 'different-slug-en'],
            'name' => ['nl' => 'different-name-en'],
        ], $rules);

        $this->assertTrue($validation->passes());
        $this->assertEmpty($validation->errors()->keys());
    }

    /** @test */
    public function a_database_connection_can_be_specified()
    {
        Model::create([
            'slug' => ['en' => 'existing-slug-en'],
            'name' => ['en' => 'existing-name-en'],
        ]);

        $connection = Config::get('database.default');

        $rules = [
            'slug' => "{$this->rule}:{$connection}.{$this->table}",
            'name' => UniqueTranslationRule::for("{$connection}.{$this->table}"),
        ];

        $validation = Validator::make([
            'slug' => 'existing-slug-en',
            'name' => 'existing-name-en',
        ], $rules);

        $this->assertTrue($validation->fails());
        $this->assertEquals(['slug', 'slug.en', 'name', 'name.en'], $validation->errors()->keys());

        $validation = Validator::make([
            'slug' => 'different-slug-en',
            'name' => 'different-name-en',
        ], $rules);

        $this->assertTrue($validation->passes());
        $this->assertEmpty($validation->errors()->keys());
    }

    /** @test */
    public function the_models_attribute_name_can_be_specified()
    {
        Model::create([
            'slug' => ['en' => 'existing-slug-en', 'nl' => 'existing-slug-nl'],
            'name' => ['en' => 'existing-name-en', 'nl' => 'existing-name-nl'],
        ]);

        $rules = [
            'form_slug' => "{$this->rule}:{$this->table},slug",
            'form_name' => UniqueTranslationRule::for($this->table, 'name'),
        ];

        $validation = Validator::make([
            'form_slug' => 'existing-slug-en',
            'form_name' => 'existing-name-en',
        ], $rules);

        $this->assertTrue($validation->fails());
        $this->assertEquals(['form_slug', 'form_slug.en', 'form_name', 'form_name.en'], $validation->errors()->keys());

        $rules = [
            'form_slug.*' => "{$this->rule}:{$this->table},slug",
            'form_name.*' => UniqueTranslationRule::for($this->table, 'name'),
        ];

        $validation = Validator::make([
            'form_slug' => ['nl' => 'existing-slug-nl'],
            'form_name' => ['nl' => 'existing-name-nl'],
        ], $rules);

        $this->assertTrue($validation->fails());
        $this->assertEquals(['form_slug', 'form_slug.nl', 'form_name', 'form_name.nl'], $validation->errors()->keys());
    }

    /** @test */
    public function it_ignores_the_given_id()
    {
        $model = Model::create([
            'slug' => ['en' => 'existing-slug-en', 'nl' => 'existing-slug-nl'],
            'name' => ['en' => 'existing-name-en', 'nl' => 'existing-name-nl'],
        ]);

        $rules = [
            'slug' => "{$this->rule}:{$this->table},null,{$model->id}",
            'name' => UniqueTranslationRule::for($this->table)->ignore($model->id),
        ];

        $validation = Validator::make([
            'slug' => 'existing-slug-en',
            'name' => 'existing-name-en',
        ], $rules);

        $this->assertTrue($validation->passes());
        $this->assertEmpty($validation->errors()->keys());

        $rules = [
            'slug.*' => "{$this->rule}:{$this->table},null,{$model->id}",
            'name.*' => UniqueTranslationRule::for($this->table)->ignore($model->id),
        ];

        $validation = Validator::make([
            'slug' => ['en' => 'existing-slug-en', 'nl' => 'existing-slug-nl'],
            'name' => ['en' => 'existing-name-en', 'nl' => 'existing-name-nl'],
        ], $rules);

        $this->assertTrue($validation->passes());
        $this->assertEmpty($validation->errors()->keys());
    }

    /** @test */
    public function it_ignores_a_specific_attribute_with_the_given_value()
    {
        $model = Model::create([
            'slug' => ['en' => 'existing-slug-en', 'nl' => 'existing-slug-nl'],
            'name' => ['en' => 'existing-name-en', 'nl' => 'existing-name-nl'],
            'other_field' => 'foobar',
        ]);

        $rules = [
            'slug' => "{$this->rule}:{$this->table},null,{$model->other_field},other_field",
            'name' => UniqueTranslationRule::for($this->table)->ignore($model->other_field, 'other_field'),
        ];

        $validation = Validator::make([
            'slug' => 'existing-slug-en',
            'name' => 'existing-name-en',
        ], $rules);

        $this->assertTrue($validation->passes());
        $this->assertEmpty($validation->errors()->keys());

        $rules = [
            'slug.*' => "{$this->rule}:{$this->table},null,{$model->other_field},other_field",
            'name.*' => UniqueTranslationRule::for($this->table)->ignore($model->other_field, 'other_field'),
        ];

        $validation = Validator::make([
            'slug' => ['nl' => 'existing-slug-nl'],
            'name' => ['nl' => 'existing-name-nl'],
        ], $rules);

        $this->assertTrue($validation->passes());
        $this->assertEmpty($validation->errors()->keys());
    }

    /** @test */
    public function it_ignores_null_values()
    {
        Model::create([
            'slug' => ['en' => null, 'nl' => 'existing-slug-nl'],
            'name' => ['en' => null, 'nl' => 'existing-name-nl'],
        ]);

        $rules = [
            'slug.*' => "{$this->rule}:{$this->table}",
            'name.*' => UniqueTranslationRule::for($this->table),
        ];

        $validation = Validator::make([
            'slug' => ['en' => null],
            'name' => ['en' => null],
        ], $rules);

        $this->assertTrue($validation->passes());
        $this->assertEmpty($validation->errors()->keys());
    }
}
