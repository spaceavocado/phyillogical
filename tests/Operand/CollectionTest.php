<?php

declare(strict_types=1);

namespace Illogical\Tests\Operand;

use Illogical\Evaluable;
use Illogical\Operand\Collection;
use Illogical\Operand\InvalidCollection;
use Illogical\Operand\Reference;
use Illogical\Operand\Value;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CollectionTest extends TestCase
{
    public function testConstructor(): void
    {
        $this->expectException(InvalidCollection::class);
        new Collection([]);
    }

    public static function shouldBeEscapedProvider(): iterable
    {
        yield ['==', true];
        yield ['!=', false];
        yield [null, false];
        yield [true, false];
    }

    #[DataProvider('shouldBeEscapedProvider')]
    public function testShouldBeEscaped(mixed $subject, bool $expected): void
    {
        self::assertSame($expected, Collection::shouldBeEscaped($subject, ['==']));
    }

    public static function escapeOperatorProvider(): iterable
    {
        yield ['==', '\\', '\\=='];
        yield ['==', 'g', 'g=='];
    }

    #[DataProvider('escapeOperatorProvider')]
    public function testEscapeOperator(string $operator, string $escapeCharacter, string $expected): void
    {
        self::assertSame($expected, Collection::escapeOperator($operator, $escapeCharacter));
    }

    public static function evaluateProvider(): iterable
    {
        yield [[new Value(1)], [1]];
        yield [[new Value('1')], ['1']];
        yield [[new Value(true)], [true]];
        yield [[new Reference('RefA')], ['A']];
        yield [[new Value(1), new Reference('RefA')], [1, 'A']];
        yield [[new Collection([new Value(1)]), new Reference('RefA')], [[1], 'A']];
    }

    #[DataProvider('evaluateProvider')]
    public function testEvaluate(array $items, array $expected): void
    {
        self::assertSame($expected, (new Collection($items))->evaluate(['RefA' => 'A']));
    }

    public static function serializeProvider(): iterable
    {
        yield [[new Value(1)], [1]];
        yield [[new Value('1')], ['1']];
        yield [[new Value(true)], [true]];
        yield [[new Reference('RefA')], ['$RefA']];
        yield [[new Value(1), new Reference('RefA')], [1, '$RefA']];
        yield [[new Value('=='), new Value(1), new Value(1)], ['\\==', 1, 1]];
        yield [[new Value('!='), new Value(1), new Value(1)], ['!=', 1, 1]];
    }

    #[DataProvider('serializeProvider')]
    public function testSerialize(array $items, array $expected): void
    {
        self::assertSame($expected, (new Collection($items, escapedOperators: ['==']))->serialize());
    }

    public static function simplifyProvider(): iterable
    {
        yield [[new Reference('RefB')], new Collection([new Reference('RefB')])];
        yield [[new Reference('RefA')], ['A']];
        yield [[new Value(1), new Reference('RefA')], [1, 'A']];
        yield [
            [new Reference('RefA'), new Reference('RefB')],
            new Collection([new Reference('RefA'), new Reference('RefB')]),
        ];
    }

    #[DataProvider('simplifyProvider')]
    public function testSimplify(array $items, mixed $expected): void
    {
        $simplified = (new Collection($items))->simplify(['RefA' => 'A']);

        if ($expected instanceof Evaluable) {
            self::assertSame((string) $expected, (string) $simplified);
        } else {
            self::assertSame($expected, $simplified);
        }
    }

    public static function toStringProvider(): iterable
    {
        yield [[new Value(1)], '[1]'];
        yield [[new Value('1')], '["1"]'];
        yield [[new Value(true)], '[true]'];
        yield [[new Reference('RefA')], '[{RefA}]'];
        yield [[new Value(1), new Reference('RefA')], '[1, {RefA}]'];
    }

    #[DataProvider('toStringProvider')]
    public function testToString(array $items, string $expected): void
    {
        self::assertSame($expected, (string) new Collection($items));
    }
}
