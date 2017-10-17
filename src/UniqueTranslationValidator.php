<?php

namespace CodeZero\UniqueTranslation;

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
        $table = $parameters[0] ?? null;
        $column = $parameters[1] ?? null;
        $ignoreValue = $parameters[2] ?? null;
        $ignoreColumn = $parameters[3] ?? 'id';

        $rule = new UniqueTranslationRule($table, $column);

        if ($ignoreValue !== null) {
            $rule->ignore($ignoreValue, $ignoreColumn);
        }

        $passes = $rule->passes($attribute, $value);

        $validator->setCustomMessages([
            'unique_translation' => $rule->message(),
        ]);

        return $passes;
    }
}
