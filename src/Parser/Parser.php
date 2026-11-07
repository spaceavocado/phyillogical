<?php

declare(strict_types=1);

namespace Illogical\Parser;

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
use Illogical\Primitive;

/**
 * Raw expression parser.
 */
final class Parser
{
    private const UNARY = 1;
    private const BINARY = 2;

    /** @var array<string, \Closure(list<Evaluable>): Evaluable> [symbol => handler] */
    private readonly array $handlers;

    /** @var list<string> */
    private readonly array $escapedOperators;

    public function __construct(private readonly Options $options = new Options())
    {
        $this->handlers = self::createHandlers($options->operatorMapping);
        $this->escapedOperators = $options->escapedOperators();
    }

    /**
     * Parse the raw expression into an evaluable.
     *
     * @throws UnexpectedExpressionInput
     * @throws UnexpectedOperand
     */
    public function parse(mixed $expression): Evaluable
    {
        if ($expression === null) {
            throw new UnexpectedExpressionInput('expression input cannot be null');
        }

        return $this->parseExpression($expression);
    }

    /**
     * Is the value escaped predicate.
     */
    public static function isEscaped(string $value, string $escapeCharacter): bool
    {
        return $escapeCharacter !== '' && str_starts_with($value, $escapeCharacter);
    }

    private function parseExpression(mixed $expression): Evaluable
    {
        if (!is_array($expression) || count($expression) < 2) {
            return $this->createOperand($expression);
        }

        if (!array_is_list($expression)) {
            throw new UnexpectedOperand('expression must be a list');
        }

        if (is_string($expression[0]) && self::isEscaped($expression[0], $this->options->escapeCharacter)) {
            $expression[0] = substr($expression[0], strlen($this->options->escapeCharacter));

            return $this->createOperand($expression);
        }

        $handler = is_string($expression[0]) ? ($this->handlers[$expression[0]] ?? null) : null;
        if ($handler === null) {
            return $this->createOperand($expression);
        }

        return $handler(array_map($this->parseExpression(...), array_slice($expression, 1)));
    }

    private function createOperand(mixed $value): Evaluable
    {
        if (is_array($value)) {
            if (count($value) === 0) {
                throw new UnexpectedOperand('collection operand must have at least 1 item');
            }
            if (!array_is_list($value)) {
                throw new UnexpectedOperand('collection operand must be a list');
            }

            return new Collection(
                array_map($this->parseExpression(...), $value),
                $this->options->escapeCharacter,
                $this->escapedOperators,
            );
        }

        $address = is_string($value) ? ($this->options->referenceFrom)($value) : null;
        if ($address !== null && $address !== '') {
            return new Reference(
                $address,
                $this->options->referenceTo,
                $this->options->ignoredPaths,
                $this->options->ignoredPathsRx,
            );
        }

        if (!Primitive::is($value)) {
            throw new UnexpectedOperand(
                'value operand must be a primitive value: string, int, float, bool or null, got '
                . get_debug_type($value)
            );
        }

        /** @var string|int|float|bool|null $value */
        return new Value($value);
    }

    /**
     * @param array<string, string> $mapping
     *
     * @return array<string, \Closure(list<Evaluable>): Evaluable>
     */
    private static function createHandlers(array $mapping): array
    {
        $not = $mapping[Kind::NOT];
        $nor = $mapping[Kind::NOR];

        $factories = [
            // Comparison
            Kind::EQ => [self::BINARY, static fn (string $s, Evaluable ...$o) => new Eq($o[0], $o[1], $s)],
            Kind::NE => [self::BINARY, static fn (string $s, Evaluable ...$o) => new Ne($o[0], $o[1], $s)],
            Kind::GT => [self::BINARY, static fn (string $s, Evaluable ...$o) => new Gt($o[0], $o[1], $s)],
            Kind::GE => [self::BINARY, static fn (string $s, Evaluable ...$o) => new Ge($o[0], $o[1], $s)],
            Kind::LT => [self::BINARY, static fn (string $s, Evaluable ...$o) => new Lt($o[0], $o[1], $s)],
            Kind::LE => [self::BINARY, static fn (string $s, Evaluable ...$o) => new Le($o[0], $o[1], $s)],
            Kind::IN => [self::BINARY, static fn (string $s, Evaluable ...$o) => new In($o[0], $o[1], $s)],
            Kind::NIN => [self::BINARY, static fn (string $s, Evaluable ...$o) => new Nin($o[0], $o[1], $s)],
            Kind::OVERLAP => [self::BINARY, static fn (string $s, Evaluable ...$o) => new Overlap($o[0], $o[1], $s)],
            Kind::PREFIX => [self::BINARY, static fn (string $s, Evaluable ...$o) => new Prefix($o[0], $o[1], $s)],
            Kind::SUFFIX => [self::BINARY, static fn (string $s, Evaluable ...$o) => new Suffix($o[0], $o[1], $s)],
            Kind::NONE => [self::UNARY, static fn (string $s, Evaluable ...$o) => new None($o[0], $s)],
            Kind::PRESENT => [self::UNARY, static fn (string $s, Evaluable ...$o) => new Present($o[0], $s)],
            // Logical
            Kind::NOT => [self::UNARY, static fn (string $s, Evaluable ...$o) => new LogicalNot($o[0], $s)],
            Kind::AND => [0, static fn (string $s, Evaluable ...$o) => new LogicalAnd($o, $s)],
            Kind::OR => [0, static fn (string $s, Evaluable ...$o) => new LogicalOr($o, $s)],
            Kind::NOR => [0, static fn (string $s, Evaluable ...$o) => new LogicalNor($o, $s, $not)],
            Kind::XOR => [0, static fn (string $s, Evaluable ...$o) => new LogicalXor($o, $s, $not, $nor)],
        ];

        $handlers = [];
        foreach ($mapping as $kind => $symbol) {
            [$arity, $factory] = $factories[$kind];
            $handlers[$symbol] = static function (array $operands) use ($symbol, $arity, $factory): Evaluable {
                if ($arity > 0 && count($operands) < $arity) {
                    throw new UnexpectedOperand(sprintf(
                        '"%s" expression expects %d operand(s), got %d',
                        $symbol,
                        $arity,
                        count($operands),
                    ));
                }

                return $factory($symbol, ...$operands);
            };
        }

        return $handlers;
    }
}
