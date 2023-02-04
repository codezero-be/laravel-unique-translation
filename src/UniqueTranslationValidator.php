<?php

namespace CodeZero\UniqueTranslation;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
        list ($name, $locale) = $this->isNovaTranslation($attribute)
            ? $this->getNovaAttributeNameAndLocale($attribute)
            : $this->getArrayAttributeNameAndLocale($attribute);

        if ($this->isUnique($value, $name, $locale, $parameters)) {
            return true;
        }

        $this->setMissingErrorMessages($validator, $name, $locale);

        return false;
    }

    /**
     * Set any missing (custom) error messages for our validation rule.
     *
     * @param \Illuminate\Validation\Validator $validator
     * @param string $name
     * @param string $locale
     *
     * @return void
     */
    protected function setMissingErrorMessages($validator, $name, $locale)
    {
        $rule = 'unique_translation';

        $keys = [
            "{$name}.{$rule}",
            "{$name}.*.{$rule}",
            "{$name}.{$locale}.{$rule}",
            "translations_{$name}_{$locale}.{$rule}",
        ];

        foreach ($keys as $key) {
            if ( ! array_key_exists($key, $validator->customMessages)) {
                $validator->customMessages[$key] = trans('validation.unique');
            }
        }
    }

    /**
     * Check if the attribute is a Nova translation field name.
     *
     * @param string $attribute
     *
     * @return bool
     */
    protected function isNovaTranslation($attribute)
    {
        return strpos($attribute, '.') === false && strpos($attribute, 'translations_') === 0;
    }

    /**
     * Get the attribute name and locale of a Nova translation field.
     *
     * @param string $attribute
     *
     * @return array
     */
    protected function getNovaAttributeNameAndLocale($attribute)
    {
        $attribute = str_replace('translations_', '', $attribute);

        return $this->getAttributeNameAndLocale($attribute, '_');
    }

    /**
     * Get the attribute name and locale of an array field.
     *
     * @param string $attribute
     *
     * @return array
     */
    protected function getArrayAttributeNameAndLocale($attribute)
    {
        return $this->getAttributeNameAndLocale($attribute, '.');
    }

    /**
     * Get the attribute name and locale.
     *
     * @param string $attribute
     * @param string $delimiter
     *
     * @return array
     */
    protected function getAttributeNameAndLocale($attribute, $delimiter)
    {
        $locale = $this->getAttributeLocale($attribute, $delimiter);
        $name = $this->getAttributeName($attribute, $locale, $delimiter);

        return [$name, $locale ?: App::getLocale()];
    }

    /**
     * Get the locale from the attribute name.
     *
     * @param string $attribute
     * @param string $delimiter
     *
     * @return string|null
     */
    protected function getAttributeLocale($attribute, $delimiter)
    {
        $pos = strrpos($attribute, $delimiter);

        return $pos > 0 ? substr($attribute, $pos +  1) : null;
    }

    /**
     * Get the attribute name without the locale.
     *
     * @param string $attribute
     * @param string|null $locale
     * @param string $delimiter
     *
     * @return string
     */
    protected function getAttributeName($attribute, $locale, $delimiter)
    {
        return $locale ? str_replace("{$delimiter}{$locale}", '', $attribute) : $attribute;
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
        $query = $this->addConditions($query, $this->getUniqueExtra($parameters));

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
        // Properly escape backslashes to work with LIKE queries...
        // See: https://stackoverflow.com/questions/14926386/how-to-search-for-slash-in-mysql-and-why-escaping-not-required-for-wher
        $value = str_replace('\\', '\\\\\\\\', $value);

        return DB::connection($connection)->table($table)
            ->where(function ($query) use ($column, $locale, $value) {
                $query->where($column, 'LIKE', "%\"{$locale}\": \"{$value}\"%")
                    ->orWhere($column, 'LIKE', "%\"{$locale}\":\"{$value}\"%");
            });
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
     * Get the extra conditions for a unique rule.
     * Taken From: \Illuminate\Validation\Concerns\ValidatesAttributes
     *
     * @param array $parameters
     *
     * @return array
     */
    protected function getUniqueExtra($parameters)
    {
        if (isset($parameters[4])) {
            return $this->getExtraConditions(array_slice($parameters, 4));
        }

        return [];
    }

    /**
     * Get the extra conditions for a unique / exists rule.
     * Taken from: \Illuminate\Validation\Concerns\ValidatesAttributes
     *
     * @param array $segments
     *
     * @return array
     */
    protected function getExtraConditions(array $segments)
    {
        $extra = [];

        $count = count($segments);

        for ($i = 0; $i < $count; $i += 2) {
            $extra[$segments[$i]] = $segments[$i + 1];
        }

        return $extra;
    }

    /**
     * Add the given conditions to the query.
     * Adapted from: \Illuminate\Validation\DatabasePresenceVerifier
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @param array $conditions
     *
     * @return \Illuminate\Database\Query\Builder
     */
    protected function addConditions($query, $conditions)
    {
        foreach ($conditions as $key => $value) {
            $this->addWhere($query, $key, $value);
        }

        return $query;
    }

    /**
     * Add a "where" clause to the given query.
     * Taken from: \Illuminate\Validation\DatabasePresenceVerifier
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @param string $key
     * @param string $extraValue
     *
     * @return \Illuminate\Database\Query\Builder
     */
    protected function addWhere($query, $key, $extraValue)
    {
        if ($extraValue === 'NULL') {
            return $query->whereNull($key);
        }

        if ($extraValue === 'NOT_NULL') {
            return $query->whereNotNull($key);
        }

        $isNegative = Str::startsWith($extraValue, '!');
        $operator = $isNegative ? '!=' : '=';
        $value = $isNegative ? mb_substr($extraValue, 1) : $extraValue;

        return $query->where($key, $operator, $value);
    }
}
