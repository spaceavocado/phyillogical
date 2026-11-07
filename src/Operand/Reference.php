<?php

declare(strict_types=1);

namespace Illogical\Operand;

use Illogical\Evaluable;
use Illogical\FlattenContext;

/**
 * Reference operand, i.e. the value is resolved from the evaluation data context.
 *
 * Supported address forms:
 * - nested property:            `address.city`
 * - list item:                  `options[1]`
 * - list item via reference:    `options[{index}]`
 * - nested referencing:         `address.{segment}`
 * - composite reference key:    `shape{shapeType}`
 * - data type casting:          `age.(String)`
 */
final class Reference implements Evaluable
{
    private const NESTED_REFERENCE_RX = '/{([^{}]+)}/';

    public readonly DataType $dataType;
    public readonly string $path;

    /** @var \Closure(string): string */
    private readonly \Closure $serializeTo;

    /**
     * @param string $address Reference address, annotation (e.g. "$") stripped form.
     * @param null|callable(string): string $serializeTo A function used to transform
     *     the address back into the annotated reference form, e.g. "ref" => "$ref".
     * @param array<string> $ignoredPaths Reference paths which should be ignored while
     *     simplification is applied. Must be an exact match.
     * @param array<string> $ignoredPathsRx Reference paths which should be ignored while
     *     simplification is applied. PCRE patterns incl. delimiters, e.g. "/^prefix/".
     *
     * @throws UnsupportedDataType
     */
    public function __construct(
        public readonly string $address,
        ?callable $serializeTo = null,
        public readonly array $ignoredPaths = [],
        public readonly array $ignoredPathsRx = [],
    ) {
        $this->serializeTo = $serializeTo !== null
            ? \Closure::fromCallable($serializeTo)
            : self::defaultSerializeTo(...);
        $this->dataType = DataType::fromAddress($address);
        $this->path = DataType::trim($address);

        if ($this->dataType === DataType::Unsupported) {
            throw new UnsupportedDataType("unsupported type casting, {$address}");
        }
    }

    public function evaluate(array|object $context): mixed
    {
        [, , $value] = self::resolve(FlattenContext::from($context), $this->path, $this->dataType);

        return $value;
    }

    public function simplify(array|object $context): mixed
    {
        [$found, $path, $value] = self::resolve(FlattenContext::from($context), $this->path, $this->dataType);

        if ($found && !self::isIgnoredPath($path, $this->ignoredPaths, $this->ignoredPathsRx)) {
            return $value;
        }

        return $this;
    }

    public function serialize(): string
    {
        $path = $this->path;
        if ($this->dataType !== DataType::Undefined) {
            $path = "{$path}.({$this->dataType->value})";
        }

        return ($this->serializeTo)($path);
    }

    public function __toString(): string
    {
        return '{' . $this->address . '}';
    }

    /**
     * Default deserialization, i.e. "$ref" => "ref". Returns null if the operand
     * is not a reference.
     */
    public static function defaultSerializeFrom(string $operand): ?string
    {
        return strlen($operand) > 1 && str_starts_with($operand, '$') ? substr($operand, 1) : null;
    }

    /**
     * Default serialization, i.e. "ref" => "$ref".
     */
    public static function defaultSerializeTo(string $address): string
    {
        return '$' . $address;
    }

    /**
     * Is ignored path by simplification options (ignored paths, ignored paths rx)
     * predicate.
     *
     * @param array<string> $ignoredPaths
     * @param array<string> $ignoredPathsRx
     */
    public static function isIgnoredPath(string $path, array $ignoredPaths = [], array $ignoredPathsRx = []): bool
    {
        if (in_array($path, $ignoredPaths, true)) {
            return true;
        }

        foreach ($ignoredPathsRx as $pattern) {
            if (preg_match($pattern, $path) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * Try to get a value from the context based on the reference path. Nested
     * references, i.e. `{path}`, are resolved first.
     *
     * @return array{bool, string, mixed} [found, resolved path, value]
     */
    public static function contextLookup(FlattenContext $context, string $path): array
    {
        while (preg_match(self::NESTED_REFERENCE_RX, $path, $match, PREG_OFFSET_CAPTURE) === 1) {
            [$found, , $value] = self::contextLookup($context, $match[1][0]);
            if (!$found) {
                return [false, $path, null];
            }

            $path = substr($path, 0, $match[0][1])
                . DataType::toString($value)
                . substr($path, $match[0][1] + strlen($match[0][0]));
        }

        if ($context->has($path)) {
            return [true, $path, $context->get($path)];
        }

        return [false, $path, null];
    }

    /**
     * Evaluate the reference at the given path from the context. Resolved value
     * is cast, if a data type is provided.
     *
     * @return array{bool, string, mixed} [found, resolved path, value]
     */
    public static function resolve(FlattenContext $context, string $path, DataType $dataType): array
    {
        [$found, $resolvedPath, $value] = self::contextLookup($context, $path);

        if ($value !== null) {
            $value = $dataType->cast($value);
        }

        return [$found, $resolvedPath, $value];
    }
}
