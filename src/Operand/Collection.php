<?php

declare(strict_types=1);

namespace Illogical\Operand;

use Illogical\Evaluable;
use Illogical\FlattenContext;

/**
 * Collection operand, i.e. a list of operands (values, references, collections).
 */
final class Collection implements Evaluable
{
    /** Default character used to escape the first item of a collection. */
    public const DEFAULT_ESCAPE_CHARACTER = '\\';

    /** @var list<Evaluable> */
    public readonly array $items;

    /**
     * @param array<Evaluable> $items
     * @param string $escapeCharacter Character used to escape the first value within
     *     a collection, if the value contains an operator symbol. E.g.:
     *     `["==", 1, 1]` is interpreted as EQ expression, while
     *     `["\==", 1, 1]` is interpreted as a collection.
     * @param array<string> $escapedOperators Operator symbols which should be escaped.
     *
     * @throws InvalidCollection
     */
    public function __construct(
        array $items,
        public readonly string $escapeCharacter = self::DEFAULT_ESCAPE_CHARACTER,
        public readonly array $escapedOperators = [],
    ) {
        if (count($items) === 0) {
            throw new InvalidCollection('collection operand must have at least 1 item');
        }

        $this->items = array_values($items);
    }

    /**
     * @return list<mixed>
     */
    public function evaluate(array|object $context): array
    {
        $context = FlattenContext::from($context);

        return array_map(static fn (Evaluable $item) => $item->evaluate($context), $this->items);
    }

    public function simplify(array|object $context): mixed
    {
        $context = FlattenContext::from($context);
        $result = [];

        foreach ($this->items as $item) {
            $value = $item->simplify($context);
            if ($value instanceof Evaluable) {
                return $this;
            }

            $result[] = $value;
        }

        return $result;
    }

    /**
     * @return list<mixed>
     */
    public function serialize(): array
    {
        $serialized = array_map(static fn (Evaluable $item) => $item->serialize(), $this->items);

        if (self::shouldBeEscaped($serialized[0], $this->escapedOperators)) {
            $serialized[0] = self::escapeOperator($serialized[0], $this->escapeCharacter);
        }

        return $serialized;
    }

    public function __toString(): string
    {
        return '[' . implode(', ', array_map(static fn (Evaluable $item) => (string) $item, $this->items)) . ']';
    }

    /**
     * Should be the subject escaped, i.e. it is an operator symbol, predicate.
     *
     * @param array<string> $escapedOperators
     *
     * @phpstan-assert-if-true string $subject
     */
    public static function shouldBeEscaped(mixed $subject, array $escapedOperators): bool
    {
        return is_string($subject) && in_array($subject, $escapedOperators, true);
    }

    /**
     * Escape operator symbol.
     */
    public static function escapeOperator(string $operator, string $escapeCharacter): string
    {
        return $escapeCharacter . $operator;
    }
}
