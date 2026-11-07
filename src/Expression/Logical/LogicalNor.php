<?php

declare(strict_types=1);

namespace Illogical\Expression\Logical;

use Illogical\Evaluable;
use Illogical\FlattenContext;

/**
 * Negated or logical expression.
 */
final class LogicalNor extends Logical
{
    /**
     * @param array<Evaluable> $operands
     * @param string $notSymbol Symbol of the NOT expression, used when simplified
     *     into a NOT expression.
     *
     * @throws InvalidLogicalExpression
     */
    public function __construct(
        array $operands,
        string $symbol = 'NOR',
        public readonly string $notSymbol = 'NOT',
    ) {
        self::assertOperands($operands);
        parent::__construct('NOR', $symbol, ...array_values($operands));
    }

    public function evaluate(array|object $context): bool
    {
        $context = FlattenContext::from($context);

        foreach ($this->operands as $operand) {
            if (self::evaluateOperand($operand, $context)) {
                return false;
            }
        }

        return true;
    }

    public function simplify(array|object $context): mixed
    {
        $context = FlattenContext::from($context);
        $simplified = [];

        foreach ($this->operands as $operand) {
            $result = $operand->simplify($context);
            if (is_bool($result)) {
                if ($result) {
                    return false;
                }
                continue;
            }

            $simplified[] = $result instanceof Evaluable ? $result : $operand;
        }

        return match (count($simplified)) {
            0 => true,
            1 => new LogicalNot($simplified[0], $this->notSymbol),
            default => new self($simplified, $this->symbol, $this->notSymbol),
        };
    }
}
