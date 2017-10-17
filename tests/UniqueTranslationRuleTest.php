<?php

namespace CodeZero\UniqueTranslation\Tests;

use CodeZero\UniqueTranslation\UniqueTranslationRule;
use CodeZero\UniqueTranslation\Tests\Stubs\TestModel;

class UniqueTranslationRuleTest extends TestCase
{
    protected $table = 'test_models';
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
        $rule = new UniqueTranslationRule($this->table);

        $isUnique = $rule->passes('slug', 'post-slug-en');
        $this->assertFalse($isUnique);

        $isUnique = $rule->passes('slug', 'post-slug-nl');
        $this->assertTrue($isUnique);
    }

    /** @test */
    public function it_checks_if_the_translation_for_a_specific_locale_is_unique()
    {
        $rule = new UniqueTranslationRule($this->table);

        $isUnique = $rule->passes('slug.en', 'post-slug-en');
        $this->assertFalse($isUnique);

        $isUnique = $rule->passes('slug.nl', 'post-slug-nl');
        $this->assertFalse($isUnique);

        $isUnique = $rule->passes('slug.en', 'different-post-slug-en');
        $this->assertTrue($isUnique);

        $isUnique = $rule->passes('slug.nl', 'different-post-slug-nl');
        $this->assertTrue($isUnique);
    }

    /** @test */
    public function the_models_attribute_name_can_be_specified()
    {
        $rule = new UniqueTranslationRule($this->table, 'slug');

        $isUnique = $rule->passes('form_slug', 'post-slug-en');
        $this->assertFalse($isUnique);

        $isUnique = $rule->passes('form_slug.nl', 'post-slug-nl');
        $this->assertFalse($isUnique);
    }

    /** @test */
    public function it_ignores_the_given_id()
    {
        $rule = new UniqueTranslationRule($this->table);

        $rule->ignore($this->model->id);

        $isUnique = $rule->passes('slug', 'post-slug-en');
        $this->assertTrue($isUnique);

        $isUnique = $rule->passes('slug.nl', 'post-slug-nl');
        $this->assertTrue($isUnique);
    }

    /** @test */
    public function it_ignores_a_specific_attribute_with_the_given_value()
    {
        $rule = new UniqueTranslationRule($this->table);

        $rule->ignore($this->model->other_field, 'other_field');

        $isUnique = $rule->passes('slug', 'post-slug-en');
        $this->assertTrue($isUnique);

        $isUnique = $rule->passes('slug.nl', 'post-slug-nl');
        $this->assertTrue($isUnique);
    }

    /** @test */
    public function it_returns_the_correct_error_message()
    {
        $rule = new UniqueTranslationRule($this->table, 'slug');
        $rule->passes('form_slug', null);

        $this->assertContains('form_slug', $rule->message());
    }
}
