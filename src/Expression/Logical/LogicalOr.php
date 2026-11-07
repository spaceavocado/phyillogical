<?php

declare(strict_types=1);

namespace Illogical\Expression\Logical;

use Illogical\Evaluable;
use Illogical\FlattenContext;

/**
 * Or logical expression.
 */
final class LogicalOr extends Logical
{
    /**
     * @param array<Evaluable> $operands
     *
     * @throws InvalidLogicalExpression
     */
    public function __construct(array $operands, string $symbol = 'OR')
    {
        self::assertOperands($operands);
        parent::__construct('OR', $symbol, ...array_values($operands));
    }

    public function evaluate(array|object $context): bool
    {
        $context = FlattenContext::from($context);

        foreach ($this->operands as $operand) {
            if (self::evaluateOperand($operand, $context)) {
                return true;
            }
        }

        return false;
    }

    public function simplify(array|object $context): mixed
    {
        $context = FlattenContext::from($context);
        $simplified = [];

        foreach ($this->operands as $operand) {
            $result = $operand->simplify($context);
            if (is_bool($result)) {
                if ($result) {
                    return true;
                }
                continue;
            }

            $simplified[] = $result instanceof Evaluable ? $result : $operand;
        }

        return match (count($simplified)) {
            0 => false,
            1 => $simplified[0],
            default => new self($simplified, $this->symbol),
        };
    }
}
