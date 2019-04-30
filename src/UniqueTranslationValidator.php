<?php

namespace CodeZero\UniqueTranslation;

use App;
use Config;
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
    public function validate($attribute, $value, $parameters, $validator)
    {
        list ($name, $locale) = $this->getAttributeNameAndLocale($attribute);

        if ($this->isUnique($value, $name, $locale, $parameters)) {
            return true;
        }

        $this->addErrorsToValidator($validator, $parameters, $name, $locale);

        return false;
    }

    /**
     * Get the attribute name and locale.
     *
     * @param string $attribute
     *
     * @return array
     */
    protected function getAttributeNameAndLocale($attribute)
    {
        $parts = explode('.', $attribute);

        $name = $parts[0];
        $locale = $parts[1] ?? App::getLocale();

        return [$name, $locale];
    }

    /**
     * Get the database connection and table name.
     *
     * @param array $parameters
     *
     * @return array
     */
    protected function getConnectionAndTable($parameters)
    {
        $parts = explode('.', $this->getParameter($parameters, 0));

        $connection = isset($parts[1])
            ? $parts[0]
            : Config::get('database.default');

        $table = $parts[1] ?? $parts[0];

        return [$connection, $table];
    }

    /**
     * Get the parameter value at the given index.
     *
     * @param array $parameters
     * @param int $index
     *
     * @return string|null
     */
    protected function getParameter($parameters, $index)
    {
        return $this->convertNullValue($parameters[$index] ?? null);
    }

    /**
     * Convert any 'NULL' string value to null.
     *
     * @param string $value
     *
     * @return string|null
     */
    protected function convertNullValue($value)
    {
        return strtoupper($value) === 'NULL' ? null : $value;
    }

    /**
     * Check if a translation is unique.
     *
     * @param mixed $value
     * @param string $name
     * @param string $locale
     * @param array $parameters
     *
     * @return bool
     */
    protected function isUnique($value, $name, $locale, $parameters)
    {
        list ($connection, $table) = $this->getConnectionAndTable($parameters);

        $column = $this->getParameter($parameters, 1) ?? $name;
        $ignoreValue = $this->getParameter($parameters, 2);
        $ignoreColumn = $this->getParameter($parameters, 3);

        $query = $this->findTranslation($connection, $table, $column, $locale, $value);
        $query = $this->ignore($query, $ignoreColumn, $ignoreValue);

        $isUnique = $query->count() === 0;

        return $isUnique;
    }

    /**
     * Find the given translated value in the database.
     *
     * @param string $connection
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
