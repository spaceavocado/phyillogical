<?php

declare(strict_types=1);

namespace Illogical\Expression\Comparison;

use Illogical\Evaluable;

/**
 * Greater than or equal comparison expression. Valid operands: numbers or strings.
 */
final class Ge extends Comparison
{
    public function __construct(Evaluable $left, Evaluable $right, string $symbol = '>=')
    {
        parent::__construct(
            '>=',
            $symbol,
            static fn (mixed $left, mixed $right): bool => self::comparable($left, $right) && $left >= $right,
            $left,
            $right,
        );
    }
}
