<?php

declare(strict_types=1);

namespace Illogical;

/**
 * Evaluable expression, i.e. an operand or an expression.
 *
 * Context is either a raw (nested) data context, i.e. an array or an object,
 * or an already flattened context.
 */
interface Evaluable extends \Stringable
{
    /**
     * Evaluate in the given context.
     *
     * @param array<mixed>|object $context
     */
    public function evaluate(array|object $context): mixed;

    /**
     * Simplify in the given context. Returns an evaluated value if the expression
     * could be fully resolved, otherwise a simplified evaluable expression.
     *
     * @param array<mixed>|object $context
     */
    public function simplify(array|object $context): mixed;

    /**
     * Serialize to the raw expression form, i.e. reverse of the parsing.
     */
    public function serialize(): mixed;

    /**
     * Get the statement (text form) of the expression.
     */
    public function __toString(): string;
}
