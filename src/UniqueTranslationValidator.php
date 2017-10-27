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
        $attribute = $attributeParts[0];
        $locale = $attributeParts[1] ?? app()->getLocale();
        $table = $parameters[0] ?? null;
        $column = $parameters[1] ?? null;
        $ignoreValue = $parameters[2] ?? null;
        $ignoreColumn = $parameters[3] ?? null;

        $isUnique = $this->isUnique($attribute, $locale, $value, $table, $column, $ignoreValue, $ignoreColumn);

        $validator->setCustomMessages([
            'unique_translation' => trans('validation.unique'),
        ]);

        return $isUnique;
    }

    /**
     * Check if a translation is unique.
     *
     * @param string $attribute
     * @param string $locale
     * @param mixed $value
     * @param string $table
     * @param string|null $column
     * @param mixed $ignoreValue
     * @param string|null $ignoreColumn
     *
     * @return bool
     */
    protected function isUnique($attribute, $locale, $value, $table, $column = null, $ignoreValue = null, $ignoreColumn = null)
    {
        $column = $column ?: $attribute;

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
}
