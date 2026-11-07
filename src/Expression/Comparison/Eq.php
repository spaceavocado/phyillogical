<?php

declare(strict_types=1);

namespace Illogical\Expression\Comparison;

use Illogical\Evaluable;

/**
 * Equal comparison expression. Collections are never equal.
 */
final class Eq extends Comparison
{
    public function __construct(Evaluable $left, Evaluable $right, string $symbol = '==')
    {
        parent::__construct(
            '==',
            $symbol,
            static fn (mixed $left, mixed $right): bool => !is_array($left) && $left === $right,
            $left,
            $right,
        );
    }
}
