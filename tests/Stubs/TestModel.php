<?php

namespace CodeZero\UniqueTranslation\Tests\Stubs;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class TestModel extends Model
{
    use HasTranslations;

    public $translatable = ['slug'];

    protected $table = 'test_models';

    protected $guarded = [];

    public $timestamps = false;
}
