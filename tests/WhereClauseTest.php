<?php

namespace CodeZero\UniqueTranslation\Tests;

use CodeZero\UniqueTranslation\Tests\Stubs\Model;
use CodeZero\UniqueTranslation\UniqueTranslationRule;
use Validator;

// * * *
// You can use any method defined in the DatabaseRule
// trait, except the whereIn and whereNotIn methods.
//
// https://laravel.com/api/5.8/Illuminate/Validation/Rules/DatabaseRule.html
//
// This is because it uses a closure which cannot be converted into a string.
// We need to convert the rule into a string to use it with the UniqueTranslationValidator.
// The reason we use this kind of validator is because it has access to the Validator instance.
// We need that instance to add custom error messages.
// * * *

class WhereClauseTestTest extends TestCase
{
    /** @test */
    public function it_accepts_where_clause()
    {
        Model::create([
            'slug' => ['en' => 'existing-slug-en'],
            'name' => ['en' => 'existing-name-en'],
            'other_field' => 'foobar',
        ]);

        $rules = [
            'slug.*' => "{$this->rule}:{$this->table},null,null,null,other_field,!foobar",
            'name.*' => UniqueTranslationRule::for($this->table)->where('other_field', 'not foobar'),
        ];

        $validation = Validator::make([
            'slug' => ['en' => 'existing-slug-en'],
            'name' => ['en' => 'existing-name-en'],
        ], $rules);

        $this->assertTrue($validation->passes());
        $this->assertEmpty($validation->errors()->keys());
    }

    /** @test */
    public function it_accepts_where_not_clauses()
    {
        Model::create([
            'slug' => ['en' => 'existing-slug-en'],
            'name' => ['en' => 'existing-name-en'],
            'other_field' => 'foobar',
        ]);

        $rules = [
            'slug.*' => "{$this->rule}:{$this->table},null,null,null,other_field,!foobar",
            'name.*' => UniqueTranslationRule::for($this->table)->whereNot('other_field', 'foobar'),
        ];

        $validation = Validator::make([
            'slug' => ['en' => 'existing-slug-en'],
            'name' => ['en' => 'existing-name-en'],
        ], $rules);

        $this->assertTrue($validation->passes());
        $this->assertEmpty($validation->errors()->keys());
    }

    /** @test */
    public function it_accepts_where_null_clause()
    {
        Model::create([
            'slug' => ['en' => 'existing-slug-en'],
            'name' => ['en' => 'existing-name-en'],
            'other_field' => 'foobar',
        ]);

        $rules = [
            'slug.*' => "{$this->rule}:{$this->table},null,null,null,other_field,NULL",
            'name.*' => UniqueTranslationRule::for($this->table)->whereNull('other_field'),
        ];

        $validation = Validator::make([
            'slug' => ['en' => 'existing-slug-en'],
            'name' => ['en' => 'existing-name-en'],
        ], $rules);

        $this->assertTrue($validation->passes());
        $this->assertEmpty($validation->errors()->keys());
    }

    /** @test */
    public function it_accepts_where_not_null_clause()
    {
        Model::create([
            'slug' => ['en' => 'existing-slug-en'],
            'name' => ['en' => 'existing-name-en'],
            'other_field' => null,
        ]);

        $rules = [
            'slug.*' => "{$this->rule}:{$this->table},null,null,null,other_field,NOT_NULL",
            'name.*' => UniqueTranslationRule::for($this->table)->whereNotNull('other_field'),
        ];

        $validation = Validator::make([
            'slug' => ['en' => 'existing-slug-en'],
            'name' => ['en' => 'existing-name-en'],
        ], $rules);

        $this->assertTrue($validation->passes());
        $this->assertEmpty($validation->errors()->keys());
    }
}
