<?php

declare(strict_types=1);

namespace LaraDantic\Validation;

use Illuminate\Container\Container;
use Illuminate\Contracts\Validation\Factory;
use LaraDantic\Schema\Schema;

/**
 * Validates raw data for a schema class using Laravel's validation engine,
 * with rules inferred from the schema (or overridden via a custom `rules()` method).
 */
final class Validator
{
    /**
     * @param  class-string<Schema>  $schemaClass
     * @param  array<string, mixed>  $data
     */
    public static function validate(string $schemaClass, array $data): ValidationResult
    {
        $rules = $schemaClass::rules();

        $validator = Container::getInstance()
            ->make(Factory::class)
            ->make($data, $rules);

        if ($validator->fails()) {
            /** @var array<string, array<int, string>> $errors */
            $errors = $validator->errors()->toArray();

            return ValidationResult::failure($errors);
        }

        return ValidationResult::success();
    }
}
