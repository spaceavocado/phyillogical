<?php

declare(strict_types=1);

namespace Illogical\Expression\Comparison;

use Illogical\Evaluable;
use Illogical\FlattenContext;

/**
 * Comparison expression.
 */
class Comparison implements Evaluable
{
    /** @var list<Evaluable> */
    public readonly array $operands;

    /** @var \Closure */
    private readonly \Closure $comparison;

    /**
     * @param string $operator Operator used in the statement (text) form.
     * @param string $symbol Operator symbol used in the raw expression.
     * @param callable $comparison Comparison of the evaluated operands, returns bool.
     */
    public function __construct(
        public readonly string $operator,
        public readonly string $symbol,
        callable $comparison,
        Evaluable ...$operands,
    ) {
        $this->comparison = \Closure::fromCallable($comparison);
        $this->operands = array_values($operands);
    }

    public function evaluate(array|object $context): bool
    {
        $context = FlattenContext::from($context);

        return $this->compare(
            array_map(static fn (Evaluable $operand) => $operand->evaluate($context), $this->operands)
        );
    }

    public function simplify(array|object $context): mixed
    {
        $context = FlattenContext::from($context);
        $result = [];

        foreach ($this->operands as $operand) {
            $value = $operand->simplify($context);
            if ($value instanceof Evaluable) {
                return $this;
            }

            $result[] = $value;
        }

        return $this->compare($result);
    }

    /**
     * @return list<mixed>
     */
    public function serialize(): array
    {
        return [
            $this->symbol,
            ...array_map(static fn (Evaluable $operand) => $operand->serialize(), $this->operands),
        ];
    }

    public function __toString(): string
    {
        $result = '(' . $this->operands[0] . ' ' . $this->operator;
        if (count($this->operands) > 1) {
            $result .= ' ' . implode(' ', array_map(
                static fn (Evaluable $operand) => (string) $operand,
                array_slice($this->operands, 1),
            ));
        }

        return $result . ')';
    }

    /**
     * @param list<mixed> $values
     */
    private function compare(array $values): bool
    {
        try {
            return ($this->comparison)(...$values);
        } catch (\TypeError) {
            return false;
        }
    }

    /**
     * Are the values comparable by the relational operators (<, <=, >, >=),
     * i.e. both are numbers, or both are strings, predicate.
     */
    protected static function comparable(mixed $left, mixed $right): bool
    {
        $isNumber = static fn (mixed $value) => is_int($value) || is_float($value);

        return ($isNumber($left) && $isNumber($right)) || (is_string($left) && is_string($right));
    }
}
