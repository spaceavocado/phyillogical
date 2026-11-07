<?php

declare(strict_types=1);

namespace Illogical\Tests\Parser;

use Illogical\Evaluable;
use Illogical\Expression\Comparison\Eq;
use Illogical\Expression\Comparison\Ge;
use Illogical\Expression\Comparison\Gt;
use Illogical\Expression\Comparison\In;
use Illogical\Expression\Comparison\Le;
use Illogical\Expression\Comparison\Lt;
use Illogical\Expression\Comparison\Ne;
use Illogical\Expression\Comparison\Nin;
use Illogical\Expression\Comparison\None;
use Illogical\Expression\Comparison\Overlap;
use Illogical\Expression\Comparison\Prefix;
use Illogical\Expression\Comparison\Present;
use Illogical\Expression\Comparison\Suffix;
use Illogical\Expression\Logical\LogicalAnd;
use Illogical\Expression\Logical\LogicalNor;
use Illogical\Expression\Logical\LogicalNot;
use Illogical\Expression\Logical\LogicalOr;
use Illogical\Expression\Logical\LogicalXor;
use Illogical\Operand\Collection;
use Illogical\Operand\Reference;
use Illogical\Operand\Value;
use Illogical\Parser\Kind;
use Illogical\Parser\Options;
use Illogical\Parser\Parser;
use Illogical\Parser\UnexpectedExpressionInput;
use Illogical\Parser\UnexpectedOperand;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ParserTest extends TestCase
{
    private const M = Options::DEFAULT_OPERATOR_MAPPING;

    public static function isEscapedProvider(): iterable
    {
        yield ['\\expected', '\\', true];
        yield ['unexpected', '\\', false];
        yield ['\\expected', '', false];
    }

    #[DataProvider('isEscapedProvider')]
    public function testIsEscaped(string $value, string $escapeCharacter, bool $expected): void
    {
        self::assertSame($expected, Parser::isEscaped($value, $escapeCharacter));
    }

    public static function parseProvider(): iterable
    {
        // Value
        yield [1, new Value(1)];
        yield [1.1, new Value(1.1)];
        yield ['val', new Value('val')];
        yield [true, new Value(true)];
        yield ['$', new Value('$')];
        // Reference
        yield ['$path', new Reference('path')];
        // Collection
        yield [[1], new Collection([new Value(1)])];
        yield [['val'], new Collection([new Value('val')])];
        yield [['val1', 'val2'], new Collection([new Value('val1'), new Value('val2')])];
        yield [[true], new Collection([new Value(true)])];
        yield [[null], new Collection([new Value(null)])];
        yield [['$ref'], new Collection([new Reference('ref')])];
        yield [
            [1, 'val', true, '$ref'],
            new Collection([new Value(1), new Value('val'), new Value(true), new Reference('ref')]),
        ];
        yield [[1, 2], new Collection([new Value(1), new Value(2)])];
        yield [['BOGUS', 1], new Collection([new Value('BOGUS'), new Value(1)])];
        yield [['\\' . self::M[Kind::AND], 1], new Collection([new Value('AND'), new Value(1)])];
        // Comparison
        $v = new Value(1);
        yield [[self::M[Kind::EQ], 1, 1], new Eq($v, $v)];
        yield [[self::M[Kind::NE], 1, 1], new Ne($v, $v)];
        yield [[self::M[Kind::GT], 1, 1], new Gt($v, $v)];
        yield [[self::M[Kind::GE], 1, 1], new Ge($v, $v)];
        yield [[self::M[Kind::LT], 1, 1], new Lt($v, $v)];
        yield [[self::M[Kind::LE], 1, 1], new Le($v, $v)];
        yield [[self::M[Kind::IN], 1, 1], new In($v, $v)];
        yield [[self::M[Kind::NIN], 1, 1], new Nin($v, $v)];
        yield [[self::M[Kind::OVERLAP], 1, 1], new Overlap($v, $v)];
        yield [[self::M[Kind::NONE], 1, 1], new None($v)];
        yield [[self::M[Kind::PRESENT], 1, 1], new Present($v)];
        yield [[self::M[Kind::SUFFIX], 1, 1], new Suffix($v, $v)];
        yield [[self::M[Kind::PREFIX], 1, 1], new Prefix($v, $v)];
        // Logical
        $t = new Value(true);
        yield [[self::M[Kind::AND], true, true], new LogicalAnd([$t, $t])];
        yield [[self::M[Kind::OR], true, true], new LogicalOr([$t, $t])];
        yield [[self::M[Kind::NOR], true, true], new LogicalNor([$t, $t])];
        yield [[self::M[Kind::XOR], true, true], new LogicalXor([$t, $t])];
        yield [[self::M[Kind::NOT], true], new LogicalNot($t)];
    }

    #[DataProvider('parseProvider')]
    public function testParse(mixed $expression, Evaluable $expected): void
    {
        $result = (new Parser())->parse($expression);

        self::assertInstanceOf($expected::class, $result);
        self::assertSame((string) $expected, (string) $result);
    }

    public static function serializeProvider(): iterable
    {
        yield [1];
        yield ['$ref'];
        yield ['$ref.(Number)'];
        yield [[1, '$ref', null]];
        yield [['\\==', 1, 1]];
        yield [['AND', ['==', '$a', 10], ['OR', ['IN', 1, [1, 2]], ['NOT', ['PRESENT', '$b']]]]];
        yield [['XOR', ['NONE', '$a'], ['NOR', ['PREFIX', 'a', 'ab'], ['SUFFIX', 'ab', 'b']]]];
    }

    #[DataProvider('serializeProvider')]
    public function testSerializeRoundTrip(mixed $expression): void
    {
        self::assertSame($expression, (new Parser())->parse($expression)->serialize());
    }

    public static function invalidProvider(): iterable
    {
        yield [null, UnexpectedExpressionInput::class];
        yield [[], UnexpectedOperand::class];
        yield [[static fn () => true], UnexpectedOperand::class];
        yield [['val1', static fn () => true], UnexpectedOperand::class];
        yield [new \stdClass(), UnexpectedOperand::class];
        yield [['a' => 1, 'b' => 2], UnexpectedOperand::class];
        yield [['==', 1], UnexpectedOperand::class];
    }

    #[DataProvider('invalidProvider')]
    public function testInvalid(mixed $expression, string $expected): void
    {
        $this->expectException($expected);
        (new Parser())->parse($expression);
    }
}
