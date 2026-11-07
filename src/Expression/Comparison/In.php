<?php

declare(strict_types=1);

namespace Illogical\Expression\Comparison;

use Illogical\Evaluable;

/**
 * In comparison expression, i.e. one operand is a collection containing the other operand.
 */
final class In extends Comparison
{
    public function __construct(Evaluable $left, Evaluable $right, string $symbol = 'IN')
    {
        parent::__construct(
            '<in>',
            $symbol,
            static fn (mixed $left, mixed $right): bool => is_array($left) !== is_array($right) && (is_array($left) ? in_array($right, $left, true) : in_array($left, (array) $right, true)),
            $left,
            $right,
        );
    }
}
