<?php

declare(strict_types=1);

namespace Illogical\Expression\Logical;

use Illogical\Evaluable;
use Illogical\FlattenContext;

/**
 * Exclusive or logical expression, i.e. exactly one operand is truthy.
 */
final class LogicalXor extends Logical
{
    /**
     * @param array<Evaluable> $operands
     * @param string $notSymbol Symbol of the NOT expression, used when simplified
     *     into a NOT expression.
     * @param string $norSymbol Symbol of the NOR expression, used when simplified
     *     into a NOR expression.
     *
     * @throws InvalidLogicalExpression
     */
    public function __construct(
        array $operands,
        string $symbol = 'XOR',
        public readonly string $notSymbol = 'NOT',
        public readonly string $norSymbol = 'NOR',
    ) {
        self::assertOperands($operands);
        parent::__construct('XOR', $symbol, ...array_values($operands));
    }

    public function evaluate(array|object $context): bool
    {
        $context = FlattenContext::from($context);
        $xor = false;

        foreach ($this->operands as $operand) {
            if (!self::evaluateOperand($operand, $context)) {
                continue;
            }

            if ($xor) {
                return false;
            }

            $xor = true;
        }

        return $xor;
    }

    public function simplify(array|object $context): mixed
    {
        $context = FlattenContext::from($context);
        $truthy = 0;
        $simplified = [];

        foreach ($this->operands as $operand) {
            $result = $operand->simplify($context);
            if (is_bool($result)) {
                if ($result) {
                    $truthy++;
                }
                if ($truthy > 1) {
                    return false;
                }
                continue;
            }

            $simplified[] = $result instanceof Evaluable ? $result : $operand;
        }

        if (count($simplified) === 0) {
            return $truthy === 1;
        }

        if (count($simplified) === 1) {
            return $truthy === 1 ? new LogicalNot($simplified[0], $this->notSymbol) : $simplified[0];
        }

        if ($truthy === 1) {
            return new LogicalNor($simplified, $this->norSymbol, $this->notSymbol);
        }

        return new self($simplified, $this->symbol, $this->notSymbol, $this->norSymbol);
    }
}
