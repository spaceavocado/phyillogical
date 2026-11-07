<?php

declare(strict_types=1);

namespace Illogical\Expression\Logical;

/**
 * Non unary logical expression must have at least 2 operands.
 */
final class InvalidLogicalExpression extends \InvalidArgumentException
{
}
