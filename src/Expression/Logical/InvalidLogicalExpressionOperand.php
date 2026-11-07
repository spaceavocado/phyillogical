<?php

declare(strict_types=1);

namespace Illogical\Expression\Logical;

/**
 * Logical operands must be evaluated as boolean values.
 */
final class InvalidLogicalExpressionOperand extends \UnexpectedValueException
{
}
