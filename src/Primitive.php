<?php

declare(strict_types=1);

namespace Illogical;

/**
 * Primitive value helpers.
 *
 * @internal
 */
final class Primitive
{
    /**
     * Is subject a primitive value (string, int, float, bool, null) predicate.
     */
    public static function is(mixed $subject): bool
    {
        return $subject === null || is_scalar($subject);
    }

    /**
     * Is subject a number (int, float), excluding bool, predicate.
     */
    public static function isNumber(mixed $subject): bool
    {
        return is_int($subject) || is_float($subject);
    }

    /**
     * Statement (text) form of a primitive value.
     */
    public static function toStatement(mixed $value): string
    {
        return match (true) {
            is_string($value) => "\"{$value}\"",
            default => self::toString($value),
        };
    }

    /**
     * Plain string form of a value.
     */
    public static function toString(mixed $value): string
    {
        return match (true) {
            $value === null => 'null',
            is_bool($value) => $value ? 'true' : 'false',
            is_float($value) => var_export($value, true),
            is_scalar($value) => (string) $value,
            is_array($value) => '[' . implode(', ', array_map(self::toStatement(...), $value)) . ']',
            $value instanceof \Stringable => (string) $value,
            default => get_debug_type($value),
        };
    }
}
