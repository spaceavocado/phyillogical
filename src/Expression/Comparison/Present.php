<?php

declare(strict_types=1);

namespace Illogical\Expression\Comparison;

use Illogical\Evaluable;

/**
 * Is present comparison expression, i.e. the operand is not evaluated as null (missing).
 */
final class Present extends Comparison
{
    public function __construct(Evaluable $operand, string $symbol = 'PRESENT')
    {
        parent::__construct(
            '<is present>',
            $symbol,
            static fn (mixed $value): bool => $value !== null,
            $operand,
        );
    }
}
