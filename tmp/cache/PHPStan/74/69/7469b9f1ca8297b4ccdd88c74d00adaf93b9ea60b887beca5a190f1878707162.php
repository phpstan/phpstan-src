<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/src/Reflection/Assertions.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\Reflection\Assertions
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-c99e320b70a3fbdf2767a9985a81a57aef62cc87383f5544ad1c0a1405800bd5',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\Reflection\\Assertions',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/src/Reflection/Assertions.php',
      ),
    ),
    'namespace' => 'PHPStan\\Reflection',
    'name' => 'PHPStan\\Reflection\\Assertions',
    'shortName' => 'Assertions',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Collection of @phpstan-assert annotations on a function or method.
 *
 * PHPStan supports type assertions via PHPDoc annotations:
 * - `@phpstan-assert Type $param` — narrows the parameter type unconditionally
 * - `@phpstan-assert-if-true Type $param` — narrows when the method returns true
 * - `@phpstan-assert-if-false Type $param` — narrows when the method returns false
 *
 * This class collects all such assertions and provides methods to retrieve them
 * by condition type. It also handles negation: an `@phpstan-assert-if-true` assertion
 * is automatically negated and included in the `getAssertsIfFalse()` result.
 *
 * Returned by ExtendedMethodReflection::getAsserts() and FunctionReflection::getAsserts().
 *
 * @api
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 31,
    'endLine' => 189,
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
    ),
    'immediateProperties' => 
    array (
      'empty' => 
      array (
        'declaringClassName' => 'PHPStan\\Reflection\\Assertions',
        'implementingClassName' => 'PHPStan\\Reflection\\Assertions',
        'name' => 'empty',
        'modifiers' => 20,
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
                  'name' => 'self',
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
        'default' => 
        array (
          'code' => 'null',
          'attributes' => 
          array (
            'startLine' => 34,
            'endLine' => 34,
            'startTokenPos' => 92,
            'startFilePos' => 1120,
            'endTokenPos' => 92,
            'endFilePos' => 1123,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 34,
        'endLine' => 34,
        'startColumn' => 2,
        'endColumn' => 36,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'asserts' => 
      array (
        'declaringClassName' => 'PHPStan\\Reflection\\Assertions',
        'implementingClassName' => 'PHPStan\\Reflection\\Assertions',
        'name' => 'asserts',
        'modifiers' => 4,
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
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 39,
        'endLine' => 39,
        'startColumn' => 31,
        'endColumn' => 52,
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
          'asserts' => 
          array (
            'name' => 'asserts',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'array',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 39,
            'endLine' => 39,
            'startColumn' => 31,
            'endColumn' => 52,
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
 * @param AssertTag[] $asserts
 */',
        'startLine' => 39,
        'endLine' => 41,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'PHPStan\\Reflection',
        'declaringClassName' => 'PHPStan\\Reflection\\Assertions',
        'implementingClassName' => 'PHPStan\\Reflection\\Assertions',
        'currentClassName' => 'PHPStan\\Reflection\\Assertions',
        'aliasName' => NULL,
      ),
      'getAll' => 
      array (
        'name' => 'getAll',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/** @return AssertTag[] */',
        'startLine' => 44,
        'endLine' => 47,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection',
        'declaringClassName' => 'PHPStan\\Reflection\\Assertions',
        'implementingClassName' => 'PHPStan\\Reflection\\Assertions',
        'currentClassName' => 'PHPStan\\Reflection\\Assertions',
        'aliasName' => NULL,
      ),
      'getAsserts' => 
      array (
        'name' => 'getAsserts',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Unconditional assertions — narrow parameter types regardless of the method\'s return value.
 *
 * @return AssertTag[]
 */',
        'startLine' => 54,
        'endLine' => 57,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection',
        'declaringClassName' => 'PHPStan\\Reflection\\Assertions',
        'implementingClassName' => 'PHPStan\\Reflection\\Assertions',
        'currentClassName' => 'PHPStan\\Reflection\\Assertions',
        'aliasName' => NULL,
      ),
      'getAssertsIfTrue' => 
      array (
        'name' => 'getAssertsIfTrue',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Includes @phpstan-assert-if-true tags and negated @phpstan-assert-if-false tags.
 *
 * @return AssertTag[]
 */',
        'startLine' => 64,
        'endLine' => 73,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection',
        'declaringClassName' => 'PHPStan\\Reflection\\Assertions',
        'implementingClassName' => 'PHPStan\\Reflection\\Assertions',
        'currentClassName' => 'PHPStan\\Reflection\\Assertions',
        'aliasName' => NULL,
      ),
      'getAssertsIfFalse' => 
      array (
        'name' => 'getAssertsIfFalse',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Includes @phpstan-assert-if-false tags and negated @phpstan-assert-if-true tags.
 *
 * @return AssertTag[]
 */',
        'startLine' => 80,
        'endLine' => 89,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection',
        'declaringClassName' => 'PHPStan\\Reflection\\Assertions',
        'implementingClassName' => 'PHPStan\\Reflection\\Assertions',
        'currentClassName' => 'PHPStan\\Reflection\\Assertions',
        'aliasName' => NULL,
      ),
      'mapTypes' => 
      array (
        'name' => 'mapTypes',
        'parameters' => 
        array (
          'callable' => 
          array (
            'name' => 'callable',
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
            'startLine' => 92,
            'endLine' => 92,
            'startColumn' => 27,
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
            'name' => 'self',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/** @param callable(Type): Type $callable */',
        'startLine' => 92,
        'endLine' => 97,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection',
        'declaringClassName' => 'PHPStan\\Reflection\\Assertions',
        'implementingClassName' => 'PHPStan\\Reflection\\Assertions',
        'currentClassName' => 'PHPStan\\Reflection\\Assertions',
        'aliasName' => NULL,
      ),
      'intersectWith' => 
      array (
        'name' => 'intersectWith',
        'parameters' => 
        array (
          'other' => 
          array (
            'name' => 'other',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\Reflection\\Assertions',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 102,
            'endLine' => 102,
            'startColumn' => 32,
            'endColumn' => 48,
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
 * @deprecated use union() or intersect() instead
 */',
        'startLine' => 102,
        'endLine' => 105,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection',
        'declaringClassName' => 'PHPStan\\Reflection\\Assertions',
        'implementingClassName' => 'PHPStan\\Reflection\\Assertions',
        'currentClassName' => 'PHPStan\\Reflection\\Assertions',
        'aliasName' => NULL,
      ),
      'union' => 
      array (
        'name' => 'union',
        'parameters' => 
        array (
          'other' => 
          array (
            'name' => 'other',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\Reflection\\Assertions',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 107,
            'endLine' => 107,
            'startColumn' => 24,
            'endColumn' => 40,
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
        'docComment' => NULL,
        'startLine' => 107,
        'endLine' => 117,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection',
        'declaringClassName' => 'PHPStan\\Reflection\\Assertions',
        'implementingClassName' => 'PHPStan\\Reflection\\Assertions',
        'currentClassName' => 'PHPStan\\Reflection\\Assertions',
        'aliasName' => NULL,
      ),
      'intersect' => 
      array (
        'name' => 'intersect',
        'parameters' => 
        array (
          'other' => 
          array (
            'name' => 'other',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\Reflection\\Assertions',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 119,
            'endLine' => 119,
            'startColumn' => 28,
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
            'name' => 'self',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 119,
        'endLine' => 145,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection',
        'declaringClassName' => 'PHPStan\\Reflection\\Assertions',
        'implementingClassName' => 'PHPStan\\Reflection\\Assertions',
        'currentClassName' => 'PHPStan\\Reflection\\Assertions',
        'aliasName' => NULL,
      ),
      'getAssertKey' => 
      array (
        'name' => 'getAssertKey',
        'parameters' => 
        array (
          'assert' => 
          array (
            'name' => 'assert',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\PhpDoc\\Tag\\AssertTag',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 147,
            'endLine' => 147,
            'startColumn' => 39,
            'endColumn' => 55,
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
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 147,
        'endLine' => 155,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 20,
        'namespace' => 'PHPStan\\Reflection',
        'declaringClassName' => 'PHPStan\\Reflection\\Assertions',
        'implementingClassName' => 'PHPStan\\Reflection\\Assertions',
        'currentClassName' => 'PHPStan\\Reflection\\Assertions',
        'aliasName' => NULL,
      ),
      'create' => 
      array (
        'name' => 'create',
        'parameters' => 
        array (
          'asserts' => 
          array (
            'name' => 'asserts',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'array',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 160,
            'endLine' => 160,
            'startColumn' => 33,
            'endColumn' => 46,
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
 * @param AssertTag[] $asserts
 */',
        'startLine' => 160,
        'endLine' => 166,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 20,
        'namespace' => 'PHPStan\\Reflection',
        'declaringClassName' => 'PHPStan\\Reflection\\Assertions',
        'implementingClassName' => 'PHPStan\\Reflection\\Assertions',
        'currentClassName' => 'PHPStan\\Reflection\\Assertions',
        'aliasName' => NULL,
      ),
      'createEmpty' => 
      array (
        'name' => 'createEmpty',
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
        'docComment' => NULL,
        'startLine' => 168,
        'endLine' => 180,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'PHPStan\\Reflection',
        'declaringClassName' => 'PHPStan\\Reflection\\Assertions',
        'implementingClassName' => 'PHPStan\\Reflection\\Assertions',
        'currentClassName' => 'PHPStan\\Reflection\\Assertions',
        'aliasName' => NULL,
      ),
      'createFromResolvedPhpDocBlock' => 
      array (
        'name' => 'createFromResolvedPhpDocBlock',
        'parameters' => 
        array (
          'phpDocBlock' => 
          array (
            'name' => 'phpDocBlock',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\PhpDoc\\ResolvedPhpDocBlock',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 182,
            'endLine' => 182,
            'startColumn' => 55,
            'endColumn' => 86,
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
        'docComment' => NULL,
        'startLine' => 182,
        'endLine' => 187,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'PHPStan\\Reflection',
        'declaringClassName' => 'PHPStan\\Reflection\\Assertions',
        'implementingClassName' => 'PHPStan\\Reflection\\Assertions',
        'currentClassName' => 'PHPStan\\Reflection\\Assertions',
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