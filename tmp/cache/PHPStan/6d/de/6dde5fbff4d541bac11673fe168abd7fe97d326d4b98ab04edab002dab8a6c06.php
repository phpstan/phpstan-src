<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/src/Type/VerbosityLevel.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\Type\VerbosityLevel
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-2acf741e00fdd6cad83e596cd41bde30785fecb4f2d0618f733edce5f3ba7bdf',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\Type\\VerbosityLevel',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/VerbosityLevel.php',
      ),
    ),
    'namespace' => 'PHPStan\\Type',
    'name' => 'PHPStan\\Type\\VerbosityLevel',
    'shortName' => 'VerbosityLevel',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Controls the verbosity of type descriptions in error messages.
 *
 * When PHPStan describes a type for an error message, it uses VerbosityLevel to
 * decide how much detail to include. Higher levels include more detail like constant
 * values and array shapes.
 *
 * The four levels (from least to most verbose):
 * - **typeOnly**: Just the type name, e.g. "string", "array", "Foo"
 * - **value**: Includes constant values, e.g. "\'hello\'", "array{foo: int}", "non-empty-string"
 * - **precise**: Maximum detail — adds subtracted types on object/mixed (e.g. "object~Bar"),
 *   lowercase/uppercase string distinctions, untruncated array shapes, and template type scope
 * - **cache**: Internal level used for generating cache keys
 *
 * Used as a parameter to Type::describe() to control output detail:
 *
 *     $type->describe(VerbosityLevel::typeOnly())  // "string"
 *     $type->describe(VerbosityLevel::value())      // "\'hello\'"
 *     $type->describe(VerbosityLevel::precise())    // "non-empty-lowercase-string"
 *
 * The getRecommendedLevelByType() factory method automatically chooses the right level
 * for error messages based on what types are involved — it picks the minimum verbosity
 * needed to distinguish the accepting type from the accepted type.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 41,
    'endLine' => 280,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
    ),
    'traitClassNames' => 
    array (
    ),
    'immediateConstants' => 
    array (
      'TYPE_ONLY' => 
      array (
        'declaringClassName' => 'PHPStan\\Type\\VerbosityLevel',
        'implementingClassName' => 'PHPStan\\Type\\VerbosityLevel',
        'name' => 'TYPE_ONLY',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '1',
          'attributes' => 
          array (
            'startLine' => 44,
            'endLine' => 44,
            'startTokenPos' => 89,
            'startFilePos' => 1959,
            'endTokenPos' => 89,
            'endFilePos' => 1959,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 44,
        'endLine' => 44,
        'startColumn' => 2,
        'endColumn' => 29,
      ),
      'VALUE' => 
      array (
        'declaringClassName' => 'PHPStan\\Type\\VerbosityLevel',
        'implementingClassName' => 'PHPStan\\Type\\VerbosityLevel',
        'name' => 'VALUE',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '2',
          'attributes' => 
          array (
            'startLine' => 45,
            'endLine' => 45,
            'startTokenPos' => 100,
            'startFilePos' => 1985,
            'endTokenPos' => 100,
            'endFilePos' => 1985,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 45,
        'endLine' => 45,
        'startColumn' => 2,
        'endColumn' => 25,
      ),
      'PRECISE' => 
      array (
        'declaringClassName' => 'PHPStan\\Type\\VerbosityLevel',
        'implementingClassName' => 'PHPStan\\Type\\VerbosityLevel',
        'name' => 'PRECISE',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '3',
          'attributes' => 
          array (
            'startLine' => 46,
            'endLine' => 46,
            'startTokenPos' => 111,
            'startFilePos' => 2013,
            'endTokenPos' => 111,
            'endFilePos' => 2013,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 46,
        'endLine' => 46,
        'startColumn' => 2,
        'endColumn' => 27,
      ),
      'CACHE' => 
      array (
        'declaringClassName' => 'PHPStan\\Type\\VerbosityLevel',
        'implementingClassName' => 'PHPStan\\Type\\VerbosityLevel',
        'name' => 'CACHE',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '4',
          'attributes' => 
          array (
            'startLine' => 47,
            'endLine' => 47,
            'startTokenPos' => 122,
            'startFilePos' => 2039,
            'endTokenPos' => 122,
            'endFilePos' => 2039,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 47,
        'endLine' => 47,
        'startColumn' => 2,
        'endColumn' => 25,
      ),
    ),
    'immediateProperties' => 
    array (
      'registry' => 
      array (
        'declaringClassName' => 'PHPStan\\Type\\VerbosityLevel',
        'implementingClassName' => 'PHPStan\\Type\\VerbosityLevel',
        'name' => 'registry',
        'modifiers' => 20,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'default' => NULL,
        'docComment' => '/** @var self[] */',
        'attributes' => 
        array (
        ),
        'startLine' => 50,
        'endLine' => 50,
        'startColumn' => 2,
        'endColumn' => 32,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'value' => 
      array (
        'declaringClassName' => 'PHPStan\\Type\\VerbosityLevel',
        'implementingClassName' => 'PHPStan\\Type\\VerbosityLevel',
        'name' => 'value',
        'modifiers' => 4,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'int',
            'isIdentifier' => true,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 55,
        'endLine' => 55,
        'startColumn' => 31,
        'endColumn' => 48,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
    ),
    'immediateMethods' => 
    array (
      '__construct' => 
      array (
        'name' => '__construct',
        'parameters' => 
        array (
          'value' => 
          array (
            'name' => 'value',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'int',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 55,
            'endLine' => 55,
            'startColumn' => 31,
            'endColumn' => 48,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @param self::* $value
 */',
        'startLine' => 55,
        'endLine' => 57,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'PHPStan\\Type',
        'declaringClassName' => 'PHPStan\\Type\\VerbosityLevel',
        'implementingClassName' => 'PHPStan\\Type\\VerbosityLevel',
        'currentClassName' => 'PHPStan\\Type\\VerbosityLevel',
        'aliasName' => NULL,
      ),
      'create' => 
      array (
        'name' => 'create',
        'parameters' => 
        array (
          'value' => 
          array (
            'name' => 'value',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'int',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 62,
            'endLine' => 62,
            'startColumn' => 33,
            'endColumn' => 42,
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
            'name' => 'self',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @param self::* $value
 */',
        'startLine' => 62,
        'endLine' => 66,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 20,
        'namespace' => 'PHPStan\\Type',
        'declaringClassName' => 'PHPStan\\Type\\VerbosityLevel',
        'implementingClassName' => 'PHPStan\\Type\\VerbosityLevel',
        'currentClassName' => 'PHPStan\\Type\\VerbosityLevel',
        'aliasName' => NULL,
      ),
      'getLevelValue' => 
      array (
        'name' => 'getLevelValue',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'int',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/** @return self::* */',
        'startLine' => 69,
        'endLine' => 72,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type',
        'declaringClassName' => 'PHPStan\\Type\\VerbosityLevel',
        'implementingClassName' => 'PHPStan\\Type\\VerbosityLevel',
        'currentClassName' => 'PHPStan\\Type\\VerbosityLevel',
        'aliasName' => NULL,
      ),
      'typeOnly' => 
      array (
        'name' => 'typeOnly',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'self',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/** @api */',
        'startLine' => 75,
        'endLine' => 78,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'PHPStan\\Type',
        'declaringClassName' => 'PHPStan\\Type\\VerbosityLevel',
        'implementingClassName' => 'PHPStan\\Type\\VerbosityLevel',
        'currentClassName' => 'PHPStan\\Type\\VerbosityLevel',
        'aliasName' => NULL,
      ),
      'value' => 
      array (
        'name' => 'value',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'self',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/** @api */',
        'startLine' => 81,
        'endLine' => 84,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'PHPStan\\Type',
        'declaringClassName' => 'PHPStan\\Type\\VerbosityLevel',
        'implementingClassName' => 'PHPStan\\Type\\VerbosityLevel',
        'currentClassName' => 'PHPStan\\Type\\VerbosityLevel',
        'aliasName' => NULL,
      ),
      'precise' => 
      array (
        'name' => 'precise',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'self',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/** @api */',
        'startLine' => 87,
        'endLine' => 90,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'PHPStan\\Type',
        'declaringClassName' => 'PHPStan\\Type\\VerbosityLevel',
        'implementingClassName' => 'PHPStan\\Type\\VerbosityLevel',
        'currentClassName' => 'PHPStan\\Type\\VerbosityLevel',
        'aliasName' => NULL,
      ),
      'cache' => 
      array (
        'name' => 'cache',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'self',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Internal level for generating unique cache keys — not for user-facing messages.
 *
 * @api
 */',
        'startLine' => 97,
        'endLine' => 100,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'PHPStan\\Type',
        'declaringClassName' => 'PHPStan\\Type\\VerbosityLevel',
        'implementingClassName' => 'PHPStan\\Type\\VerbosityLevel',
        'currentClassName' => 'PHPStan\\Type\\VerbosityLevel',
        'aliasName' => NULL,
      ),
      'isTypeOnly' => 
      array (
        'name' => 'isTypeOnly',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 102,
        'endLine' => 105,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type',
        'declaringClassName' => 'PHPStan\\Type\\VerbosityLevel',
        'implementingClassName' => 'PHPStan\\Type\\VerbosityLevel',
        'currentClassName' => 'PHPStan\\Type\\VerbosityLevel',
        'aliasName' => NULL,
      ),
      'isValue' => 
      array (
        'name' => 'isValue',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 107,
        'endLine' => 110,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type',
        'declaringClassName' => 'PHPStan\\Type\\VerbosityLevel',
        'implementingClassName' => 'PHPStan\\Type\\VerbosityLevel',
        'currentClassName' => 'PHPStan\\Type\\VerbosityLevel',
        'aliasName' => NULL,
      ),
      'isPrecise' => 
      array (
        'name' => 'isPrecise',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 112,
        'endLine' => 115,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type',
        'declaringClassName' => 'PHPStan\\Type\\VerbosityLevel',
        'implementingClassName' => 'PHPStan\\Type\\VerbosityLevel',
        'currentClassName' => 'PHPStan\\Type\\VerbosityLevel',
        'aliasName' => NULL,
      ),
      'isCache' => 
      array (
        'name' => 'isCache',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 117,
        'endLine' => 120,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type',
        'declaringClassName' => 'PHPStan\\Type\\VerbosityLevel',
        'implementingClassName' => 'PHPStan\\Type\\VerbosityLevel',
        'currentClassName' => 'PHPStan\\Type\\VerbosityLevel',
        'aliasName' => NULL,
      ),
      'getRecommendedLevelByType' => 
      array (
        'name' => 'getRecommendedLevelByType',
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
            'startLine' => 127,
            'endLine' => 127,
            'startColumn' => 51,
            'endColumn' => 69,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'acceptedType' => 
          array (
            'name' => 'acceptedType',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 127,
                'endLine' => 127,
                'startTokenPos' => 486,
                'startFilePos' => 3472,
                'endTokenPos' => 486,
                'endFilePos' => 3475,
              ),
            ),
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionUnionType',
              'data' => 
              array (
                'types' => 
                array (
                  0 => 
                  array (
                    'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                    'data' => 
                    array (
                      'name' => 'PHPStan\\Type\\Type',
                      'isIdentifier' => false,
                    ),
                  ),
                  1 => 
                  array (
                    'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                    'data' => 
                    array (
                      'name' => 'null',
                      'isIdentifier' => true,
                    ),
                  ),
                ),
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 127,
            'endLine' => 127,
            'startColumn' => 72,
            'endColumn' => 97,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'self',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Chooses the minimum verbosity needed to distinguish the two types in error messages.
 *
 * @api
 */',
        'startLine' => 127,
        'endLine' => 238,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'PHPStan\\Type',
        'declaringClassName' => 'PHPStan\\Type\\VerbosityLevel',
        'implementingClassName' => 'PHPStan\\Type\\VerbosityLevel',
        'currentClassName' => 'PHPStan\\Type\\VerbosityLevel',
        'aliasName' => NULL,
      ),
      'handle' => 
      array (
        'name' => 'handle',
        'parameters' => 
        array (
          'typeOnlyCallback' => 
          array (
            'name' => 'typeOnlyCallback',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'callable',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 247,
            'endLine' => 247,
            'startColumn' => 3,
            'endColumn' => 28,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'valueCallback' => 
          array (
            'name' => 'valueCallback',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'callable',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 248,
            'endLine' => 248,
            'startColumn' => 3,
            'endColumn' => 25,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'preciseCallback' => 
          array (
            'name' => 'preciseCallback',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 249,
                'endLine' => 249,
                'startTokenPos' => 1217,
                'startFilePos' => 6703,
                'endTokenPos' => 1217,
                'endFilePos' => 6706,
              ),
            ),
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionUnionType',
              'data' => 
              array (
                'types' => 
                array (
                  0 => 
                  array (
                    'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                    'data' => 
                    array (
                      'name' => 'callable',
                      'isIdentifier' => true,
                    ),
                  ),
                  1 => 
                  array (
                    'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                    'data' => 
                    array (
                      'name' => 'null',
                      'isIdentifier' => true,
                    ),
                  ),
                ),
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 249,
            'endLine' => 249,
            'startColumn' => 3,
            'endColumn' => 35,
            'parameterIndex' => 2,
            'isOptional' => true,
          ),
          'cacheCallback' => 
          array (
            'name' => 'cacheCallback',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 250,
                'endLine' => 250,
                'startTokenPos' => 1227,
                'startFilePos' => 6738,
                'endTokenPos' => 1227,
                'endFilePos' => 6741,
              ),
            ),
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionUnionType',
              'data' => 
              array (
                'types' => 
                array (
                  0 => 
                  array (
                    'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                    'data' => 
                    array (
                      'name' => 'callable',
                      'isIdentifier' => true,
                    ),
                  ),
                  1 => 
                  array (
                    'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                    'data' => 
                    array (
                      'name' => 'null',
                      'isIdentifier' => true,
                    ),
                  ),
                ),
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 250,
            'endLine' => 250,
            'startColumn' => 3,
            'endColumn' => 33,
            'parameterIndex' => 3,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @param callable(): string $typeOnlyCallback
 * @param callable(): string $valueCallback
 * @param callable(): string|null $preciseCallback
 * @param callable(): string|null $cacheCallback
 */',
        'startLine' => 246,
        'endLine' => 278,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type',
        'declaringClassName' => 'PHPStan\\Type\\VerbosityLevel',
        'implementingClassName' => 'PHPStan\\Type\\VerbosityLevel',
        'currentClassName' => 'PHPStan\\Type\\VerbosityLevel',
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