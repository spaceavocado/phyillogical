<?php

declare(strict_types=1);

namespace Illogical\Tests\Expression\Comparison;

use Illogical\Expression\Comparison\Comparison;
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
use Illogical\Operand\Collection;
use Illogical\Operand\Reference;
use Illogical\Operand\Value;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Evaluation of the concrete comparison expressions.
 */
final class ComparisonsTest extends TestCase
{
    private static function v(mixed $value): Value
    {
        return new Value($value);
    }

    private static function c(mixed ...$values): Collection
    {
        return new Collection(array_map(static fn ($value) => new Value($value), $values));
    }

    public static function evaluateProvider(): iterable
    {
        $v = self::v(...);
        $c = self::c(...);

        // Eq
        yield 'eq int' => [new Eq($v(1), $v(1)), true];
        yield 'eq float' => [new Eq($v(1.1), $v(1.1)), true];
        yield 'eq string' => [new Eq($v('1'), $v('1')), true];
        yield 'eq true' => [new Eq($v(true), $v(true)), true];
        yield 'eq false' => [new Eq($v(false), $v(false)), true];
        yield 'eq null' => [new Eq($v(null), $v(null)), true];
        yield 'eq int float' => [new Eq($v(1), $v(1.1)), false];
        yield 'eq int string' => [new Eq($v(1), $v('1')), false];
        yield 'eq int bool' => [new Eq($v(1), $v(true)), false];
        yield 'eq float string' => [new Eq($v(1.1), $v('1')), false];
        yield 'eq float bool' => [new Eq($v(1.1), $v(true)), false];
        yield 'eq string bool' => [new Eq($v('1'), $v(true)), false];
        yield 'eq collections' => [new Eq($c(1), $c(1)), false];
        yield 'eq value collection' => [new Eq($v(1), $c(1)), false];
        // Ne
        yield 'ne int' => [new Ne($v(1), $v(0)), true];
        yield 'ne int same' => [new Ne($v(1), $v(1)), false];
        yield 'ne float' => [new Ne($v(1.1), $v(1.0)), true];
        yield 'ne float same' => [new Ne($v(1.1), $v(1.1)), false];
        yield 'ne string' => [new Ne($v('1'), $v('2')), true];
        yield 'ne string same' => [new Ne($v('1'), $v('1')), false];
        yield 'ne bool' => [new Ne($v(true), $v(false)), true];
        yield 'ne bool same' => [new Ne($v(true), $v(true)), false];
        yield 'ne int float' => [new Ne($v(1), $v(1.1)), true];
        yield 'ne int string' => [new Ne($v(1), $v('1')), true];
        yield 'ne int bool' => [new Ne($v(1), $v(true)), true];
        yield 'ne float string' => [new Ne($v(1.1), $v('1')), true];
        yield 'ne float bool' => [new Ne($v(1.1), $v(true)), true];
        yield 'ne string bool' => [new Ne($v('1'), $v(true)), true];
        yield 'ne collections' => [new Ne($c(1), $c(1)), true];
        yield 'ne value collection' => [new Ne($v(1), $c(1)), true];
        // Gt
        yield 'gt int' => [new Gt($v(2), $v(1)), true];
        yield 'gt float' => [new Gt($v(1.2), $v(1.1)), true];
        yield 'gt float int' => [new Gt($v(1.1), $v(1)), true];
        yield 'gt string' => [new Gt($v('b'), $v('a')), true];
        yield 'gt int same' => [new Gt($v(1), $v(1)), false];
        yield 'gt float same' => [new Gt($v(1.1), $v(1.1)), false];
        yield 'gt int less' => [new Gt($v(0), $v(1)), false];
        yield 'gt float less' => [new Gt($v(1.0), $v(1.1)), false];
        yield 'gt string int' => [new Gt($v('value'), $v(1)), false];
        yield 'gt int string' => [new Gt($v(2), $v('1')), false];
        yield 'gt null' => [new Gt($v(1), $v(null)), false];
        yield 'gt collection' => [new Gt($c(2), $v(1)), false];
        // Ge
        yield 'ge int' => [new Ge($v(2), $v(1)), true];
        yield 'ge float' => [new Ge($v(1.2), $v(1.1)), true];
        yield 'ge float int' => [new Ge($v(1.1), $v(1)), true];
        yield 'ge int same' => [new Ge($v(1), $v(1)), true];
        yield 'ge float same' => [new Ge($v(1.1), $v(1.1)), true];
        yield 'ge int less' => [new Ge($v(0), $v(1)), false];
        yield 'ge float less' => [new Ge($v(1.0), $v(1.1)), false];
        yield 'ge string int' => [new Ge($v('value'), $v(1)), false];
        // Lt
        yield 'lt int' => [new Lt($v(0), $v(1)), true];
        yield 'lt float' => [new Lt($v(1.0), $v(1.1)), true];
        yield 'lt int float' => [new Lt($v(1), $v(1.1)), true];
        yield 'lt int same' => [new Lt($v(1), $v(1)), false];
        yield 'lt float same' => [new Lt($v(1.1), $v(1.1)), false];
        yield 'lt int greater' => [new Lt($v(2), $v(1)), false];
        yield 'lt float greater' => [new Lt($v(1.2), $v(1.1)), false];
        yield 'lt string int' => [new Lt($v('value'), $v(1)), false];
        // Le
        yield 'le int' => [new Le($v(0), $v(1)), true];
        yield 'le float' => [new Le($v(1.0), $v(1.1)), true];
        yield 'le int float' => [new Le($v(1), $v(1.1)), true];
        yield 'le int same' => [new Le($v(1), $v(1)), true];
        yield 'le float same' => [new Le($v(1.1), $v(1.1)), true];
        yield 'le int greater' => [new Le($v(2), $v(1)), false];
        yield 'le float greater' => [new Le($v(1.2), $v(1.1)), false];
        yield 'le string int' => [new Le($v('value'), $v(1)), false];
        // In
        yield 'in value collection' => [new In($v(1), $c(1)), true];
        yield 'in collection value' => [new In($c(1), $v(1)), true];
        yield 'in string' => [new In($v('1'), $c('1')), true];
        yield 'in bool' => [new In($v(true), $c(true)), true];
        yield 'in float' => [new In($v(1.1), $c(1.1)), true];
        yield 'in missing' => [new In($v(1), $c(2)), false];
        yield 'in collection missing' => [new In($c(2), $v(1)), false];
        yield 'in values' => [new In($v(1), $v(1)), false];
        yield 'in collections' => [new In($c(1), $c(1)), false];
        yield 'in diff types' => [new In($v(1), $c('1')), false];
        // Nin
        yield 'nin value collection' => [new Nin($v(1), $c(2)), true];
        yield 'nin collection value' => [new Nin($c(2), $v(1)), true];
        yield 'nin values' => [new Nin($v(1), $v(1)), true];
        yield 'nin collections' => [new Nin($c(1), $c(1)), true];
        yield 'nin diff types' => [new Nin($v(1), $c('1')), true];
        yield 'nin present' => [new Nin($v(1), $c(1)), false];
        yield 'nin collection present' => [new Nin($c(1), $v(1)), false];
        yield 'nin string present' => [new Nin($v('1'), $c('1')), false];
        // Overlap
        yield 'overlap single' => [new Overlap($c(1), $c(1)), true];
        yield 'overlap partial' => [new Overlap($c(1, 2), $c(1, 3)), true];
        yield 'overlap unordered' => [new Overlap($c(3, 2), $c(1, 2, 3)), true];
        yield 'overlap string' => [new Overlap($c('1'), $c('1')), true];
        yield 'overlap bool' => [new Overlap($c(true), $c(true)), true];
        yield 'overlap float' => [new Overlap($c(1.1), $c(1.1)), true];
        yield 'overlap value collection' => [new Overlap($v(1), $c(1)), false];
        yield 'overlap collection value' => [new Overlap($c(1), $v(1)), false];
        yield 'overlap values' => [new Overlap($v(1), $v(1)), false];
        yield 'overlap none' => [new Overlap($c(1), $c(2)), false];
        yield 'overlap diff types' => [new Overlap($c(1), $c('1')), false];
        // Prefix
        yield 'prefix' => [new Prefix($v('bo'), $v('bogus')), true];
        yield 'prefix not' => [new Prefix($v('bo'), $v('something')), false];
        yield 'prefix int float' => [new Prefix($v(1), $v(1.1)), false];
        yield 'prefix int string' => [new Prefix($v(1), $v('1')), false];
        yield 'prefix string bool' => [new Prefix($v('1'), $v(true)), false];
        yield 'prefix collections' => [new Prefix($c(1), $c(1)), false];
        // Suffix
        yield 'suffix' => [new Suffix($v('bogus'), $v('us')), true];
        yield 'suffix not' => [new Suffix($v('something'), $v('us')), false];
        yield 'suffix int float' => [new Suffix($v(1), $v(1.1)), false];
        yield 'suffix int string' => [new Suffix($v(1), $v('1')), false];
        yield 'suffix string bool' => [new Suffix($v('1'), $v(true)), false];
        yield 'suffix collections' => [new Suffix($c(1), $c(1)), false];
        // None
        yield 'none missing' => [new None(new Reference('Missing')), true];
        yield 'none null' => [new None($v(null)), true];
        yield 'none int' => [new None($v(1)), false];
        yield 'none float' => [new None($v(1.1)), false];
        yield 'none string' => [new None($v('1')), false];
        yield 'none true' => [new None($v(true)), false];
        yield 'none false' => [new None($v(false)), false];
        // Present
        yield 'present missing' => [new Present(new Reference('Missing')), false];
        yield 'present null' => [new Present($v(null)), false];
        yield 'present int' => [new Present($v(1)), true];
        yield 'present float' => [new Present($v(1.1)), true];
        yield 'present string' => [new Present($v('1')), true];
        yield 'present true' => [new Present($v(true)), true];
        yield 'present false' => [new Present($v(false)), true];
    }

