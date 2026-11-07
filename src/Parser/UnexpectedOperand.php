<?php

declare(strict_types=1);

namespace Illogical\Parser;

/**
 * Unexpected operand, e.g. empty collection, or a non-primitive value.
 */
final class UnexpectedOperand extends \InvalidArgumentException
{
}
