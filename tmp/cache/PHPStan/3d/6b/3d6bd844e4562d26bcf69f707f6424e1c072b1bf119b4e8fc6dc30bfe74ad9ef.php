<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/src/Reflection/MethodReflection.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\Reflection\MethodReflection
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-412cb31e0144c1fc557180e81bb2613c8655c32b27d414b9fde08178a4cbcbea',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\Reflection\\MethodReflection',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/src/Reflection/MethodReflection.php',
      ),
    ),
    'namespace' => 'PHPStan\\Reflection',
    'name' => 'PHPStan\\Reflection\\MethodReflection',
    'shortName' => 'MethodReflection',
    'isInterface' => true,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Reflection for a class method.
 *
 * This is the interface extension developers should implement when creating custom
 * MethodsClassReflectionExtension implementations for magic methods (__call, etc.).
 *
 * Methods can have multiple "variants" (overloaded signatures) — for example,
 * built-in functions like `array_map` have different signatures depending on
 * the number of arguments. Each variant is a ParametersAcceptor.
 *
 * For additional method metadata (assertions, purity, named arguments, attributes),
 * see ExtendedMethodReflection which extends this interface.
 *
 * @api
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 23,
    'endLine' => 58,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
      0 => 'PHPStan\\Reflection\\ClassMemberReflection',
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
      'getName' => 
      array (
        'name' => 'getName',
        'parameters' => 
        array (
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
        'startLine' => 26,
        'endLine' => 26,
        'startColumn' => 2,
        'endColumn' => 35,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection',
        'declaringClassName' => 'PHPStan\\Reflection\\MethodReflection',
        'implementingClassName' => 'PHPStan\\Reflection\\MethodReflection',
        'currentClassName' => 'PHPStan\\Reflection\\MethodReflection',
        'aliasName' => NULL,
      ),
      'getPrototype' => 
      array (
        'name' => 'getPrototype',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPStan\\Reflection\\ClassMemberReflection',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * For methods that override a parent method, this returns the parent\'s
 * method reflection. For methods with no parent, returns itself.
 */',
        'startLine' => 32,
        'endLine' => 32,
        'startColumn' => 2,
        'endColumn' => 55,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection',
        'declaringClassName' => 'PHPStan\\Reflection\\MethodReflection',
        'implementingClassName' => 'PHPStan\\Reflection\\MethodReflection',
        'currentClassName' => 'PHPStan\\Reflection\\MethodReflection',
        'aliasName' => NULL,
      ),
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
        'docComment' => '/**
 * Most methods have a single variant. Built-in PHP functions with overloaded
 * signatures (e.g. different return types based on argument count) have multiple.
 *
 * @return list<ParametersAcceptor>
 */',
        'startLine' => 40,
        'endLine' => 40,
        'startColumn' => 2,
        'endColumn' => 38,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection',
        'declaringClassName' => 'PHPStan\\Reflection\\MethodReflection',
        'implementingClassName' => 'PHPStan\\Reflection\\MethodReflection',
        'currentClassName' => 'PHPStan\\Reflection\\MethodReflection',
        'aliasName' => NULL,
      ),
      'isDeprecated' => 
      array (
        'name' => 'isDeprecated',
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
        'startLine' => 42,
        'endLine' => 42,
        'startColumn' => 2,
        'endColumn' => 46,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection',
        'declaringClassName' => 'PHPStan\\Reflection\\MethodReflection',
        'implementingClassName' => 'PHPStan\\Reflection\\MethodReflection',
        'currentClassName' => 'PHPStan\\Reflection\\MethodReflection',
        'aliasName' => NULL,
      ),
      'getDeprecatedDescription' => 
      array (
        'name' => 'getDeprecatedDescription',
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
                  'name' => 'string',
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
        'docComment' => NULL,
        'startLine' => 44,
        'endLine' => 44,
        'startColumn' => 2,
        'endColumn' => 53,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection',
        'declaringClassName' => 'PHPStan\\Reflection\\MethodReflection',
        'implementingClassName' => 'PHPStan\\Reflection\\MethodReflection',
        'currentClassName' => 'PHPStan\\Reflection\\MethodReflection',
        'aliasName' => NULL,
      ),
      'isFinal' => 
      array (
        'name' => 'isFinal',
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
        'startLine' => 46,
        'endLine' => 46,
        'startColumn' => 2,
        'endColumn' => 41,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection',
        'declaringClassName' => 'PHPStan\\Reflection\\MethodReflection',
        'implementingClassName' => 'PHPStan\\Reflection\\MethodReflection',
        'currentClassName' => 'PHPStan\\Reflection\\MethodReflection',
        'aliasName' => NULL,
      ),
      'isInternal' => 
      array (
        'name' => 'isInternal',
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
        'endColumn' => 44,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection',
        'declaringClassName' => 'PHPStan\\Reflection\\MethodReflection',
        'implementingClassName' => 'PHPStan\\Reflection\\MethodReflection',
        'currentClassName' => 'PHPStan\\Reflection\\MethodReflection',
        'aliasName' => NULL,
      ),
      'getThrowType' => 
      array (
        'name' => 'getThrowType',
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
        'docComment' => NULL,
        'startLine' => 50,
        'endLine' => 50,
        'startColumn' => 2,
        'endColumn' => 39,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection',
        'declaringClassName' => 'PHPStan\\Reflection\\MethodReflection',
        'implementingClassName' => 'PHPStan\\Reflection\\MethodReflection',
        'currentClassName' => 'PHPStan\\Reflection\\MethodReflection',
        'aliasName' => NULL,
      ),
      'hasSideEffects' => 
      array (
        'name' => 'hasSideEffects',
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
 * Void methods are always considered impure since they must do something
 * to be useful.
 */',
        'startLine' => 56,
        'endLine' => 56,
        'startColumn' => 2,
        'endColumn' => 48,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection',
        'declaringClassName' => 'PHPStan\\Reflection\\MethodReflection',
        'implementingClassName' => 'PHPStan\\Reflection\\MethodReflection',
        'currentClassName' => 'PHPStan\\Reflection\\MethodReflection',
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