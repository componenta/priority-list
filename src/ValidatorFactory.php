<?php

declare(strict_types=1);

namespace Componenta\Stdlib;

/**
 * Factory for creating item validators.
 */
final class ValidatorFactory
{
    /**
     * @return callable(mixed): bool
     */
    public static function string(): \Closure
    {
        return is_string(...);
    }

    /**
     * @return callable(mixed): bool
     */
    public static function int(): \Closure
    {
        return is_int(...);
    }

    /**
     * @return callable(mixed): bool
     */
    public static function float(): \Closure
    {
        return is_float(...);
    }

    /**
     * @return callable(mixed): bool
     */
    public static function numeric(): \Closure
    {
        return is_numeric(...);
    }

    /**
     * @return callable(mixed): bool
     */
    public static function bool(): \Closure
    {
        return is_bool(...);
    }

    /**
     * @return callable(mixed): bool
     */
    public static function array(): \Closure
    {
        return is_array(...);
    }

    /**
     * @return callable(mixed): bool
     */
    public static function object(): \Closure
    {
        return is_object(...);
    }

    /**
     * @return callable(mixed): bool
     */
    public static function callable(): \Closure
    {
        return is_callable(...);
    }

    /**
     * @return callable(mixed): bool
     */
    public static function iterable(): \Closure
    {
        return is_iterable(...);
    }

    /**
     * @return callable(mixed): bool
     */
    public static function scalar(): \Closure
    {
        return is_scalar(...);
    }

    /**
     * @param class-string $className
     * @return callable(mixed): bool
     */
    public static function instanceOf(string $className): \Closure
    {
        return static fn(mixed $value): bool => $value instanceof $className;
    }

    /**
     * Combines multiple validators with AND logic.
     *
     * @param callable(mixed): bool ...$validators
     * @return callable(mixed): bool
     */
    public static function all(callable ...$validators): \Closure
    {
        return static function (mixed $value) use ($validators): bool {
            return array_all($validators, static fn(callable $validator) => $validator($value));
        };
    }

    /**
     * Combines multiple validators with OR logic.
     *
     * @param callable(mixed): bool ...$validators
     * @return callable(mixed): bool
     */
    public static function any(callable ...$validators): \Closure
    {
        return static function (mixed $value) use ($validators): bool {
            return array_any($validators, static fn(callable $validator) => $validator($value));
        };
    }
}
