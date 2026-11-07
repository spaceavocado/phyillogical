<?php

declare(strict_types=1);

namespace Illogical\Expression\Comparison;

use Illogical\Evaluable;

/**
 * Is none comparison expression, i.e. the operand is evaluated as null (missing).
 */
final class None extends Comparison
{
    public function __construct(Evaluable $operand, string $symbol = 'NONE')
    {
        parent::__construct(
            '<is none>',
            $symbol,
            static fn (mixed $value): bool => $value === null,
            $operand,
        );
    }
}
