<?php

declare(strict_types=1);

namespace Illogical\Tests\Expression\Comparison;

use Illogical\Evaluable;
use Illogical\Expression\Comparison\Comparison;
use Illogical\Operand\Reference;
use Illogical\Operand\Value;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ComparisonTest extends TestCase
{
    private static function eq(Evaluable ...$operands): Comparison
    {
        return new Comparison('==', '==', static fn ($left, $right) => $left === $right, ...$operands);
    }

    public static function serializeProvider(): iterable
    {
        yield ['->', [new Value(1), new Value(2)], ['->', 1, 2]];
        yield ['X', [new Value(1)], ['X', 1]];
    }

    #[DataProvider('serializeProvider')]
    public function testSerialize(string $operator, array $operands, array $expected): void
    {
        $comparison = new Comparison($operator, $operator, static fn () => false, ...$operands);

        self::assertSame($expected, $comparison->serialize());
    }

    public static function simplifyProvider(): iterable
    {
        yield [[new Value(0), new Reference('Missing')], self::eq(new Value(0), new Reference('Missing'))];
        yield [[new Reference('Missing'), new Value(0)], self::eq(new Reference('Missing'), new Value(0))];
        yield [
            [new Reference('Missing'), new Reference('Missing')],
            self::eq(new Reference('Missing'), new Reference('Missing')),
        ];
        yield [[new Value(0), new Value(0)], true];
        yield [[new Value(0), new Value(1)], false];
        yield [[new Value('A'), new Reference('RefA')], true];
    }

    #[DataProvider('simplifyProvider')]
    public function testSimplify(array $operands, mixed $expected): void
    {
        $simplified = self::eq(...$operands)->simplify(['RefA' => 'A']);

        if ($expected instanceof Evaluable) {
            self::assertSame((string) $expected, (string) $simplified);
        } else {
            self::assertSame($expected, $simplified);
        }
    }

    public function testTypeErrorIsFalse(): void
    {
        $comparison = new Comparison('X', 'X', static fn (int $value) => true, new Value('not int'));

        self::assertFalse($comparison->evaluate([]));
    }

    public static function toStringProvider(): iterable
    {
        yield ['==', [new Value(1), new Value(2)], '(1 == 2)'];
        yield ['<nil>', [new Value(1)], '(1 <nil>)'];
    }

    #[DataProvider('toStringProvider')]
    public function testToString(string $operator, array $operands, string $expected): void
    {
        self::assertSame($expected, (string) new Comparison($operator, $operator, static fn () => false, ...$operands));
    }
}
