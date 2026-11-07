<?php

declare(strict_types=1);

namespace Illogical\Expression\Comparison;

use Illogical\Evaluable;

/**
 * Has prefix comparison expression. Left operand is the prefix, right operand is the tested word.
 */
final class Prefix extends Comparison
{
    public function __construct(Evaluable $left, Evaluable $right, string $symbol = 'PREFIX')
    {
        parent::__construct(
            '<prefixes>',
            $symbol,
            static fn (mixed $left, mixed $right): bool => is_string($left) && is_string($right) && str_starts_with($right, $left),
            $left,
            $right,
        );
    }
}
