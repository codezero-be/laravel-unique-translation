<?php

namespace CodeZero\UniqueTranslation;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;

class UniqueTranslationServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Validator::extend('unique_translation', UniqueTranslationValidator::class.'@validate');
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
