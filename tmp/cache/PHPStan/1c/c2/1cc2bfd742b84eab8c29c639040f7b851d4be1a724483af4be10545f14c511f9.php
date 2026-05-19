<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/src/Type/Accessory/AccessoryType.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\Type\Accessory\AccessoryType
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-3e4afca7eed1779bd56dd0a2a036a289e03ded9d1636da01cda91faa23454370',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\Type\\Accessory\\AccessoryType',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Accessory/AccessoryType.php',
      ),
    ),
    'namespace' => 'PHPStan\\Type\\Accessory',
    'name' => 'PHPStan\\Type\\Accessory\\AccessoryType',
    'shortName' => 'AccessoryType',
    'isInterface' => true,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Marker interface for types that refine a base type with additional constraints.
 *
 * An AccessoryType never stands alone — it always exists inside an `IntersectionType`
 * alongside a base type (like `StringType` or `ArrayType`). The base type provides
 * the fundamental type identity, and the AccessoryType adds a narrowing guarantee.
 *
 * For example, `non-empty-string` is represented as:
 *
 *     IntersectionType([StringType, AccessoryNonEmptyStringType])
 *
 * And `non-empty-list<int>` is represented as:
 *
 *     IntersectionType([ArrayType(int, int), NonEmptyArrayType, AccessoryArrayListType])
 *
 * Each AccessoryType implementation has a corresponding query method on the `Type` interface
 * that returns `TrinaryLogic`. This lets any code query the refinement without knowing
 * how it is represented internally:
 *
 * | AccessoryType class              | Corresponding Type method       |
 * |----------------------------------|---------------------------------|
 * | AccessoryNonEmptyStringType      | `isNonEmptyString()`            |
 * | AccessoryNonFalsyStringType      | `isNonFalsyString()`            |
 * | AccessoryLiteralStringType       | `isLiteralString()`             |
 * | AccessoryNumericStringType       | `isNumericString()`             |
 * | AccessoryLowercaseStringType     | `isLowercaseString()`           |
 * | AccessoryUppercaseStringType     | `isUppercaseString()`           |
 * | AccessoryArrayListType           | `isList()`                      |
 * | NonEmptyArrayType                | `isIterableAtLeastOnce()`       |
 * | OversizedArrayType               | `isOversizedArray()`            |
 * | HasMethodType                    | `hasMethod(string)`             |
 * | HasPropertyType                  | `hasProperty(string)`           |
 * | HasOffsetType                    | `hasOffsetValueType(Type)`      |
 * | HasOffsetValueType               | `getOffsetValueType(Type)`      |
 *
 * All implementations also implement `CompoundType`, so they participate in the
 * double-dispatch protocol for type comparison — simple types delegate to
 * `$type->isAcceptedBy()`/`$type->isSubTypeOf()` when they encounter an AccessoryType.
 *
 * The `instanceof AccessoryType` check is used in a few specific places:
 * - `IntersectionType::describe()` — skips AccessoryTypes when building base type names
 *   (they are rendered as type-level qualifiers like `non-empty-` instead)
 * - `IntersectionType::describeItself()` — separates base types from accessory types
 *   when composing the human-readable type description
 * - `UnionTypeHelper::sortTypes()` — sorts AccessoryTypes after base types
 * - `TypeCombinator` — handles AccessoryType intersection/union normalization
 * - `MissingTypehintCheck` — skips AccessoryTypes in typehint analysis
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 55,
    'endLine' => 68,
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
      'getDefaultBaseType' => 
      array (
        'name' => 'getDefaultBaseType',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPStan\\Type\\Type',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Returns the base type this accessory refines.
 *
 * Used when an accessory would otherwise end up in an `IntersectionType`
 * without an explicit base type — the returned type provides that base.
 * For example `AccessoryNonEmptyStringType` returns `string`, array accessories
 * return `array`, and offset accessories return `array|ArrayAccess`.
 */',
        'startLine' => 66,
        'endLine' => 66,
        'startColumn' => 2,
        'endColumn' => 44,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type\\Accessory',
        'declaringClassName' => 'PHPStan\\Type\\Accessory\\AccessoryType',
        'implementingClassName' => 'PHPStan\\Type\\Accessory\\AccessoryType',
        'currentClassName' => 'PHPStan\\Type\\Accessory\\AccessoryType',
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