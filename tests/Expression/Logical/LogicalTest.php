<?php

declare(strict_types=1);

namespace Illogical\Tests\Expression\Logical;

use Illogical\Evaluable;
use Illogical\Expression\Logical\InvalidLogicalExpression;
use Illogical\Expression\Logical\InvalidLogicalExpressionOperand;
use Illogical\Expression\Logical\Logical;
use Illogical\Expression\Logical\LogicalAnd;
use Illogical\Expression\Logical\LogicalNor;
use Illogical\Expression\Logical\LogicalNot;
use Illogical\Expression\Logical\LogicalOr;
use Illogical\Expression\Logical\LogicalXor;
use Illogical\Operand\Reference;
use Illogical\Operand\Value;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class LogicalTest extends TestCase
{
    private const CONTEXT = ['RefA' => true, 'invalid' => 1];

    /**
     * @return list<Value>
     */
    private static function values(mixed ...$values): array
    {
        return array_map(static fn ($value) => new Value($value), $values);
    }

    private static function ref(string $address): Reference
    {
        return new Reference($address);
    }

    public static function constructorProvider(): iterable
    {
        foreach ([LogicalAnd::class, LogicalOr::class, LogicalNor::class, LogicalXor::class] as $class) {
            yield "{$class} empty" => [$class, []];
            yield "{$class} single" => [$class, self::values(true)];
        }
    }

    #[DataProvider('constructorProvider')]
    public function testConstructor(string $class, array $operands): void
    {
        $this->expectException(InvalidLogicalExpression::class);
        new $class($operands);
    }

    public static function evaluateProvider(): iterable
    {
        $v = self::values(...);

        // And
        yield [new LogicalAnd($v(true, true)), true];
        yield [new LogicalAnd($v(true, true, true)), true];
        yield [new LogicalAnd($v(true, false)), false];
        yield [new LogicalAnd($v(false, true)), false];
        yield [new LogicalAnd($v(false, false)), false];
        yield [new LogicalAnd($v(false, 1)), false]; // short circuit
        // Or
        yield [new LogicalOr($v(true, true)), true];
        yield [new LogicalOr($v(true, false)), true];
        yield [new LogicalOr($v(false, true)), true];
        yield [new LogicalOr($v(false, false, true)), true];
        yield [new LogicalOr($v(false, false)), false];
        yield [new LogicalOr($v(true, 1)), true]; // short circuit
        // Nor
        yield [new LogicalNor($v(false, false)), true];
        yield [new LogicalNor($v(false, false, false)), true];
        yield [new LogicalNor($v(true, true)), false];
        yield [new LogicalNor($v(true, false)), false];
        yield [new LogicalNor($v(false, true)), false];
        yield [new LogicalNor($v(true, true, false)), false];
        // Xor
        yield [new LogicalXor($v(true, false)), true];
        yield [new LogicalXor($v(false, true)), true];
        yield [new LogicalXor($v(true, false, false)), true];
        yield [new LogicalXor($v(false, true, false)), true];
        yield [new LogicalXor($v(false, false, true)), true];
        yield [new LogicalXor($v(true, true)), false];
        yield [new LogicalXor($v(false, false)), false];
        yield [new LogicalXor($v(true, true, false)), false];
        yield [new LogicalXor($v(true, false, true)), false];
        yield [new LogicalXor($v(true, true, true)), false];
        // Not
        yield [new LogicalNot(new Value(true)), false];
        yield [new LogicalNot(new Value(false)), true];
    }

    #[DataProvider('evaluateProvider')]
    public function testEvaluate(Logical $expression, bool $expected): void
    {
        self::assertSame($expected, $expression->evaluate([]));
    }

    public static function invalidOperandProvider(): iterable
    {
        $v = self::values(...);

        yield [new LogicalAnd($v(1, true))];
        yield [new LogicalAnd($v(true, 1))];
        yield [new LogicalAnd($v(1, 'bogus'))];
        yield [new LogicalOr($v(1, true))];
        yield [new LogicalOr($v(false, 1))];
        yield [new LogicalOr($v(1, 'bogus'))];
        yield [new LogicalNor($v(1, true))];
        yield [new LogicalNor($v(false, 1))];
        yield [new LogicalNor($v(1, 'bogus'))];
        yield [new LogicalXor($v(1, true))];
        yield [new LogicalXor($v(1, 'bogus'))];
        yield [new LogicalNot(new Value(1))];
        yield [new LogicalNot(new Value('bogus'))];
    }

    #[DataProvider('invalidOperandProvider')]
    public function testEvaluateInvalidOperand(Logical $expression): void
    {
        $this->expectException(InvalidLogicalExpressionOperand::class);
        $expression->evaluate([]);
    }

    public static function simplifyProvider(): iterable
    {
        $v = self::values(...);
        $r = self::ref(...);

        // And
        yield [new LogicalAnd($v(true, true)), true];
        yield [new LogicalAnd($v(false, true)), false];
        yield [new LogicalAnd([$r('RefA'), new Value(true)]), true];
        yield [new LogicalAnd([$r('Missing'), new Value(true)]), $r('Missing')];
        yield [new LogicalAnd([$r('Missing'), new Value(false)]), false];
        yield [new LogicalAnd([$r('Missing'), $r('Missing')]), new LogicalAnd([$r('Missing'), $r('Missing')])];
        yield [new LogicalAnd([new Value(true), $r('invalid')]), $r('invalid')];
        // Or
        yield [new LogicalOr($v(false, false)), false];
        yield [new LogicalOr($v(false, true)), true];
        yield [new LogicalOr([$r('RefA'), new Value(false)]), true];
        yield [new LogicalOr([$r('Missing'), new Value(false)]), $r('Missing')];
        yield [new LogicalOr([$r('Missing'), new Value(true)]), true];
        yield [new LogicalOr([$r('Missing'), $r('Missing')]), new LogicalOr([$r('Missing'), $r('Missing')])];
        yield [new LogicalOr([new Value(false), $r('invalid')]), $r('invalid')];
        // Nor
        yield [new LogicalNor($v(false, false)), true];
        yield [new LogicalNor($v(true, true)), false];
        yield [new LogicalNor($v(true, false)), false];
        yield [new LogicalNor([$r('RefA'), new Value(false)]), false];
        yield [new LogicalNor([$r('Missing'), new Value(true)]), false];
        yield [new LogicalNor([$r('Missing'), new Value(false)]), new LogicalNot($r('Missing'))];
        yield [new LogicalNor([$r('Missing'), $r('Missing')]), new LogicalNor([$r('Missing'), $r('Missing')])];
        yield [new LogicalNor([new Value(false), $r('invalid')]), new LogicalNot($r('invalid'))];
        // Xor
        yield [new LogicalXor($v(false, false)), false];
        yield [new LogicalXor($v(false, true)), true];
        yield [new LogicalXor([$r('RefA'), new Value(true)]), false];
        yield [
            new LogicalXor([$r('Missing'), new Value(true), $r('Missing')]),
            new LogicalNor([$r('Missing'), $r('Missing')]),
        ];
        yield [new LogicalXor([$r('Missing'), new Value(true), new Value(false)]), new LogicalNot($r('Missing'))];
        yield [new LogicalXor([$r('RefA'), $r('RefA'), new Value(true)]), false];
        yield [new LogicalXor([new Value(false), $r('Missing')]), $r('Missing')];
        yield [new LogicalXor([$r('Missing'), $r('Missing')]), new LogicalXor([$r('Missing'), $r('Missing')])];
        yield [new LogicalXor([new Value(false), $r('invalid')]), $r('invalid')];
        // Not
        yield [new LogicalNot(new Value(true)), false];
        yield [new LogicalNot(new Value(false)), true];
        yield [new LogicalNot($r('RefA')), false];
        yield [new LogicalNot($r('Missing')), new LogicalNot($r('Missing'))];
    }

    #[DataProvider('simplifyProvider')]
    public function testSimplify(Logical $expression, mixed $expected): void
    {
        $simplified = $expression->simplify(self::CONTEXT);

        if ($expected instanceof Evaluable) {
            self::assertInstanceOf($expected::class, $simplified);
            self::assertSame((string) $expected, (string) $simplified);
        } else {
            self::assertSame($expected, $simplified);
        }
    }

    public function testSimplifyKeepsSymbols(): void
    {
        $missing = self::ref('Missing');

        $xor = new LogicalXor([$missing, new Value(true), $missing], 'X', 'N', 'NR');
        self::assertSame(['NR', '$Missing', '$Missing'], $xor->simplify([])->serialize());

        $xor = new LogicalXor([$missing, new Value(true)], 'X', 'N', 'NR');
        self::assertSame(['N', '$Missing'], $xor->simplify([])->serialize());

        $xor = new LogicalXor([$missing, $missing, new Value(false)], 'X', 'N', 'NR');
        self::assertSame(['X', '$Missing', '$Missing'], $xor->simplify([])->serialize());

        $nor = new LogicalNor([$missing, new Value(false)], 'NR', 'N');
        self::assertSame(['N', '$Missing'], $nor->simplify([])->serialize());

        $and = new LogicalAnd([$missing, $missing, new Value(true)], '&&');
        self::assertSame(['&&', '$Missing', '$Missing'], $and->simplify([])->serialize());

        $or = new LogicalOr([$missing, $missing, new Value(false)], '||');
        self::assertSame(['||', '$Missing', '$Missing'], $or->simplify([])->serialize());
    }

    public static function statementProvider(): iterable
    {
        $v = self::values(...);

        yield [new LogicalAnd($v(true, false)), '(true AND false)', ['AND', true, false]];
        yield [new LogicalOr($v(true, false, true)), '(true OR false OR true)', ['OR', true, false, true]];
        yield [new LogicalNor($v(true, false)), '(true NOR false)', ['NOR', true, false]];
        yield [new LogicalXor($v(true, false)), '(true XOR false)', ['XOR', true, false]];
        yield [new LogicalNot(new Value(true)), '(NOT true)', ['NOT', true]];
        yield [new LogicalAnd($v(true, false), '&&'), '(true AND false)', ['&&', true, false]];
    }

    #[DataProvider('statementProvider')]
    public function testStatementAndSerialize(Logical $expression, string $statement, array $serialized): void
    {
        self::assertSame($statement, (string) $expression);
        self::assertSame($serialized, $expression->serialize());
    }
}
