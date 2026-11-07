<?php

declare(strict_types=1);

namespace Illogical;

/**
 * Flatten context, i.e. 1-dimensional map of [property path => value].
 *
 * Example:
 *
 * $context = [
 *     'name'    => 'peter',
 *     'options' => [1, 2, 3],
 *     'address' => [
 *         'city'    => 'Toronto',
 *         'country' => 'Canada',
 *     ],
 * ];
 *
 * FlattenContext::from($context)->toArray();
 *
 * [
 *     'name'            => 'peter',
 *     'options[0]'      => 1,
 *     'options[1]'      => 2,
 *     'options[2]'      => 3,
 *     'address.city'    => 'Toronto',
 *     'address.country' => 'Canada',
 * ]
 */
final class FlattenContext
{
    /**
     * @param array<string, mixed> $values
     */
    private function __construct(private readonly array $values)
    {
    }

    /**
     * Flatten the given context. An already flatten context is returned as is.
     *
     * Associative arrays and objects (public properties) are flattened using the
     * "." delimiter, lists using the "[index]" notation. Scalar and null values are
     * kept, any other values (closures, resources, etc.) are ignored.
     *
     * @param array<mixed>|object $context
     */
    public static function from(array|object $context): self
    {
        if ($context instanceof self) {
            return $context;
        }

        $values = [];
        self::lookup($context, '', $values);

        return new self($values);
    }

    /**
     * Create a flatten context from an already flatten map, i.e. no flattening
     * is applied to the given values.
     *
     * @param array<string, mixed> $values
     */
    public static function fromFlatten(array $values): self
    {
        return new self($values);
    }

    /**
     * Is the path present in the context predicate.
     */
    public function has(string $path): bool
    {
        return array_key_exists($path, $this->values);
    }

    /**
     * Get a value at the given path, null if not present.
     */
    public function get(string $path): mixed
    {
        return $this->values[$path] ?? null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->values;
    }

    /**
     * @param array<string, mixed> $values
     */
    private static function lookup(mixed $subject, string $path, array &$values): void
    {
        if ($subject === null || is_scalar($subject)) {
            $values[$path] = $subject;
            return;
        }

        if (is_array($subject)) {
            if (array_is_list($subject)) {
                foreach ($subject as $index => $item) {
                    self::lookup($item, "{$path}[{$index}]", $values);
                }
                return;
            }

            foreach ($subject as $key => $item) {
                self::lookup($item, self::joinPath($path, (string) $key), $values);
            }
            return;
        }

        if (is_object($subject) && !$subject instanceof \Closure) {
            foreach (get_object_vars($subject) as $key => $item) {
                self::lookup($item, self::joinPath($path, (string) $key), $values);
            }
        }
    }

    private static function joinPath(string $left, string $right): string
    {
        return $left === '' ? $right : "{$left}.{$right}";
    }
}
