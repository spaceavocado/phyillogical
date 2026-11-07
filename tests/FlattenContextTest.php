<?php

declare(strict_types=1);

namespace Illogical\Tests;

use Illogical\FlattenContext;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class FlattenContextTest extends TestCase
{
    public static function flattenProvider(): iterable
    {
        yield [['a' => 1], ['a' => 1]];
        yield [['a' => null], ['a' => null]];
        yield [['a' => 1, 'b' => ['c' => 5, 'd' => true]], ['a' => 1, 'b.c' => 5, 'b.d' => true]];
        yield [['a' => 1, 'b' => [1, 2, 3]], ['a' => 1, 'b[0]' => 1, 'b[1]' => 2, 'b[2]' => 3]];
        yield [
            ['a' => 1, 'b' => [1, 2, ['c' => 5, 'd' => true, 'e' => static fn ($x) => $x]]],
            ['a' => 1, 'b[0]' => 1, 'b[1]' => 2, 'b[2].c' => 5, 'b[2].d' => true],
        ];
        yield [
            (object) ['a' => 1, 'b' => (object) ['c' => [1, (object) ['d' => 'x']]]],
            ['a' => 1, 'b.c[0]' => 1, 'b.c[1].d' => 'x'],
        ];
        yield [['a' => []], []];
    }

    #[DataProvider('flattenProvider')]
    public function testFrom(array|object $context, array $expected): void
    {
        self::assertSame($expected, FlattenContext::from($context)->toArray());
    }

    public function testPreventReprocessing(): void
    {
        $context = FlattenContext::fromFlatten(['a' => 1, 'b' => ['b1' => 2]]);

        self::assertSame($context, FlattenContext::from($context));
        self::assertSame(['a' => 1, 'b' => ['b1' => 2]], FlattenContext::from($context)->toArray());
    }

    public function testHasGet(): void
    {
        $context = FlattenContext::from(['a' => null, 'b' => 1]);

        self::assertTrue($context->has('a'));
        self::assertTrue($context->has('b'));
        self::assertFalse($context->has('c'));
        self::assertNull($context->get('a'));
        self::assertSame(1, $context->get('b'));
        self::assertNull($context->get('c'));
    }
}