    #[DataProvider('evaluateProvider')]
    public function testEvaluate(Comparison $comparison, bool $expected): void
    {
        self::assertSame($expected, $comparison->evaluate([]));

        $simplified = $comparison->simplify([]);
        if ($comparison->operands[0] instanceof Reference) {
            // missing reference could not be simplified
            self::assertSame($comparison, $simplified);
        } else {
            self::assertSame($expected, $simplified);
        }
    }

    public static function statementProvider(): iterable
    {
        $v = self::v(...);

        yield [new Eq($v(1), $v(2)), '(1 == 2)', ['==', 1, 2]];
        yield [new Ne($v(1), $v(2)), '(1 != 2)', ['!=', 1, 2]];
        yield [new Gt($v(1), $v(2)), '(1 > 2)', ['>', 1, 2]];
        yield [new Ge($v(1), $v(2)), '(1 >= 2)', ['>=', 1, 2]];
        yield [new Lt($v(1), $v(2)), '(1 < 2)', ['<', 1, 2]];
        yield [new Le($v(1), $v(2)), '(1 <= 2)', ['<=', 1, 2]];
        yield [new In($v(1), self::c(1)), '(1 <in> [1])', ['IN', 1, [1]]];
        yield [new Nin($v(1), self::c(1)), '(1 <not in> [1])', ['NOT IN', 1, [1]]];
        yield [new Overlap(self::c(1), self::c(1)), '([1] <overlaps> [1])', ['OVERLAP', [1], [1]]];
        yield [new Prefix($v('a'), $v('ab')), '("a" <prefixes> "ab")', ['PREFIX', 'a', 'ab']];
        yield [new Suffix($v('ab'), $v('b')), '("ab" <with suffix> "b")', ['SUFFIX', 'ab', 'b']];
        yield [new None(new Reference('a')), '({a} <is none>)', ['NONE', '$a']];
        yield [new Present(new Reference('a')), '({a} <is present>)', ['PRESENT', '$a']];
        yield [new Eq($v(1), $v(2), 'IS'), '(1 == 2)', ['IS', 1, 2]];
    }

    #[DataProvider('statementProvider')]
    public function testStatementAndSerialize(Comparison $comparison, string $statement, array $serialized): void
    {
        self::assertSame($statement, (string) $comparison);
        self::assertSame($serialized, $comparison->serialize());
    }
}
