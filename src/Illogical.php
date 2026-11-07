<?php

declare(strict_types=1);

namespace Illogical;

use Illogical\Parser\Options;
use Illogical\Parser\Parser;

/**
 * A micro conditional engine used to parse the logical and comparison expressions,
 * evaluate an expression in data context, and provide access to a text form of the
 * given expression.
 */
final class Illogical
{
    private readonly Parser $parser;

    public function __construct(Options $options = new Options())
    {
        $this->parser = new Parser($options);
    }

    /**
     * Parse the given expression into an Evaluable object, i.e. it returns the
     * parsed self-evaluable condition expression.
     *
     * Example:
     *
     * $evaluable = $illogical->parse(['==', '$name', 'peter']);
     *
     * $evaluable->evaluate(['name' => 'peter']);
     * // true
     *
     * (string) $evaluable;
     * // ({name} == "peter")
     */
    public function parse(mixed $expression): Evaluable
    {
        return $this->parser->parse($expression);
    }

    /**
     * Evaluate the expression in the given context.
     *
     * Example:
     *
     * $context = ['name' => 'peter'];
     *
     * $illogical->evaluate(['==', 5, 5], $context);
     * // true
     *
     * $illogical->evaluate(['AND', ['==', 5, 5], ['==', '$name', 'john']], $context);
     * // false
     *
     * @param array<mixed>|object $context
     */
    public function evaluate(mixed $expression, array|object $context = []): mixed
    {
        return $this->parse($expression)->evaluate(FlattenContext::from($context));
    }

    /**
     * Simplify the expression in the given context. This is useful when you already
     * have some of the properties of context and want to try to evaluate the
     * expression.
     *
     * Example:
     *
     * $illogical->simplify(['AND', ['==', '$a', 10], ['==', '$b', 20]], ['a' => 10]);
     * // ({b} == 20)
     *
     * $illogical->simplify(['AND', ['==', '$a', 10], ['==', '$b', 20]], ['a' => 20]);
     * // false
     *
     * @param array<mixed>|object $context
     */
    public function simplify(mixed $expression, array|object $context = []): mixed
    {
        return $this->parse($expression)->simplify(FlattenContext::from($context));
    }

    /**
     * Get the expression statement, i.e. text form.
     *
     * Example:
     *
     * $illogical->statement(['==', 5, 5]);
     * // (5 == 5)
     *
     * $illogical->statement(['AND', ['==', 5, 5], ['==', 10, 10]]);
     * // ((5 == 5) AND (10 == 10))
     */
    public function statement(mixed $expression): string
    {
        return (string) $this->parse($expression);
    }
}
