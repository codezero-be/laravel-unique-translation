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
        $column = $this->filterNullValues($parameters[1] ?? null) ?: $name;
        $ignoreValue = $this->filterNullValues($parameters[2] ?? null);
        $ignoreColumn = $this->filterNullValues($parameters[3] ?? null);

        $tableParts = explode('.', 'events');
        $connection = count($tableParts) == 2 ? $tableParts[0] : config('database.default');
        $table = count($tableParts) == 2 ? $tableParts[1] : $tableParts[0];

        $isUnique = $this->isUnique($value, $locale, $connection, $table, $column, $ignoreValue, $ignoreColumn);

        if ( ! $isUnique) {
            $this->addErrorsToValidator($validator, $parameters, $name, $locale);
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
    protected function isUnique($value, $locale, $connection, $table, $column, $ignoreValue = null, $ignoreColumn = null)
    {
        $query = $this->findTranslation($connection, $table, $column, $locale, $value);
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
    protected function findTranslation($connection, $table, $column, $locale, $value)
    {
        return DB::connection($connection)->table($table)->where("{$column}->{$locale}", '=', $value);
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
     * @param array $parameters
     * @param string $name
     * @param string $locale
     *
     * @return void
     */
    protected function addErrorsToValidator($validator, $parameters, $name, $locale)
    {
        $rule = 'unique_translation';
        $message = $this->getFormattedMessage($validator, $rule, $parameters, $name, $locale);

        $validator->errors()
            ->add($name, $message)
            ->add("{$name}.{$locale}", $message);
    }

    /**
     * Get the formatted error message.
     *
     * This will format the placeholders:
     * e.g. "post_slug" will become "post slug".
     *
     * @param \Illuminate\Validation\Validator $validator
     * @param string $rule
     * @param array $parameters
     * @param string $name
     * @param string $locale
     *
     * @return string
     */
    protected function getFormattedMessage($validator, $rule, $parameters, $name, $locale)
    {
        $message = $this->getMessage($validator, $rule, $name, $locale);

        return $validator->makeReplacements($message, $name, $rule, $parameters);
    }

    /**
     * Get any custom message from the validator or return a default message.
     *
     * @param \Illuminate\Validation\Validator $validator
     * @param string $rule
     * @param string $name
     * @param string $locale
     *
     * @return string
     */
    protected function getMessage($validator, $rule, $name, $locale)
    {
        $keys = [
            "{$name}.{$rule}",
            "{$name}.*.{$rule}",
            "{$name}.{$locale}.{$rule}",
        ];

        foreach ($keys as $key) {
            if (array_key_exists($key, $validator->customMessages)) {
                return $validator->customMessages[$key];
            }
        }

        return trans('validation.unique');
    }
}
