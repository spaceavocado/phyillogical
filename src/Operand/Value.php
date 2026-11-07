<?php

declare(strict_types=1);

namespace Illogical\Operand;

use Illogical\Evaluable;
use Illogical\Primitive;

/**
 * Value operand, i.e. a static primitive value: string, int, float, bool, null.
 */
final class Value implements Evaluable
{
    public function __construct(public readonly string|int|float|bool|null $value)
    {
    }

    public function evaluate(array|object $context): mixed
    {
        return $this->value;
    }

    public function simplify(array|object $context): mixed
    {
        return $this->value;
    }

    public function serialize(): mixed
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return Primitive::toStatement($this->value);
    }
}
