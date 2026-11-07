# (php)illogical

A micro conditional engine used to parse the logical and comparison expressions, evaluate an expression in data context, and provide access to a text form of the given expression.

Other implementations:
- [TS/JS](https://github.com/spaceavocado/illogical)
- [Python](https://github.com/spaceavocado/pyillogical)
- [GO](https://github.com/spaceavocado/goillogical)
- [C#](https://github.com/spaceavocado/cillogical)
- [Java](https://github.com/spaceavocado/jillogical)
- [Odin](https://github.com/spaceavocado/oillogical)

## About

This project has been developed to provide PHP implementation of [spaceavocado/illogical](https://github.com/spaceavocado/illogical).

## Getting Started

You can install the **(php)illogical** via [Composer](https://getcomposer.org/):

```sh
composer require spaceavocado/illogical
```

The library is supported on PHP 8.1 and above, and has no runtime dependencies.

**Table of Content**

---

- [(php)illogical](#phpillogical)
  - [About](#about)
  - [Getting Started](#getting-started)
  - [Basic Usage](#basic-usage)
    - [Evaluate](#evaluate)
    - [Statement](#statement)
    - [Parse](#parse)
    - [Evaluable](#evaluable)
      - [Simplify](#simplify)
      - [Serialize](#serialize)
  - [Working with Expressions](#working-with-expressions)
    - [Evaluation Data Context](#evaluation-data-context)
      - [Accessing Array Element](#accessing-array-element)
      - [Accessing Array Element via Reference](#accessing-array-element-via-reference)
      - [Nested Referencing](#nested-referencing)
      - [Composite Reference Key](#composite-reference-key)
      - [Data Type Casting](#data-type-casting)
    - [Operand Types](#operand-types)
      - [Value](#value)
      - [Reference](#reference)
      - [Collection](#collection)
    - [Comparison Expressions](#comparison-expressions)
      - [Equal](#equal)
      - [Not Equal](#not-equal)
      - [Greater Than](#greater-than)
      - [Greater Than or Equal](#greater-than-or-equal)
      - [Less Than](#less-than)
      - [Less Than or Equal](#less-than-or-equal)
      - [In](#in)
      - [Not In](#not-in)
      - [Prefix](#prefix)
      - [Suffix](#suffix)
      - [Overlap](#overlap)
      - [None](#none)
      - [Present](#present)
    - [Logical Expressions](#logical-expressions)
      - [And](#and)
      - [Or](#or)
      - [Nor](#nor)
      - [Xor](#xor)
      - [Not](#not)
  - [Engine Options](#engine-options)
    - [Reference Serialize Options](#reference-serialize-options)
    - [Collection Serialize Options](#collection-serialize-options)
    - [Simplify Options](#simplify-options)
    - [Operator Mapping](#operator-mapping)
  - [Errors](#errors)
  - [Development](#development)
  - [Contributing](#contributing)
  - [License](#license)

---

## Basic Usage

```php
use Illogical\Illogical;

// Create a new instance of the engine
$illogical = new Illogical();

// Evaluate an expression
$illogical->evaluate(['==', 1, 1], []);
```

> For advanced usage, please see [Engine Options](#engine-options).

### Evaluate

Evaluate comparison or logical expression:

`$illogical->evaluate(`[Comparison Expression](#comparison-expressions) or [Logical Expression](#logical-expressions), [Evaluation Data Context](#evaluation-data-context)`)` => `bool`

**Example**

```php
$context = [
    'name' => 'peter',
];

// Comparison expression
$illogical->evaluate(['==', 5, 5], $context);
$illogical->evaluate(['==', 'circle', 'circle'], $context);
$illogical->evaluate(['==', true, true], $context);
$illogical->evaluate(['==', '$name', 'peter'], $context);
$illogical->evaluate(['NONE', '$RefA'], $context);

// Logical expression
$illogical->evaluate(['AND', ['==', 5, 5], ['==', 10, 10]], $context);
$illogical->evaluate(['AND', ['==', 'circle', 'circle'], ['==', 10, 10]], $context);
$illogical->evaluate(['OR', ['==', '$name', 'peter'], ['==', 5, 10]], $context);
```

### Statement

Get expression string representation:

`$illogical->statement(`[Comparison Expression](#comparison-expressions) or [Logical Expression](#logical-expressions)`)` => `string`

**Example**

```php
// Comparison expression

$illogical->statement(['==', 5, 5]);
// (5 == 5)

$illogical->statement(['==', 'circle', 'circle']);
// ("circle" == "circle")

$illogical->statement(['==', true, true]);
// (true == true)

$illogical->statement(['==', '$name', 'peter']);
// ({name} == "peter")

$illogical->statement(['NONE', '$RefA']);
// ({RefA} <is none>)

// Logical expression

$illogical->statement(['AND', ['==', 5, 5], ['==', 10, 10]]);
// ((5 == 5) AND (10 == 10))

$illogical->statement(['AND', ['==', 'circle', 'circle'], ['==', 10, 10]]);
// (("circle" == "circle") AND (10 == 10))

$illogical->statement(['OR', ['==', '$name', 'peter'], ['==', 5, 10]]);
// (({name} == "peter") OR (5 == 10))
```

### Parse

Parse the expression into an **Evaluable** object, i.e. it returns the parsed self-evaluable condition expression.

`$illogical->parse(`[Comparison Expression](#comparison-expressions) or [Logical Expression](#logical-expressions)`)` => `Illogical\Evaluable`

### Evaluable

- `$evaluable->evaluate($context)` please see [Evaluation Data Context](#evaluation-data-context).
- `$evaluable->simplify($context)` please see [Simplify](#simplify).
- `$evaluable->serialize()` please see [Serialize](#serialize).
- `(string) $evaluable` please see [Statement](#statement).

**Example**

```php
$evaluable = $illogical->parse(['==', '$name', 'peter']);

$evaluable->evaluate(['name' => 'peter']);
// true

echo $evaluable;
// ({name} == "peter")
```

#### Simplify

Simplifies an expression with a given context. This is useful when you already have some of
the properties of context and wants to try to evaluate the expression.

**Example**

```php
$evaluable = $illogical->parse(['AND', ['==', '$a', 10], ['==', '$b', 20]]);

$evaluable->simplify(['a' => 10]);
// ({b} == 20), i.e. an Evaluable

$evaluable->simplify(['a' => 20]);
// false
```

Values not found in the context will cause the parent operand not to be evaluated and returned
as part of the simplified expression.

In some situations we might want to evaluate the expression even if referred value is not
present. You can provide a list of keys that will be strictly evaluated even if they are not
present in the context.

**Example**

```php
use Illogical\Illogical;
use Illogical\Parser\Options;

$illogical = new Illogical(new Options(
    ignoredPaths: ['ignored'],
    ignoredPathsRx: ['/^ignored/'],
));

$evaluable = $illogical->parse(['AND', ['==', '$a', 10], ['==', '$ignored', 20]]);

$evaluable->simplify(['a' => 10]);
// ({ignored} == 20)
// "$ignored" will be kept as is, even if present in the context.
```

#### Serialize

Serializes an expression into the raw expression form, reverse the parse operation.

**Example**

```php
$evaluable = $illogical->parse(['AND', ['==', '$a', 10], ['==', 10, 20]]);

$evaluable->serialize();
// ['AND', ['==', '$a', 10], ['==', 10, 20]]
```

## Working with Expressions

### Evaluation Data Context

The evaluation data context is used to provide the expression with variable references, i.e. this allows for the dynamic expressions. The data context is an associative array (or an object, e.g. the `json_decode` output) with properties used as the references keys, and its values as reference values.

> Valid reference values: associative array, object (public properties), list, string, int, float, bool, null.

To reference the nested reference, please use "." delimiter, e.g.:
`$address.city`

#### Accessing Array Element

`$options[1]`

#### Accessing Array Element via Reference

`$options[{index}]`

- The **index** reference is resolved within the data context as an array index.

#### Nested Referencing

`$address.{segment}`

- The **segment** reference is resolved within the data context as a property key.

#### Composite Reference Key

`$shape{shapeType}`

- The **shapeType** reference is resolved within the data context, and inserted into the outer reference key.
- E.g. **shapeType** is resolved as "**B**" and would compose the **$shapeB** outer reference.
- This resolution could be n-nested.

#### Data Type Casting

`$payment.amount.(Type)`

Cast the given data context into the desired data type before being used as an operand in the evaluation.

> Note: If the conversion is invalid, the value is used as it is (except for Boolean, where invalid values are cast to `false`).

Supported data type conversions:

- .(String): cast a given reference to string.
- .(Number): cast a given reference to number (int or float).
- .(Integer): cast a given reference to int.
- .(Float): cast a given reference to float.
- .(Boolean): cast a given reference to bool.

Unsupported data type, e.g. `$ref.(Bogus)`, throws `Illogical\Operand\UnsupportedDataType` while parsing.

**Example**

```php
// Data context
$context = [
    'name' => 'peter',
    'country' => 'canada',
    'age' => 21,
    'options' => [1, 2, 3],
    'address' => [
        'city' => 'Toronto',
        'country' => 'Canada',
    ],
    'index' => 2,
    'segment' => 'city',
    'shapeA' => 'box',
    'shapeB' => 'circle',
    'shapeType' => 'B',
];

// Evaluate an expression in the given data context

$illogical->evaluate(['>', '$age', 20], $context);
// true

$illogical->evaluate(['==', '$address.city', 'Toronto'], $context);
// true

// Accessing Array Element
$illogical->evaluate(['==', '$options[1]', 2], $context);
// true

// Accessing Array Element via Reference
$illogical->evaluate(['==', '$options[{index}]', 3], $context);
// true

// Nested Referencing
$illogical->evaluate(['==', '$address.{segment}', 'Toronto'], $context);
// true

// Composite Reference Key
$illogical->evaluate(['==', '$shape{shapeType}', 'circle'], $context);
// true

// Data Type Casting
$illogical->evaluate(['==', '$age.(String)', '21'], $context);
// true
```

### Operand Types

The [Comparison Expression](#comparison-expressions) expect operands to be one of the below:

#### Value

Simple value types: string, int, float, bool, null.

**Example**

```php
$val1 = 5;
$var2 = 'circle';
$var3 = true;

$illogical->parse(['AND', ['==', $val1, $var2], ['==', $var3, $var3]]);
```

#### Reference

The reference operand value is resolved from the [Evaluation Data Context](#evaluation-data-context), where the operands name is used as key in the context.

The reference operand must be prefixed with `$` symbol, e.g.: `$name`. This might be customized via [Reference Serialize Options](#reference-serialize-options).

**Example**

| Expression                     | Data Context            |
| ------------------------------ | ----------------------- |
| `['==', '$age', 21]`           | `['age' => 21]`         |
| `['==', 'circle', '$shape']`   | `['shape' => 'circle']` |
| `['==', '$visible', true]`     | `['visible' => true]`   |

#### Collection

The operand could be a list mixed from [Value](#value) and [Reference](#reference).

**Example**

| Expression                                 | Data Context                                |
| ------------------------------------------ | ------------------------------------------- |
| `['IN', [1, 2], 1]`                        | `[]`                                        |
| `['IN', 'circle', ['$shapeA', '$shapeB']]` | `['shapeA' => 'circle', 'shapeB' => 'box']` |
| `['IN', ['$number', 5], 5]`                | `['number' => 3]`                           |

### Comparison Expressions

#### Equal

Expression format: `['==', `[Left Operand](#operand-types), [Right Operand](#operand-types)`]`.

> Valid operand types: string, int, float, bool, null. Strict comparison, i.e. `1` and `1.0` are not equal.

```php
$illogical->evaluate(['==', 5, 5], $context);
// true
```

#### Not Equal

Expression format: `['!=', `[Left Operand](#operand-types), [Right Operand](#operand-types)`]`.

> Valid operand types: string, int, float, bool, null.

```php
$illogical->evaluate(['!=', 'circle', 'square'], $context);
// true
```

#### Greater Than

Expression format: `['>', `[Left Operand](#operand-types), [Right Operand](#operand-types)`]`.

> Valid operand types: int, float, or both strings. Non comparable operands are evaluated as `false`.

```php
$illogical->evaluate(['>', 10, 5], $context);
// true
```

#### Greater Than or Equal

Expression format: `['>=', `[Left Operand](#operand-types), [Right Operand](#operand-types)`]`.

> Valid operand types: int, float, or both strings.

```php
$illogical->evaluate(['>=', 5, 5], $context);
// true
```

#### Less Than

Expression format: `['<', `[Left Operand](#operand-types), [Right Operand](#operand-types)`]`.

> Valid operand types: int, float, or both strings.

```php
$illogical->evaluate(['<', 5, 10], $context);
// true
```

#### Less Than or Equal

Expression format: `['<=', `[Left Operand](#operand-types), [Right Operand](#operand-types)`]`.

> Valid operand types: int, float, or both strings.

```php
$illogical->evaluate(['<=', 5, 5], $context);
// true
```

#### In

Expression format: `['IN', `[Left Operand](#operand-types), [Right Operand](#operand-types)`]`.

> Valid operand types: one operand is a value (string, int, float, bool, null), the other one is a collection.

```php
$illogical->evaluate(['IN', 5, [1, 2, 3, 4, 5]], $context);
// true

$illogical->evaluate(['IN', ['circle', 'square', 'triangle'], 'square'], $context);
// true
```

#### Not In

Expression format: `['NOT IN', `[Left Operand](#operand-types), [Right Operand](#operand-types)`]`.

> Valid operand types: one operand is a value (string, int, float, bool, null), the other one is a collection.

```php
$illogical->evaluate(['NOT IN', 10, [1, 2, 3, 4, 5]], $context);
// true

$illogical->evaluate(['NOT IN', ['circle', 'square', 'triangle'], 'oval'], $context);
// true
```

#### Prefix

Expression format: `['PREFIX', `[Left Operand](#operand-types), [Right Operand](#operand-types)`]`.

> Valid operand types: string.

- Left operand is the PREFIX term.
- Right operand is the tested word.

```php
$illogical->evaluate(['PREFIX', 'hemi', 'hemisphere'], $context);
// true

$illogical->evaluate(['PREFIX', 'hemi', 'sphere'], $context);
// false
```

#### Suffix

Expression format: `['SUFFIX', `[Left Operand](#operand-types), [Right Operand](#operand-types)`]`.

> Valid operand types: string.

- Left operand is the tested word.
- Right operand is the SUFFIX term.

```php
$illogical->evaluate(['SUFFIX', 'establishment', 'ment'], $context);
// true

$illogical->evaluate(['SUFFIX', 'establish', 'ment'], $context);
// false
```

#### Overlap

Expression format: `['OVERLAP', `[Left Operand](#operand-types), [Right Operand](#operand-types)`]`.

> Valid operand types: collections of (string, int, float, bool, null).

```php
$illogical->evaluate(['OVERLAP', [1, 2, 6], [1, 2, 3, 4, 5]], $context);
// true

$illogical->evaluate(['OVERLAP', ['circle', 'square', 'triangle'], ['square', 'oval']], $context);
// true
```

#### None

Evaluates as TRUE when the operand is missing or NULL.

Expression format: `['NONE', `[Reference Operand](#reference)`]`.

```php
$illogical->evaluate(['NONE', '$RefA'], []);
// true

$illogical->evaluate(['NONE', '$RefA'], ['RefA' => 10]);
// false
```

#### Present

Evaluates as FALSE when the operand is missing or NULL.

Expression format: `['PRESENT', `[Reference Operand](#reference)`]`.

```php
$illogical->evaluate(['PRESENT', '$RefA'], []);
// false

$illogical->evaluate(['PRESENT', '$RefA'], ['RefA' => 10]);
// true

$illogical->evaluate(['PRESENT', '$RefA'], ['RefA' => false]);
// true

$illogical->evaluate(['PRESENT', '$RefA'], ['RefA' => 'val']);
// true
```

### Logical Expressions

All logical operands must be evaluated as bool values, otherwise `Illogical\Expression\Logical\InvalidLogicalExpressionOperand` is thrown.

#### And

The logical AND operator returns the bool value TRUE if all operands are TRUE and returns FALSE otherwise.

Expression format: `['AND', Left Operand 1, Right Operand 2, ... , Right Operand N]`.

> Valid operand types: [Comparison Expression](#comparison-expressions) or [Nested Logical Expression](#logical-expressions).

```php
$illogical->evaluate(['AND', ['==', 5, 5], ['==', 10, 10]], $context);
// true
```

#### Or

The logical OR operator returns the bool value TRUE if any of the operands is TRUE and returns FALSE otherwise.

Expression format: `['OR', Left Operand 1, Right Operand 2, ... , Right Operand N]`.

> Valid operand types: [Comparison Expression](#comparison-expressions) or [Nested Logical Expression](#logical-expressions).

```php
$illogical->evaluate(['OR', ['==', 5, 5], ['==', 10, 5]], $context);
// true
```

#### Nor

The logical NOR operator returns the bool value TRUE if all operands are FALSE and returns FALSE otherwise.

Expression format: `['NOR', Left Operand 1, Right Operand 2, ... , Right Operand N]`

> Valid operand types: [Comparison Expression](#comparison-expressions) or [Nested Logical Expression](#logical-expressions).

```php
$illogical->evaluate(['NOR', ['==', 5, 1], ['==', 10, 5]], $context);
// true
```

#### Xor

The logical XOR operator returns the bool value TRUE if exactly one operand is TRUE and returns FALSE otherwise.

Expression format: `['XOR', Left Operand 1, Right Operand 2, ... , Right Operand N]`

> Valid operand types: [Comparison Expression](#comparison-expressions) or [Nested Logical Expression](#logical-expressions).

```php
$illogical->evaluate(['XOR', ['==', 5, 5], ['==', 10, 5]], $context);
// true

$illogical->evaluate(['XOR', ['==', 5, 5], ['==', 10, 10]], $context);
// false
```

#### Not

The logical NOT operator returns the bool value TRUE if the operand is FALSE, FALSE otherwise.

Expression format: `['NOT', Operand]`

> Valid operand types: [Comparison Expression](#comparison-expressions) or [Nested Logical Expression](#logical-expressions).

```php
$illogical->evaluate(['NOT', ['==', 5, 5]], $context);
// false
```

## Engine Options

All options are passed via `Illogical\Parser\Options`, ideally using named arguments.

### Reference Serialize Options

**Usage**

```php
use Illogical\Illogical;
use Illogical\Parser\Options;

$illogical = new Illogical(new Options(
    referenceFrom: fn (string $operand): ?string => str_starts_with($operand, '__') ? substr($operand, 2) : null,
    referenceTo: fn (string $address): string => "__{$address}",
));
```

#### From

A function used to determine if the operand is a reference type, otherwise evaluated as a static value.

```php
callable(string $operand): ?string
```

**Return value:**

- `string` = reference type, the reference address, i.e. annotation stripped form, e.g. `$State` => `State`.
- `null` = value type

> **Default reference predicate:**
> The `$` symbol at the begging of the operand is used to predicate the reference type, e.g. `$State`, `$Country`.

#### To

A function used to transform the reference address back into the annotated form, used by the [Serialize](#serialize). E.g. `Reference` => `$Reference`.

```php
callable(string $address): string
```

> **Default reference transform:**
> It prepends the `$` symbol to the reference address.

### Collection Serialize Options

**Usage**

```php
use Illogical\Illogical;
use Illogical\Parser\Options;

$illogical = new Illogical(new Options(escapeCharacter: '\\'));
```

#### Escape Character

Character used to escape the first value within a collection, if the value contains an operator symbol.

**Example**
- `['==', 1, 1]` // interpreted as EQ expression
- `['\==', 1, 1]` // interpreted as a collection

> **Default escape character:**
> `\`

### Simplify Options

Options applied while an expression is being simplified.

**Usage**

```php
use Illogical\Illogical;
use Illogical\Parser\Options;

$illogical = new Illogical(new Options(
    ignoredPaths: ['ignored'],
    ignoredPathsRx: ['/^prefix/'],
));
```

#### Ignored Paths

Reference paths which should be ignored while simplification is applied. Must be an exact match.

#### Ignored Paths RegEx

Reference paths which should be ignored while simplification is applied. Matching PCRE patterns, incl. the delimiters.

### Operator Mapping

Mapping of the operators. The key is unique operator kind (`Illogical\Parser\Kind`), and the value is the symbol used to represent the given operator in the raw expression. The given mapping is merged into the default mapping, i.e. only the overridden operators must be provided.

**Usage**

```php
use Illogical\Illogical;
use Illogical\Parser\Kind;
use Illogical\Parser\Options;

$illogical = new Illogical(new Options(operatorMapping: [Kind::EQ => 'IS']));

$illogical->evaluate(['IS', 1, 1]);
// true
```

**Default operator mapping** (`Options::DEFAULT_OPERATOR_MAPPING`):

```php
[
    // Logical
    Kind::AND     => 'AND',
    Kind::OR      => 'OR',
    Kind::NOR     => 'NOR',
    Kind::XOR     => 'XOR',
    Kind::NOT     => 'NOT',
    // Comparison
    Kind::EQ      => '==',
    Kind::NE      => '!=',
    Kind::GT      => '>',
    Kind::GE      => '>=',
    Kind::LT      => '<',
    Kind::LE      => '<=',
    Kind::NONE    => 'NONE',
    Kind::PRESENT => 'PRESENT',
    Kind::IN      => 'IN',
    Kind::NIN     => 'NOT IN',
    Kind::OVERLAP => 'OVERLAP',
    Kind::PREFIX  => 'PREFIX',
    Kind::SUFFIX  => 'SUFFIX',
]
```

## Errors

| Exception | Thrown when |
| --- | --- |
| `Illogical\Parser\UnexpectedExpressionInput` | The parsed expression is `null`. |
| `Illogical\Parser\UnexpectedOperand` | Empty collection, non-list array, non-primitive value, or a missing operand of a unary/binary expression. |
| `Illogical\Operand\UnsupportedDataType` | Unsupported reference data type casting, e.g. `$ref.(Bogus)`. |
| `Illogical\Expression\Logical\InvalidLogicalExpression` | Non unary logical expression has less than 2 operands. |
| `Illogical\Expression\Logical\InvalidLogicalExpressionOperand` | A logical expression operand is not evaluated as bool. |

## Development

```sh
composer install
composer test      # PHPUnit
composer analyse   # PHPStan
```

---

## Contributing

See [contributing.md](contributing.md).

## License

Illogical is released under the MIT license. See [LICENSE.md](LICENSE.md).
