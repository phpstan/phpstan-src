<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/src/Reflection/ExtendedMethodReflection.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\Reflection\ExtendedMethodReflection
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-dea74c9af32bf65bd2fddd8d1e2aa145982d04195b0c70f9414ed0e1bfb352b4',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\Reflection\\ExtendedMethodReflection',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/src/Reflection/ExtendedMethodReflection.php',
      ),
    ),
    'namespace' => 'PHPStan\\Reflection',
    'name' => 'PHPStan\\Reflection\\ExtendedMethodReflection',
    'shortName' => 'ExtendedMethodReflection',
    'isInterface' => true,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Extended method reflection with additional metadata beyond MethodReflection.
 *
 * This interface exists to allow PHPStan to add new method query methods in minor
 * versions without breaking existing MethodsClassReflectionExtension implementations.
 * Extension developers should implement MethodReflection, not this interface — PHPStan
 * wraps MethodReflection implementations to provide ExtendedMethodReflection.
 *
 * Provides access to:
 * - Extended parameter signatures (ExtendedParametersAcceptor with PHPDoc/native types)
 * - Named argument variants (different signatures when using named arguments)
 * - Type assertions (@phpstan-assert annotations)
 * - Self-out types (@phpstan-self-out for fluent interfaces)
 * - Purity information (@phpstan-pure/@phpstan-impure)
 * - PHP attributes (including #[\\NoDiscard])
 * - Resolved PHPDoc block
 *
 * This is the return type of Type::getMethod() and Scope::getMethodReflection().
 *
 * @api
 * @api-do-not-implement
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 31,
    'endLine' => 83,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
      0 => 'PHPStan\\Reflection\\MethodReflection',
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
      'getVariants' => 
      array (
        'name' => 'getVariants',
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
        'docComment' => '/** @return list<ExtendedParametersAcceptor> */',
        'startLine' => 35,
        'endLine' => 35,
        'startColumn' => 2,
        'endColumn' => 38,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection',
        'declaringClassName' => 'PHPStan\\Reflection\\ExtendedMethodReflection',
        'implementingClassName' => 'PHPStan\\Reflection\\ExtendedMethodReflection',
        'currentClassName' => 'PHPStan\\Reflection\\ExtendedMethodReflection',
        'aliasName' => NULL,
      ),
      'getOnlyVariant' => 
      array (
        'name' => 'getOnlyVariant',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPStan\\Reflection\\ExtendedParametersAcceptor',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/** @internal */',
        'startLine' => 38,
        'endLine' => 38,
        'startColumn' => 2,
        'endColumn' => 62,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection',
        'declaringClassName' => 'PHPStan\\Reflection\\ExtendedMethodReflection',
        'implementingClassName' => 'PHPStan\\Reflection\\ExtendedMethodReflection',
        'currentClassName' => 'PHPStan\\Reflection\\ExtendedMethodReflection',
        'aliasName' => NULL,
      ),
      'getNamedArgumentsVariants' => 
      array (
        'name' => 'getNamedArgumentsVariants',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
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
                  'name' => 'array',
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
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Returns alternative signatures used when the method is called with named arguments.
 * Returns null if the named argument variants are the same as regular variants.
 *
 * @return list<ExtendedParametersAcceptor>|null
 */',
        'startLine' => 46,
        'endLine' => 46,
        'startColumn' => 2,
        'endColumn' => 53,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection',
        'declaringClassName' => 'PHPStan\\Reflection\\ExtendedMethodReflection',
        'implementingClassName' => 'PHPStan\\Reflection\\ExtendedMethodReflection',
        'currentClassName' => 'PHPStan\\Reflection\\ExtendedMethodReflection',
        'aliasName' => NULL,
      ),
      'acceptsNamedArguments' => 
      array (
        'name' => 'acceptsNamedArguments',
        'parameters' => 
        array (
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
        'docComment' => NULL,
        'startLine' => 48,
        'endLine' => 48,
        'startColumn' => 2,
        'endColumn' => 55,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection',
        'declaringClassName' => 'PHPStan\\Reflection\\ExtendedMethodReflection',
        'implementingClassName' => 'PHPStan\\Reflection\\ExtendedMethodReflection',
        'currentClassName' => 'PHPStan\\Reflection\\ExtendedMethodReflection',
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
            'name' => 'PHPStan\\Reflection\\Assertions',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 50,
        'endLine' => 50,
        'startColumn' => 2,
        'endColumn' => 42,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection',
        'declaringClassName' => 'PHPStan\\Reflection\\ExtendedMethodReflection',
        'implementingClassName' => 'PHPStan\\Reflection\\ExtendedMethodReflection',
        'currentClassName' => 'PHPStan\\Reflection\\ExtendedMethodReflection',
        'aliasName' => NULL,
      ),
      'getSelfOutType' => 
      array (
        'name' => 'getSelfOutType',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
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
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Used for fluent interfaces where calling a method changes the generic
 * type parameters of $this (e.g. a builder pattern).
 */',
        'startLine' => 56,
        'endLine' => 56,
        'startColumn' => 2,
        'endColumn' => 41,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection',
        'declaringClassName' => 'PHPStan\\Reflection\\ExtendedMethodReflection',
        'implementingClassName' => 'PHPStan\\Reflection\\ExtendedMethodReflection',
        'currentClassName' => 'PHPStan\\Reflection\\ExtendedMethodReflection',
        'aliasName' => NULL,
      ),
      'returnsByReference' => 
      array (
        'name' => 'returnsByReference',
        'parameters' => 
        array (
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
        'docComment' => NULL,
        'startLine' => 58,
        'endLine' => 58,
        'startColumn' => 2,
        'endColumn' => 52,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection',
        'declaringClassName' => 'PHPStan\\Reflection\\ExtendedMethodReflection',
        'implementingClassName' => 'PHPStan\\Reflection\\ExtendedMethodReflection',
        'currentClassName' => 'PHPStan\\Reflection\\ExtendedMethodReflection',
        'aliasName' => NULL,
      ),
      'isFinalByKeyword' => 
      array (
        'name' => 'isFinalByKeyword',
        'parameters' => 
        array (
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
        'docComment' => NULL,
        'startLine' => 60,
        'endLine' => 60,
        'startColumn' => 2,
        'endColumn' => 50,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection',
        'declaringClassName' => 'PHPStan\\Reflection\\ExtendedMethodReflection',
        'implementingClassName' => 'PHPStan\\Reflection\\ExtendedMethodReflection',
        'currentClassName' => 'PHPStan\\Reflection\\ExtendedMethodReflection',
        'aliasName' => NULL,
      ),
      'isAbstract' => 
      array (
        'name' => 'isAbstract',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
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
                  'name' => 'PHPStan\\TrinaryLogic',
                  'isIdentifier' => false,
                ),
              ),
              1 => 
              array (
                'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                'data' => 
                array (
                  'name' => 'bool',
                  'isIdentifier' => true,
                ),
              ),
            ),
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 62,
        'endLine' => 62,
        'startColumn' => 2,
        'endColumn' => 49,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection',
        'declaringClassName' => 'PHPStan\\Reflection\\ExtendedMethodReflection',
        'implementingClassName' => 'PHPStan\\Reflection\\ExtendedMethodReflection',
        'currentClassName' => 'PHPStan\\Reflection\\ExtendedMethodReflection',
        'aliasName' => NULL,
      ),
      'isBuiltin' => 
      array (
        'name' => 'isBuiltin',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
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
                  'name' => 'PHPStan\\TrinaryLogic',
                  'isIdentifier' => false,
                ),
              ),
              1 => 
              array (
                'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                'data' => 
                array (
                  'name' => 'bool',
                  'isIdentifier' => true,
                ),
              ),
            ),
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 64,
        'endLine' => 64,
        'startColumn' => 2,
        'endColumn' => 48,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection',
        'declaringClassName' => 'PHPStan\\Reflection\\ExtendedMethodReflection',
        'implementingClassName' => 'PHPStan\\Reflection\\ExtendedMethodReflection',
        'currentClassName' => 'PHPStan\\Reflection\\ExtendedMethodReflection',
        'aliasName' => NULL,
      ),
      'isPure' => 
      array (
        'name' => 'isPure',
        'parameters' => 
        array (
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
 * In most cases hasSideEffects() is more practical as it also accounts
 * for void return type (methods returning void are always impure).
 */',
        'startLine' => 70,
        'endLine' => 70,
        'startColumn' => 2,
        'endColumn' => 40,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection',
        'declaringClassName' => 'PHPStan\\Reflection\\ExtendedMethodReflection',
        'implementingClassName' => 'PHPStan\\Reflection\\ExtendedMethodReflection',
        'currentClassName' => 'PHPStan\\Reflection\\ExtendedMethodReflection',
        'aliasName' => NULL,
      ),
      'getAttributes' => 
      array (
        'name' => 'getAttributes',
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
        'docComment' => '/** @return list<AttributeReflection> */',
        'startLine' => 73,
        'endLine' => 73,
        'startColumn' => 2,
        'endColumn' => 40,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection',
        'declaringClassName' => 'PHPStan\\Reflection\\ExtendedMethodReflection',
        'implementingClassName' => 'PHPStan\\Reflection\\ExtendedMethodReflection',
        'currentClassName' => 'PHPStan\\Reflection\\ExtendedMethodReflection',
        'aliasName' => NULL,
      ),
      'mustUseReturnValue' => 
      array (
        'name' => 'mustUseReturnValue',
        'parameters' => 
        array (
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
 * On PHP 8.5+ if the return value is unused at runtime, a warning is emitted.
 * PHPStan reports this during analysis regardless of PHP version.
 */',
        'startLine' => 79,
        'endLine' => 79,
        'startColumn' => 2,
        'endColumn' => 52,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection',
        'declaringClassName' => 'PHPStan\\Reflection\\ExtendedMethodReflection',
        'implementingClassName' => 'PHPStan\\Reflection\\ExtendedMethodReflection',
        'currentClassName' => 'PHPStan\\Reflection\\ExtendedMethodReflection',
        'aliasName' => NULL,
      ),
      'getResolvedPhpDoc' => 
      array (
        'name' => 'getResolvedPhpDoc',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
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
                  'name' => 'PHPStan\\PhpDoc\\ResolvedPhpDocBlock',
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
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 81,
        'endLine' => 81,
        'startColumn' => 2,
        'endColumn' => 59,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection',
        'declaringClassName' => 'PHPStan\\Reflection\\ExtendedMethodReflection',
        'implementingClassName' => 'PHPStan\\Reflection\\ExtendedMethodReflection',
        'currentClassName' => 'PHPStan\\Reflection\\ExtendedMethodReflection',
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