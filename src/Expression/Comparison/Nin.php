<?php

declare(strict_types=1);

namespace Illogical\Expression\Comparison;

use Illogical\Evaluable;

/**
 * Not in comparison expression, i.e. negation of the In expression.
 */
final class Nin extends Comparison
{
    public function __construct(Evaluable $left, Evaluable $right, string $symbol = 'NOT IN')
    {
        parent::__construct(
            '<not in>',
            $symbol,
            static fn (mixed $left, mixed $right): bool => is_array($left) === is_array($right) || !(is_array($left) ? in_array($right, $left, true) : in_array($left, (array) $right, true)),
            $left,
            $right,
        );
    }
}
