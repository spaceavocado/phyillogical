<?php

declare(strict_types=1);

namespace Illogical\Expression\Comparison;

use Illogical\Evaluable;

/**
 * Has suffix comparison expression. Left operand is the tested word, right operand is the suffix.
 */
final class Suffix extends Comparison
{
    public function __construct(Evaluable $left, Evaluable $right, string $symbol = 'SUFFIX')
    {
        parent::__construct(
            '<with suffix>',
            $symbol,
            static fn (mixed $left, mixed $right): bool => is_string($left) && is_string($right) && str_ends_with($left, $right),
            $left,
            $right,
        );
    }
}
