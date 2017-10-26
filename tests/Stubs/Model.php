<?php

namespace CodeZero\UniqueTranslation\Tests\Stubs;

use Illuminate\Database\Eloquent\Model as EloquentModel;
use Spatie\Translatable\HasTranslations;

class Model extends EloquentModel
{
    use HasTranslations;

    public $translatable = ['slug', 'name'];

    protected $table = 'test_models';

    protected $guarded = [];

    public $timestamps = false;
}
