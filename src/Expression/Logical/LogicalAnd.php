<?php

declare(strict_types=1);

namespace Illogical\Expression\Logical;

use Illogical\Evaluable;
use Illogical\FlattenContext;

/**
 * And logical expression.
 */
final class LogicalAnd extends Logical
{
    /**
     * @param array<Evaluable> $operands
     *
     * @throws InvalidLogicalExpression
     */
    public function __construct(array $operands, string $symbol = 'AND')
    {
        self::assertOperands($operands);
        parent::__construct('AND', $symbol, ...array_values($operands));
    }

    public function evaluate(array|object $context): bool
    {
        $context = FlattenContext::from($context);

        foreach ($this->operands as $operand) {
            if (!self::evaluateOperand($operand, $context)) {
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
                if (!$result) {
                    return false;
                }
                continue;
            }

            $simplified[] = $result instanceof Evaluable ? $result : $operand;
        }

        return match (count($simplified)) {
            0 => true,
            1 => $simplified[0],
            default => new self($simplified, $this->symbol),
        };
    }
}
