<?php

declare(strict_types=1);

namespace Illogical\Operand;

/**
 * Referenced value data type, i.e. type casting annotation of a reference,
 * e.g. `$age.(String)`.
 */
enum DataType: string
{
    case Undefined = 'Undefined';
    case Unsupported = 'Unsupported';
    case Number = 'Number';
    case Integer = 'Integer';
    case Float = 'Float';
    case String = 'String';
    case Boolean = 'Boolean';

    private const DATA_TYPE_RX = '/^.+\.\(([A-Z][a-z]+)\)$/';
    private const DATA_TYPE_TRIM_RX = '/.\(([A-Z][a-z]+)\)$/';
    private const FLOAT_RX = '/^\d+\.\d+$/';
    private const FLOAT_TRIM_RX = '/\.\d+$/';
    private const INT_RX = '/^0$|^[1-9]\d*$/';

    /**
     * Get the data type from the reference address, if present.
     */
    public static function fromAddress(string $address): self
    {
        if (preg_match(self::DATA_TYPE_RX, $address, $match) !== 1) {
            return self::Undefined;
        }

        return match ($match[1]) {
            self::Number->value => self::Number,
            self::Integer->value => self::Integer,
            self::Float->value => self::Float,
            self::String->value => self::String,
            self::Boolean->value => self::Boolean,
            default => self::Unsupported,
        };
    }

    /**
     * Trim the data type annotation from the reference address.
     */
    public static function trim(string $address): string
    {
        return (string) preg_replace(self::DATA_TYPE_TRIM_RX, '', $address);
    }

    /**
     * Cast the value into the data type. Values which could not be converted
     * are returned as they are.
     */
    public function cast(mixed $value): mixed
    {
        return match ($this) {
            self::Number => self::toNumber($value),
            self::Integer => self::toInt($value),
            self::Float => self::toFloat($value),
            self::String => self::toString($value),
            self::Boolean => self::toBoolean($value),
            default => $value,
        };
    }

    /**
     * Convert value to number if possible. Boolean is converted to 1, 0.
     */
    public static function toNumber(mixed $value): mixed
    {
        if (is_bool($value)) {
            return $value ? 1 : 0;
        }

        if (is_int($value) || is_float($value)) {
            return $value;
        }

        if (is_string($value)) {
            if (preg_match(self::FLOAT_RX, $value) === 1) {
                return (float) $value;
            }
            if (preg_match(self::INT_RX, $value) === 1) {
                return (int) $value;
            }
        }

        return $value;
    }

    /**
     * Convert value to int if possible. Boolean is converted to 1, 0.
     * Float is truncated.
     */
    public static function toInt(mixed $value): mixed
    {
        if (is_bool($value)) {
            return $value ? 1 : 0;
        }

        if (is_int($value) || is_float($value)) {
            return (int) $value;
        }

        if (is_string($value)) {
            if (preg_match(self::FLOAT_RX, $value) === 1) {
                return (int) preg_replace(self::FLOAT_TRIM_RX, '', $value);
            }
            if (preg_match(self::INT_RX, $value) === 1) {
                return (int) $value;
            }
        }

        return $value;
    }

    /**
     * Convert value to float if possible. Boolean is converted to 1.0, 0.0.
     */
    public static function toFloat(mixed $value): mixed
    {
        if (is_bool($value) || is_int($value) || is_float($value)) {
            return (float) $value;
        }

        if (is_string($value)) {
            if (preg_match(self::INT_RX, $value) === 1 || preg_match(self::FLOAT_RX, $value) === 1) {
                return (float) $value;
            }
        }

        return $value;
    }

    /**
     * Convert value to string. Boolean is converted to "true", "false".
     */
    public static function toString(mixed $value): string
    {
        return match (true) {
            is_string($value) => $value,
            is_bool($value) => $value ? 'true' : 'false',
            is_scalar($value) => (string) $value,
            default => \Illogical\Primitive::toString($value),
        };
    }

    /**
     * Convert value to boolean if possible. Number is converted to true if equal 1,
     * text "true", "1" (case-insensitive) to true, "false", "0" to false.
     * Anything else is converted to false.
     */
    public static function toBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return $value == 1;
        }

        if (is_string($value)) {
            return in_array(strtolower(trim($value)), ['true', '1'], true);
        }

        return false;
    }
}
