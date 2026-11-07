<?php

declare(strict_types=1);

namespace Illogical\Expression\Comparison;

use Illogical\Evaluable;

/**
 * Overlap comparison expression, i.e. both collections have at least one common item.
 */
final class Overlap extends Comparison
{
    public function __construct(Evaluable $left, Evaluable $right, string $symbol = 'OVERLAP')
    {
        parent::__construct(
            '<overlaps>',
            $symbol,
            static fn (mixed $left, mixed $right): bool => is_array($left) && is_array($right) && array_filter($left, static fn (mixed $item) => in_array($item, $right, true)) !== [],
            $left,
            $right,
        );
    }
}
