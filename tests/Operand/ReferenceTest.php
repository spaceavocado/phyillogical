<?php

declare(strict_types=1);

namespace Illogical\Tests\Operand;

use Illogical\Evaluable;
use Illogical\FlattenContext;
use Illogical\Operand\DataType;
use Illogical\Operand\Reference;
use Illogical\Operand\UnsupportedDataType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ReferenceTest extends TestCase
{
    private static function context(): FlattenContext
    {
        return FlattenContext::from([
            'refA' => 1,
            'refB' => [
                'refB1' => 2,
                'refB2' => 'refB1',
                'refB3' => true,
            ],
            'refC' => 'refB1',
            'refD' => 'refB2',
            'refE' => [1, [2, 3, 4]],
            'refF' => 'A',
            'refG' => '1',
            'refH' => '1.1',
            'refX' => null,
        ]);
    }

    public static function defaultSerializeFromProvider(): iterable
    {
        yield ['', null];
        yield ['$', null];
        yield ['ref', null];
        yield ['$ref', 'ref'];
    }

    #[DataProvider('defaultSerializeFromProvider')]
    public function testDefaultSerializeFrom(string $address, ?string $expected): void
    {
        self::assertSame($expected, Reference::defaultSerializeFrom($address));
    }

    public function testDefaultSerializeTo(): void
    {
        self::assertSame('$ref', Reference::defaultSerializeTo('ref'));
    }

    public static function dataTypeProvider(): iterable
    {
        yield ['ref', DataType::Undefined];
        yield ['ref.(X)', DataType::Undefined];
        yield ['ref.(Bogus)', DataType::Unsupported];
        yield ['ref.(String)', DataType::String];
        yield ['ref.(Number)', DataType::Number];
        yield ['ref.(Integer)', DataType::Integer];
        yield ['ref.(Float)', DataType::Float];
        yield ['ref.(Boolean)', DataType::Boolean];
    }

    #[DataProvider('dataTypeProvider')]
    public function testDataTypeFromAddress(string $address, DataType $expected): void
    {
        self::assertSame($expected, DataType::fromAddress($address));
    }

    public static function trimDataTypeProvider(): iterable
    {
        yield ['ref', 'ref'];
        yield ['ref.(X)', 'ref.(X)'];
        yield ['ref.(String)', 'ref'];
    }

    #[DataProvider('trimDataTypeProvider')]
    public function testTrimDataType(string $address, string $expected): void
    {
        self::assertSame($expected, DataType::trim($address));
    }

    public static function isIgnoredPathProvider(): iterable
    {
        yield ['path', [], [], false];
        yield ['ignored', ['ignored'], [], true];
        yield ['not', ['ignored'], [], false];
        yield ['refC', [], ['/^ref/'], true];
        yield ['refC.(Number)', [], ['/^ref/'], true];
        yield ['xrefC', [], ['/^ref/'], false];
    }

    #[DataProvider('isIgnoredPathProvider')]
    public function testIsIgnoredPath(string $path, array $ignoredPaths, array $ignoredPathsRx, bool $expected): void
    {
        self::assertSame($expected, Reference::isIgnoredPath($path, $ignoredPaths, $ignoredPathsRx));
    }

    public static function toNumberProvider(): iterable
    {
        yield [1, 1];
        yield [1.1, 1.1];
        yield ['1', 1];
        yield ['1.1', 1.1];
        yield [true, 1];
        yield [false, 0];
        yield ['bogus', 'bogus'];
        yield [[], []];
    }

    #[DataProvider('toNumberProvider')]
    public function testToNumber(mixed $value, mixed $expected): void
    {
        self::assertSame($expected, DataType::toNumber($value));
    }

    public static function toIntProvider(): iterable
    {
        yield [1, 1];
        yield [1.1, 1];
        yield ['1', 1];
        yield ['1.1', 1];
        yield ['1.9', 1];
        yield [true, 1];
        yield [false, 0];
        yield [[], []];
    }

    #[DataProvider('toIntProvider')]
    public function testToInt(mixed $value, mixed $expected): void
    {
        self::assertSame($expected, DataType::toInt($value));
    }

    public static function toFloatProvider(): iterable
    {
        yield [1, 1.0];
        yield [1.1, 1.1];
        yield ['1', 1.0];
        yield ['1.1', 1.1];
        yield ['1.9', 1.9];
        yield [true, 1.0];
        yield [[], []];
    }

    #[DataProvider('toFloatProvider')]
    public function testToFloat(mixed $value, mixed $expected): void
    {
        self::assertSame($expected, DataType::toFloat($value));
    }

    public static function toStringProvider(): iterable
    {
        yield [1, '1'];
        yield [1.1, '1.1'];
        yield ['1', '1'];
        yield [true, 'true'];
        yield [false, 'false'];
    }

    #[DataProvider('toStringProvider')]
    public function testToString(mixed $value, string $expected): void
    {
        self::assertSame($expected, DataType::toString($value));
    }

    public static function toBooleanProvider(): iterable
    {
        yield [true, true];
        yield [false, false];
        yield ['true', true];
        yield ['false', false];
        yield ['True', true];
        yield ['False', false];
        yield ['1', true];
        yield ['0', false];
        yield [1, true];
        yield [0, false];
        yield [[], false];
    }

    #[DataProvider('toBooleanProvider')]
    public function testToBoolean(mixed $value, bool $expected): void
    {
        self::assertSame($expected, DataType::toBoolean($value));
    }

    public static function contextLookupProvider(): iterable
    {
        yield ['UNDEFINED', false, 'UNDEFINED', null];
        yield ['refA', true, 'refA', 1];
        yield ['refB.refB1', true, 'refB.refB1', 2];
        yield ['refB.{refC}', true, 'refB.refB1', 2];
        yield ['refB.{UNDEFINED}', false, 'refB.{UNDEFINED}', null];
        yield ['refB.{refB.refB2}', true, 'refB.refB1', 2];
        yield ['refB.{refB.{refD}}', true, 'refB.refB1', 2];
        yield ['refE[0]', true, 'refE[0]', 1];
        yield ['refE[2]', false, 'refE[2]', null];
        yield ['refE[1][0]', true, 'refE[1][0]', 2];
        yield ['refE[1][3]', false, 'refE[1][3]', null];
        yield ['refE[{refA}][0]', true, 'refE[1][0]', 2];
        yield ['refE[{refA}][{refB.refB1}]', true, 'refE[1][2]', 4];
        yield ['ref{refF}', true, 'refA', 1];
        yield ['ref{UNDEFINED}', false, 'ref{UNDEFINED}', null];
        yield ['refX', true, 'refX', null];
    }

    #[DataProvider('contextLookupProvider')]
    public function testContextLookup(string $path, bool $found, string $resolvedPath, mixed $value): void
    {
        self::assertSame([$found, $resolvedPath, $value], Reference::contextLookup(self::context(), $path));
    }

    public static function resolveProvider(): iterable
    {
        yield ['refA', DataType::Integer, 1];
        yield ['refA', DataType::String, '1'];
        yield ['refG', DataType::Number, 1];
        yield ['refH', DataType::Float, 1.1];
        yield ['refB.refB3', DataType::String, 'true'];
        yield ['refB.refB3', DataType::Boolean, true];
        yield ['refB.refB3', DataType::Number, 1];
        yield ['refJ', DataType::Undefined, null];
        yield ['refJ', DataType::String, null];
    }

    #[DataProvider('resolveProvider')]
    public function testResolve(string $path, DataType $dataType, mixed $expected): void
    {
        [, , $value] = Reference::resolve(self::context(), $path, $dataType);

        self::assertSame($expected, $value);
    }

    public function testEvaluate(): void
    {
        self::assertSame(1, (new Reference('refA'))->evaluate(self::context()));
        self::assertSame('1', (new Reference('refA.(String)'))->evaluate(self::context()));
        self::assertSame(2, (new Reference('refB.refB1'))->evaluate(['refB' => ['refB1' => 2]]));
    }

    public static function serializeProvider(): iterable
    {
        yield ['refA', '$refA'];
        yield ['refA.(Number)', '$refA.(Number)'];
    }

    #[DataProvider('serializeProvider')]
    public function testSerialize(string $address, string $expected): void
    {
        self::assertSame($expected, (new Reference($address))->serialize());
    }

    public function testSerializeTo(): void
    {
        self::assertSame('__refA', (new Reference('refA', static fn (string $a) => "__{$a}"))->serialize());
    }

    public static function simplifyProvider(): iterable
    {
        yield ['refA', 1];
        yield ['ignored', new Reference('ignored')];
        yield ['refB.{refB1}', new Reference('refB.{refB1}')];
        yield ['refC', new Reference('refC')];
        yield ['ref', new Reference('ref')];
        yield ['refX', null];
    }

    #[DataProvider('simplifyProvider')]
    public function testSimplify(string $address, mixed $expected): void
    {
        $context = FlattenContext::fromFlatten(self::context()->toArray() + ['ignored' => 1]);
        $simplified = (new Reference($address, null, ['ignored'], ['/^refC/']))->simplify($context);

        if ($expected instanceof Evaluable) {
            self::assertInstanceOf(Reference::class, $simplified);
            self::assertSame((string) $expected, (string) $simplified);
        } else {
            self::assertSame($expected, $simplified);
        }
    }

    public static function toStatementProvider(): iterable
    {
        yield ['refA', '{refA}'];
        yield ['refA.(Number)', '{refA.(Number)}'];
    }

    #[DataProvider('toStatementProvider')]
    public function testToStatement(string $address, string $expected): void
    {
        self::assertSame($expected, (string) new Reference($address));
    }

    public function testUnsupportedDataType(): void
    {
        $this->expectException(UnsupportedDataType::class);
        new Reference('address.(Unsupported)');
    }
}
