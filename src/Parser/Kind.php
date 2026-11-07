<?php

declare(strict_types=1);

namespace Illogical\Parser;

/**
 * Expression kind identifiers, used as keys of the operator mapping.
 */
final class Kind
{
    // Logical
    public const AND = 'AND';
    public const OR = 'OR';
    public const NOR = 'NOR';
    public const XOR = 'XOR';
    public const NOT = 'NOT';
    // Comparison
    public const EQ = 'EQ';
    public const NE = 'NE';
    public const GT = 'GT';
    public const GE = 'GE';
    public const LT = 'LT';
    public const LE = 'LE';
    public const IN = 'IN';
    public const NIN = 'NIN';
    public const OVERLAP = 'OVERLAP';
    public const PREFIX = 'PREFIX';
    public const SUFFIX = 'SUFFIX';
    public const NONE = 'NONE';
    public const PRESENT = 'PRESENT';

    private function __construct()
    {
    }
}
