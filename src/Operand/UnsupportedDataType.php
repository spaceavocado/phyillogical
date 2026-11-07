<?php

declare(strict_types=1);

namespace Illogical\Operand;

/**
 * Unsupported reference data type casting, e.g. `$ref.(Bogus)`.
 */
final class UnsupportedDataType extends \InvalidArgumentException
{
}
