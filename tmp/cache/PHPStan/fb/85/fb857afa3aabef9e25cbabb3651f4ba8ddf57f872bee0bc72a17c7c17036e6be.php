<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/src/Type/CompoundType.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\Type\CompoundType
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-649d1c6c2f241b933bcf7f76ca2e0ae91516457fc2139893ce38e34895572e72',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\Type\\CompoundType',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/CompoundType.php',
      ),
    ),
    'namespace' => 'PHPStan\\Type',
    'name' => 'PHPStan\\Type\\CompoundType',
    'shortName' => 'CompoundType',
    'isInterface' => true,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Marker interface for types that require bidirectional type comparison.
 *
 * Simple types like `StringType` or `IntegerType` can answer `isSuperTypeOf()`
 * and `accepts()` on their own — they check whether the incoming type fits.
 * But compound types (unions, intersections, mixed, never, accessory types,
 * integer ranges, callables, iterables, conditionals, etc.) need to be asked
 * from the other direction, because they carry internal structure that the
 * simple type on the other side knows nothing about.
 *
 * The protocol works like a double dispatch:
 *
 * 1. A simple type\'s `accepts()`/`isSuperTypeOf()` receives an argument.
 * 2. It checks `if ($type instanceof CompoundType)`.
 * 3. If true, it delegates to `$type->isAcceptedBy($this, …)` or `$type->isSubTypeOf($this)`.
 * 4. The compound type then decomposes itself (e.g., iterates union members)
 *    and calls back to the simple type for each component.
 *
 * This avoids the simple type having to understand union/intersection/mixed/never
 * semantics. For example, `StringType::accepts()` doesn\'t need to know how to
 * check a `UnionType<string|int>` — it just delegates to `UnionType::isAcceptedBy()`,
 * which iterates its members and asks `StringType::accepts()` for each one.
 *
 * Unlike `instanceof SomeSpecificType` checks (which are discouraged in CLAUDE.md),
 * `instanceof CompoundType` is the correct and intended pattern throughout the
 * type system. It is part of the double-dispatch protocol, not a type query.
 *
 * Implementations include:
 * - `UnionType` — `isSubTypeOf()` requires ALL members to be subtypes, `isAcceptedBy()` requires ALL to be accepted
 * - `IntersectionType` — `isSubTypeOf()` requires at least ONE member to be a subtype (via `maxMin`)
 * - `MixedType`, `NeverType` — terminal cases (mixed accepts everything, never is subtype of everything)
 * - All `AccessoryType` implementations — refinement types that live inside intersections
 * - `IntegerRangeType`, `CallableType`, `IterableType` — types with internal structure
 * - `ConditionalType`, `KeyOfType`, `ValueOfType`, etc. — late-resolvable types
 *
 * @api
 * @api-do-not-implement
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 46,
    'endLine' => 87,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
      0 => 'PHPStan\\Type\\Type',
    ),
    'traitClassNames' => 
    array (
    ),
    'immediateConstants' => 
    array (
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'isAcceptedBy' => 
      array (
        'name' => 'isAcceptedBy',
        'parameters' => 
        array (
          'acceptingType' => 
          array (
            'name' => 'acceptingType',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\Type\\Type',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 58,
            'endLine' => 58,
            'startColumn' => 31,
            'endColumn' => 49,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'strictTypes' => 
          array (
            'name' => 'strictTypes',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'bool',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 58,
            'endLine' => 58,
            'startColumn' => 52,
            'endColumn' => 68,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPStan\\Type\\AcceptsResult',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Answers "is this compound type accepted by $acceptingType?" from the compound type\'s perspective.
 *
 * Called by simple types when they encounter a CompoundType argument in their `accepts()` method.
 * The compound type decomposes itself and calls `$acceptingType->accepts()` for each component.
 *
 * For example, `UnionType(string|int)::isAcceptedBy(StringType)` asks StringType to accept
 * `string` and `int` separately, then combines results with `extremeIdentity` (all must pass).
 */',
        'startLine' => 58,
        'endLine' => 58,
        'startColumn' => 2,
        'endColumn' => 85,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type',
        'declaringClassName' => 'PHPStan\\Type\\CompoundType',
        'implementingClassName' => 'PHPStan\\Type\\CompoundType',
        'currentClassName' => 'PHPStan\\Type\\CompoundType',
        'aliasName' => NULL,
      ),
      'isSubTypeOf' => 
      array (
        'name' => 'isSubTypeOf',
        'parameters' => 
        array (
          'otherType' => 
          array (
            'name' => 'otherType',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\Type\\Type',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 69,
            'endLine' => 69,
            'startColumn' => 30,
            'endColumn' => 44,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPStan\\Type\\IsSuperTypeOfResult',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Answers "is this compound type a subtype of $otherType?" from the compound type\'s perspective.
 *
 * Called by simple types when they encounter a CompoundType argument in their `isSuperTypeOf()` method.
 * The compound type decomposes itself and calls `$otherType->isSuperTypeOf()` for each component.
 *
 * For example, `UnionType(string|int)::isSubTypeOf(MixedType)` asks MixedType whether it is
 * a supertype of `string` and `int` separately, then combines with `extremeIdentity` (all must pass).
 */',
        'startLine' => 69,
        'endLine' => 69,
        'startColumn' => 2,
        'endColumn' => 67,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type',
        'declaringClassName' => 'PHPStan\\Type\\CompoundType',
        'implementingClassName' => 'PHPStan\\Type\\CompoundType',
        'currentClassName' => 'PHPStan\\Type\\CompoundType',
        'aliasName' => NULL,
      ),
      'isGreaterThan' => 
      array (
        'name' => 'isGreaterThan',
        'parameters' => 
        array (
          'otherType' => 
          array (
            'name' => 'otherType',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\Type\\Type',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 78,
            'endLine' => 78,
            'startColumn' => 32,
            'endColumn' => 46,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'phpVersion' => 
          array (
            'name' => 'phpVersion',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\Php\\PhpVersion',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 78,
            'endLine' => 78,
            'startColumn' => 49,
            'endColumn' => 70,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPStan\\TrinaryLogic',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Compares this compound type against $otherType using greater-than semantics.
 *
 * Used for comparison operators (`>`). Each compound type decomposes the comparison
 * across its members (e.g., IntegerRangeType checks whether all values in the range
 * are greater than the other type).
 */',
        'startLine' => 78,
        'endLine' => 78,
        'startColumn' => 2,
        'endColumn' => 86,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type',
        'declaringClassName' => 'PHPStan\\Type\\CompoundType',
        'implementingClassName' => 'PHPStan\\Type\\CompoundType',
        'currentClassName' => 'PHPStan\\Type\\CompoundType',
        'aliasName' => NULL,
      ),
      'isGreaterThanOrEqual' => 
      array (
        'name' => 'isGreaterThanOrEqual',
        'parameters' => 
        array (
          'otherType' => 
          array (
            'name' => 'otherType',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\Type\\Type',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 85,
            'endLine' => 85,
            'startColumn' => 39,
            'endColumn' => 53,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'phpVersion' => 
          array (
            'name' => 'phpVersion',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\Php\\PhpVersion',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 85,
            'endLine' => 85,
            'startColumn' => 56,
            'endColumn' => 77,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPStan\\TrinaryLogic',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Compares this compound type against $otherType using greater-than-or-equal semantics.
 *
 * Used for comparison operators (`>=`). Same decomposition strategy as `isGreaterThan()`.
 */',
        'startLine' => 85,
        'endLine' => 85,
        'startColumn' => 2,
        'endColumn' => 93,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type',
        'declaringClassName' => 'PHPStan\\Type\\CompoundType',
        'implementingClassName' => 'PHPStan\\Type\\CompoundType',
        'currentClassName' => 'PHPStan\\Type\\CompoundType',
        'aliasName' => NULL,
      ),
    ),
    'traitsData' => 
    array (
      'aliases' => 
      array (
      ),
      'modifiers' => 
      array (
      ),
      'precedences' => 
      array (
      ),
      'hashes' => 
      array (
      ),
    ),
  ),
));