<?php

declare(strict_types=1);

namespace Illogical\Expression\Logical;

use Illogical\Evaluable;
use Illogical\FlattenContext;

/**
 * Logical expression.
 */
abstract class Logical implements Evaluable
{
    /** @var list<Evaluable> */
    public readonly array $operands;

    /**
     * @param string $operator Operator used in the statement (text) form.
     * @param string $symbol Operator symbol used in the raw expression.
     */
    public function __construct(
        public readonly string $operator,
        public readonly string $symbol,
        Evaluable ...$operands,
    ) {
        $this->operands = array_values($operands);
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
        if (count($this->operands) === 1) {
            return "({$this->operator} {$this->operands[0]})";
        }

        return '(' . implode(
            " {$this->operator} ",
            array_map(static fn (Evaluable $operand) => (string) $operand, $this->operands),
        ) . ')';
    }

    /**
     * Evaluate the operand, which must be evaluated as a boolean value.
     *
     * @throws InvalidLogicalExpressionOperand
     */
    protected static function evaluateOperand(Evaluable $operand, FlattenContext $context): bool
    {
        $result = $operand->evaluate($context);
        if (!is_bool($result)) {
            throw new InvalidLogicalExpressionOperand('logical operands must be evaluated as boolean values');
        }

        return $result;
    }

    /**
     * @param array<Evaluable> $operands
     *
     * @throws InvalidLogicalExpression
     */
    protected static function assertOperands(array $operands): void
    {
        if (count($operands) < 2) {
            throw new InvalidLogicalExpression('non unary logical expression must have at least 2 operands');
        }
    }
}
