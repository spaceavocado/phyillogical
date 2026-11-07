<?php

declare(strict_types=1);

namespace Illogical\Tests;

use Illogical\Evaluable;
use Illogical\Expression\Comparison\Eq;
use Illogical\Expression\Logical\LogicalAnd;
use Illogical\Illogical;
use Illogical\Operand\Collection;
use Illogical\Operand\Reference;
use Illogical\Operand\Value;
use Illogical\Parser\Kind;
use Illogical\Parser\Options;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class IllogicalTest extends TestCase
{
    public static function parseProvider(): iterable
    {
        yield [1, new Value(1)];
        yield ['$path', new Reference('path')];
        yield [[1], new Collection([new Value(1)])];
        yield [['==', 1, 1], new Eq(new Value(1), new Value(1))];
        yield [['AND', true, true], new LogicalAnd([new Value(true), new Value(true)])];
    }

    #[DataProvider('parseProvider')]
    public function testParse(mixed $expression, Evaluable $expected): void
    {
        $result = (new Illogical())->parse($expression);

        self::assertInstanceOf($expected::class, $result);
        self::assertSame((string) $expected, (string) $result);
    }

    public static function evaluateProvider(): iterable
    {
        yield [1, 1];
        yield ['$path', 'value'];
        yield [[1], [1]];
        yield [['==', 1, 1], true];
        yield [['AND', true, false], false];
    }

    #[DataProvider('evaluateProvider')]
    public function testEvaluate(mixed $expression, mixed $expected): void
    {
        self::assertSame($expected, (new Illogical())->evaluate($expression, ['path' => 'value']));
    }

    public static function simplifyProvider(): iterable
    {
        yield [1, 1];
        yield ['$path', 'value'];
        yield ['$nested.inner', 2];
        yield ['$list[1]', 3];
        yield ['$missing', new Reference('missing')];
        yield [[1], [1]];
        yield [['==', 1, 1], true];
        yield [['AND', true, true], true];
        yield [['AND', true, '$missing'], new Reference('missing')];
    }

    #[DataProvider('simplifyProvider')]
    public function testSimplify(mixed $expression, mixed $expected): void
    {
        $result = (new Illogical())->simplify(
            $expression,
            ['path' => 'value', 'nested' => ['inner' => 2], 'list' => [1, 3]],
        );

        if ($expected instanceof Evaluable) {
            self::assertSame((string) $expected, (string) $result);
        } else {
            self::assertSame($expected, $result);
        }
    }

    public static function statementProvider(): iterable
    {
        yield [1, '1'];
        yield [1.5, '1.5'];
        yield [true, 'true'];
        yield [null, 'null', [null]];
        yield ['val', '"val"'];
        yield ['$refA', '{refA}'];
        yield [['==', 1, 1], '(1 == 1)'];
        yield [['==', '$refA', 'resolvedA'], '({refA} == "resolvedA")'];
        yield [['AND', ['==', 1, 1], ['!=', 2, 1]], '((1 == 1) AND (2 != 1))'];
        yield [['NOT', ['==', 1, 1]], '(NOT (1 == 1))'];
        yield [['NONE', '$a'], '({a} <is none>)'];
        yield [['IN', 1, [1, 2]], '(1 <in> [1, 2])'];
    }

    #[DataProvider('statementProvider')]
    public function testStatement(mixed $expression, string $expected, ?array $wrapped = null): void
    {
        // null is not a valid top-level expression, test it as a collection item
        if ($wrapped !== null) {
            self::assertSame("[{$expected}]", (new Illogical())->statement($wrapped));
            return;
        }

        self::assertSame($expected, (new Illogical())->statement($expression));
    }

    public function testOperatorMapping(): void
    {
        $illogical = new Illogical(new Options(operatorMapping: [Kind::EQ => 'IS']));

        self::assertTrue($illogical->evaluate(['IS', 1, 1]));
        self::assertFalse($illogical->evaluate(['IS', 1, 2]));
        self::assertSame(['IS', 1, 2], $illogical->parse(['IS', 1, 2])->serialize());
        // default symbol is not an operator anymore, i.e. a collection
        self::assertSame(['==', 1, 1], $illogical->evaluate(['==', 1, 1]));
        // other operators are kept
        self::assertTrue($illogical->evaluate(['AND', ['IS', 1, 1], ['!=', 1, 2]]));
    }

    public function testUnknownOperatorKind(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Options(operatorMapping: ['BOGUS' => 'X']);
    }

    public function testEscapeCharacter(): void
    {
        $illogical = new Illogical(new Options(escapeCharacter: '*'));

        self::assertSame('["AND", 1, 1]', $illogical->statement(['*AND', 1, 1]));
        self::assertSame(['*AND', 1, 1], $illogical->parse(['*AND', 1, 1])->serialize());
    }

    public function testReferenceSerializeOptions(): void
    {
        $illogical = new Illogical(new Options(
            referenceFrom: static fn (string $operand) => str_starts_with($operand, '__') ? substr($operand, 2) : null,
            referenceTo: static fn (string $address) => "__{$address}",
        ));

        self::assertTrue($illogical->evaluate(['==', '__name', 'peter'], ['name' => 'peter']));
        self::assertSame(['==', '__name', '$name'], $illogical->parse(['==', '__name', '$name'])->serialize());
    }

    public function testSimplifyIgnoredPaths(): void
    {
        $illogical = new Illogical(new Options(ignoredPaths: ['ignored'], ignoredPathsRx: ['/^skip/']));

        $result = $illogical->simplify(
            ['AND', ['==', '$a', 10], ['==', '$ignored', 20], ['==', '$skipThis', 30]],
            ['a' => 10, 'ignored' => 20, 'skipThis' => 30],
        );

        self::assertSame('(({ignored} == 20) AND ({skipThis} == 30))', (string) $result);
    }

    public function testReadmeExamples(): void
    {
        $illogical = new Illogical();
        $context = [
            'name' => 'peter',
            'country' => 'canada',
            'age' => 21,
            'options' => [1, 2, 3],
            'address' => ['city' => 'Toronto', 'country' => 'Canada'],
            'index' => 2,
            'segment' => 'city',
            'shapeA' => 'box',
            'shapeB' => 'circle',
            'shapeType' => 'B',
        ];

        self::assertTrue($illogical->evaluate(['>', '$age', 20], $context));
        self::assertTrue($illogical->evaluate(['==', '$address.city', 'Toronto'], $context));
        self::assertTrue($illogical->evaluate(['==', '$options[1]', 2], $context));
        self::assertTrue($illogical->evaluate(['==', '$options[{index}]', 3], $context));
        self::assertTrue($illogical->evaluate(['==', '$address.{segment}', 'Toronto'], $context));
        self::assertTrue($illogical->evaluate(['==', '$shape{shapeType}', 'circle'], $context));
        self::assertTrue($illogical->evaluate(['==', '$age.(String)', '21'], $context));
        self::assertTrue($illogical->evaluate(['IN', 5, [1, 2, 3, 4, 5]], $context));
        self::assertTrue($illogical->evaluate(['IN', ['circle', 'square', 'triangle'], 'square'], $context));
        self::assertTrue($illogical->evaluate(['NOT IN', 10, [1, 2, 3, 4, 5]], $context));
        self::assertTrue($illogical->evaluate(['PREFIX', 'hemi', 'hemisphere'], $context));
        self::assertFalse($illogical->evaluate(['PREFIX', 'hemi', 'sphere'], $context));
        self::assertTrue($illogical->evaluate(['SUFFIX', 'establishment', 'ment'], $context));
        self::assertTrue($illogical->evaluate(['OVERLAP', [1, 2, 6], [1, 2, 3, 4, 5]], $context));
        self::assertTrue($illogical->evaluate(['NONE', '$RefA'], []));
        self::assertFalse($illogical->evaluate(['PRESENT', '$RefA'], []));
        self::assertTrue($illogical->evaluate(['PRESENT', '$RefA'], ['RefA' => false]));
        self::assertTrue($illogical->evaluate(['XOR', ['==', 5, 5], ['==', 10, 5]], $context));
        self::assertFalse($illogical->evaluate(['XOR', ['==', 5, 5], ['==', 10, 10]], $context));
        self::assertTrue($illogical->evaluate(['NOR', ['==', 5, 1], ['==', 10, 5]], $context));
        self::assertFalse($illogical->evaluate(['NOT', ['==', 5, 5]], $context));

        $evaluable = $illogical->parse(['AND', ['==', '$a', 10], ['==', '$b', 20]]);
        self::assertSame('({b} == 20)', (string) $evaluable->simplify(['a' => 10]));
        self::assertFalse($evaluable->simplify(['a' => 20]));
        self::assertSame(['AND', ['==', '$a', 10], ['==', '$b', 20]], $evaluable->serialize());

        // stdClass context, e.g. json_decode output
        self::assertTrue($illogical->evaluate(
            ['==', '$address.city', 'Toronto'],
            json_decode('{"address": {"city": "Toronto"}}'),
        ));
    }
}
