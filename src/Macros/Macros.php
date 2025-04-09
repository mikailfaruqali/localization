<?php

namespace Snawbar\Localization\Macros;

use InvalidArgumentException;

class Macros
{
    public static function register()
    {
        self::registerConfigMacros();
    }

    private static function registerConfigMacros()
    {
        config()->macro('string', function (string $key, $default = NULL): string {
            $value = config()->get($key, $default);

            throw_unless(is_string($value), new InvalidArgumentException(
                sprintf('Configuration value for key [%s] must be a string, %s given.', $key, gettype($value))
            ));

            return $value;
        });

        config()->macro('integer', function (string $key, $default = NULL): int {
            $value = config()->get($key, $default);

            throw_unless(is_int($value), new InvalidArgumentException(
                sprintf('Configuration value for key [%s] must be an integer, %s given.', $key, gettype($value))
            ));

            return $value;
        });

        config()->macro('float', function (string $key, $default = NULL): float {
            $value = config()->get($key, $default);

            throw_unless(is_float($value), new InvalidArgumentException(
                sprintf('Configuration value for key [%s] must be a float, %s given.', $key, gettype($value))
            ));

            return $value;
        });

        config()->macro('boolean', function (string $key, $default = NULL): bool {
            $value = config()->get($key, $default);

            throw_unless(is_bool($value), new InvalidArgumentException(
                sprintf('Configuration value for key [%s] must be a boolean, %s given.', $key, gettype($value))
            ));

            return $value;
        });

        config()->macro('array', function (string $key, $default = NULL): array {
            $value = config()->get($key, $default);

            throw_unless(is_array($value), new InvalidArgumentException(
                sprintf('Configuration value for key [%s] must be an array, %s given.', $key, gettype($value))
            ));

            return $value;
        });
    }
}
