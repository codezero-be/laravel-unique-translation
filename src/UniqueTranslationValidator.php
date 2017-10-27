<?php

namespace CodeZero\UniqueTranslation;

use DB;

class UniqueTranslationValidator
{
    /**
     * Check if the translated value is unique in the database.
     *
     * @param string $attribute
     * @param string $value
     * @param array $parameters
     * @param \Illuminate\Validation\Validator $validator
     *
     * @return bool
     */
    public function validate($attribute, $value, $parameters, $validator) {
        $attributeParts = explode('.', $attribute);
        $name = $attributeParts[0];
        $locale = $attributeParts[1] ?? app()->getLocale();
        $table = $parameters[0] ?? null;
        $column = $this->filterNullValues($parameters[1] ?? null) ?: $name;
        $ignoreValue = $this->filterNullValues($parameters[2] ?? null);
        $ignoreColumn = $this->filterNullValues($parameters[3] ?? null);

        $isUnique = $this->isUnique($value, $locale, $table, $column, $ignoreValue, $ignoreColumn);

        if ( ! $isUnique) {
            $this->addErrorsToValidator($validator, $attribute, $parameters, $name, $locale);
        }

        return $isUnique;
    }

    /**
     * Filter NULL values.
     *
     * @param string|null $value
     *
     * @return string|null
     */
    protected function filterNullValues($value)
    {
        $nullValues = ['null', 'NULL'];

        if (in_array($value, $nullValues)) {
            return null;
        }

        return $value;
    }

    /**
     * Check if a translation is unique.
     *
     * @param mixed $value
     * @param string $locale
     * @param string $table
     * @param string $column
     * @param mixed $ignoreValue
     * @param string|null $ignoreColumn
     *
     * @return bool
     */
    protected function isUnique($value, $locale, $table, $column, $ignoreValue = null, $ignoreColumn = null)
    {
        $query = $this->findTranslation($table, $column, $locale, $value);
        $query = $this->ignore($query, $ignoreColumn, $ignoreValue);

        $isUnique = $query->count() === 0;

        return $isUnique;
    }

    /**
     * Find the given translated value in the database.
     *
     * @param string $table
     * @param string $column
     * @param string $locale
     * @param mixed $value
     *
     * @return \Illuminate\Database\Query\Builder
     */
    protected function findTranslation($table, $column, $locale, $value)
    {
        return DB::table($table)->where("{$column}->{$locale}", '=', $value);
    }

    /**
     * Ignore the column with the given value.
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @param string|null $column
     * @param mixed $value
     *
     * @return \Illuminate\Database\Query\Builder
     */
    protected function ignore($query, $column = null, $value = null)
    {
        if ($value !== null && $column === null) {
            $column = 'id';
        }

        if ($column !== null) {
            $query = $query->where($column, '!=', $value);
        }

        return $query;
    }

    /**
     * Add error messages to the validator.
     *
     * @param \Illuminate\Validation\Validator $validator
     * @param string $attribute
     * @param array $parameters
     * @param string $name
     * @param string $locale
     *
     * @return void
     */
    protected function addErrorsToValidator($validator, $attribute, $parameters, $name, $locale)
    {
        $message = trans('validation.unique');
        $rule = 'unique_translation';

        // This Validator method will format the placeholders:
        // eg. "post_slug" will become "post slug".
        $formattedMessage = $validator->makeReplacements($message, $attribute, $rule, $parameters);

        $validator->errors()
            ->add($name, $formattedMessage)
            ->add("{$name}.{$locale}", $formattedMessage);
    }
}
