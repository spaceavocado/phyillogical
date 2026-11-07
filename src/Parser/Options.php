<?php

declare(strict_types=1);

namespace Illogical\Parser;

use Illogical\Operand\Collection;
use Illogical\Operand\Reference;

/**
 * Parsing options.
 */
final class Options
{
    /**
     * Default mapping of the operators. The key is the expression kind, and the
     * value is the symbol used to represent the given operator in the raw expression.
     */
    public const DEFAULT_OPERATOR_MAPPING = [
        // Logical
        Kind::AND => 'AND',
        Kind::OR => 'OR',
        Kind::NOR => 'NOR',
        Kind::XOR => 'XOR',
        Kind::NOT => 'NOT',
        // Comparison
        Kind::EQ => '==',
        Kind::NE => '!=',
        Kind::GT => '>',
        Kind::GE => '>=',
        Kind::LT => '<',
        Kind::LE => '<=',
        Kind::NONE => 'NONE',
        Kind::PRESENT => 'PRESENT',
        Kind::IN => 'IN',
        Kind::NIN => 'NOT IN',
        Kind::OVERLAP => 'OVERLAP',
        Kind::PREFIX => 'PREFIX',
        Kind::SUFFIX => 'SUFFIX',
    ];

    public const DEFAULT_ESCAPE_CHARACTER = Collection::DEFAULT_ESCAPE_CHARACTER;

    /** @var array<string, string> Resolved operator mapping, [kind => symbol]. */
    public readonly array $operatorMapping;

    /** @var \Closure(string): ?string */
    public readonly \Closure $referenceFrom;

    /** @var \Closure(string): string */
    public readonly \Closure $referenceTo;

    /**
     * @param array<string, string> $operatorMapping Overrides of the default operator
     *     mapping, [Kind::* => symbol], e.g. [Kind::EQ => 'IS'].
     * @param null|callable(string): ?string $referenceFrom A function used to determine
     *     if the operand is a reference, it returns the reference address (annotation
     *     stripped form, e.g. "$ref" => "ref"), otherwise null, i.e. the operand is
     *     a static value.
     * @param null|callable(string): string $referenceTo A function used to transform
     *     the reference address into the annotated form, e.g. "ref" => "$ref".
     * @param string $escapeCharacter Character used to escape the first value within
     *     a collection, if the value contains an operator symbol.
     * @param array<string> $ignoredPaths Reference paths which should be ignored while
     *     simplification is applied. Must be an exact match.
     * @param array<string> $ignoredPathsRx Reference paths which should be ignored while
     *     simplification is applied. PCRE patterns incl. delimiters, e.g. "/^prefix/".
     */
    public function __construct(
        array $operatorMapping = [],
        ?callable $referenceFrom = null,
        ?callable $referenceTo = null,
        public readonly string $escapeCharacter = self::DEFAULT_ESCAPE_CHARACTER,
        public readonly array $ignoredPaths = [],
        public readonly array $ignoredPathsRx = [],
    ) {
        $unknown = array_diff_key($operatorMapping, self::DEFAULT_OPERATOR_MAPPING);
        if ($unknown !== []) {
            throw new \InvalidArgumentException(
                'unknown operator kind(s): ' . implode(', ', array_keys($unknown))
            );
        }

        $this->operatorMapping = array_merge(self::DEFAULT_OPERATOR_MAPPING, $operatorMapping);
        $this->referenceFrom = $referenceFrom !== null
            ? \Closure::fromCallable($referenceFrom)
            : Reference::defaultSerializeFrom(...);
        $this->referenceTo = $referenceTo !== null
            ? \Closure::fromCallable($referenceTo)
            : Reference::defaultSerializeTo(...);
    }

    /**
     * Operator symbols, which should be escaped when used as the first item
     * of a collection.
     *
     * @return list<string>
     */
    public function escapedOperators(): array
    {
        return array_values($this->operatorMapping);
    }
}
