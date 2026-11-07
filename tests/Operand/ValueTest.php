<?php

declare(strict_types=1);

namespace Illogical\Tests\Operand;

use Illogical\Operand\Value;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ValueTest extends TestCase
{
    public static function valueProvider(): iterable
    {
        yield [1];
        yield [1.1];
        yield ['val'];
        yield [true];
        yield [false];
        yield [null];
    }

    #[DataProvider('valueProvider')]
    public function testEvaluateSimplifySerialize(mixed $value): void
    {
        $operand = new Value($value);

        self::assertSame($value, $operand->evaluate([]));
        self::assertSame($value, $operand->simplify([]));
        self::assertSame($value, $operand->serialize());
    }

    public static function toStringProvider(): iterable
    {
        yield [1, '1'];
        yield [1.1, '1.1'];
        yield [1.0, '1.0'];
        yield ['val', '"val"'];
        yield [true, 'true'];
        yield [false, 'false'];
        yield [null, 'null'];
    }

    #[DataProvider('toStringProvider')]
    public function testToString(mixed $value, string $expected): void
    {
        self::assertSame($expected, (string) new Value($value));
    }
}
