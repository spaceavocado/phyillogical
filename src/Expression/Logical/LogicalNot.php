<?php

declare(strict_types=1);

namespace Illogical\Expression\Logical;

use Illogical\Evaluable;
use Illogical\FlattenContext;

/**
 * Not logical unary expression.
 */
final class LogicalNot extends Logical
{
    public function __construct(Evaluable $operand, string $symbol = 'NOT')
    {
        parent::__construct('NOT', $symbol, $operand);
    }

    public function evaluate(array|object $context): bool
    {
        return !self::evaluateOperand($this->operands[0], FlattenContext::from($context));
    }

    public function simplify(array|object $context): mixed
    {
        $result = $this->operands[0]->simplify(FlattenContext::from($context));
        if (is_bool($result)) {
            return !$result;
        }

        return $this;
    }
}
