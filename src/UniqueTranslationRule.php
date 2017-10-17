<?php

namespace CodeZero\UniqueTranslation;

use DB;
use Illuminate\Contracts\Validation\Rule;

class UniqueTranslationRule implements Rule
{
    /**
     * @var string
     */
    protected $table;

    /**
     * @var string
     */
    protected $column;

    /**
     * @var string
     */
    protected $attribute;

    /**
     * @var mixed
     */
    protected $ignoreValue;

    /**
     * @var string
     */
    protected $ignoreColumn;

    /**
     * Create a new rule instance.
     *
     * @param string $table
     * @param string|null $column
     */
    public function __construct($table, $column = null)
    {
        $this->table = $table;
        $this->column = $column;
    }

    /**
     * Ignore any record that has a column with the given value.
     *
     * @param mixed $value
     * @param string $column
     *
     * @return $this
     */
    public function ignore($value, $column = 'id')
    {
        $this->ignoreValue = $value;
        $this->ignoreColumn = $column;

        return $this;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     * @param string $value
     *
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $attributes = explode('.', $attribute);
        $this->attribute = $attributes[0];
        $this->column = $this->column ?: $attributes[0];
        $locale = $attributes[1] ?? app()->getLocale();

        return $this->isUnique($this->column, $locale, $value);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return trans('validation.unique', [
            'attribute' => $this->attribute,
        ]);
    }

    /**
     * Check if the given translated value does not exist in the database.
     *
     * @param string $column
     * @param string $locale
     * @param string $value
     *
     * @return bool
     */
    protected function isUnique($column, $locale, $value)
    {
        $query = DB::table($this->table)->where("{$column}->{$locale}", '=', $value);

        if ($this->ignoreColumn !== null) {
            $query = $query->where($this->ignoreColumn, '!=', $this->ignoreValue);
        }

        return $query->count() === 0;
    }
}
