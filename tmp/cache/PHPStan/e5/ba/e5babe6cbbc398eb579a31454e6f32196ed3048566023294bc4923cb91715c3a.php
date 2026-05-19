<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/src/Rules/MissingTypehintCheck.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\Rules\MissingTypehintCheck
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-ebf6f11483e0cf7807952ba4b2d2a6bd58ca1acafe1bd685f7eaac5265f39115',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\Rules\\MissingTypehintCheck',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/src/Rules/MissingTypehintCheck.php',
      ),
    ),
    'namespace' => 'PHPStan\\Rules',
    'name' => 'PHPStan\\Rules\\MissingTypehintCheck',
    'shortName' => 'MissingTypehintCheck',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => NULL,
    'attributes' => 
    array (
      0 => 
      array (
        'name' => 'PHPStan\\DependencyInjection\\AutowiredService',
        'isRepeated' => false,
        'arguments' => 
        array (
        ),
      ),
    ),
    'startLine' => 36,
    'endLine' => 214,
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
      'MISSING_ITERABLE_VALUE_TYPE_TIP' => 
      array (
        'declaringClassName' => 'PHPStan\\Rules\\MissingTypehintCheck',
        'implementingClassName' => 'PHPStan\\Rules\\MissingTypehintCheck',
        'name' => 'MISSING_ITERABLE_VALUE_TYPE_TIP',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'See: https://phpstan.org/blog/solving-phpstan-no-value-type-specified-in-iterable-type\'',
          'attributes' => 
          array (
            'startLine' => 40,
            'endLine' => 40,
            'startTokenPos' => 202,
            'startFilePos' => 1079,
            'endTokenPos' => 202,
            'endFilePos' => 1166,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 40,
        'endLine' => 40,
        'startColumn' => 2,
        'endColumn' => 137,
      ),
      'ITERABLE_GENERIC_CLASS_NAMES' => 
      array (
        'declaringClassName' => 'PHPStan\\Rules\\MissingTypehintCheck',
        'implementingClassName' => 'PHPStan\\Rules\\MissingTypehintCheck',
        'name' => 'ITERABLE_GENERIC_CLASS_NAMES',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\\Traversable::class, \\Iterator::class, \\IteratorAggregate::class, \\Generator::class]',
          'attributes' => 
          array (
            'startLine' => 42,
            'endLine' => 47,
            'startTokenPos' => 213,
            'startFilePos' => 1216,
            'endTokenPos' => 235,
            'endFilePos' => 1308,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 42,
        'endLine' => 47,
        'startColumn' => 2,
        'endColumn' => 3,
      ),
    ),
    'immediateProperties' => 
    array (
      'checkMissingCallableSignature' => 
      array (
        'declaringClassName' => 'PHPStan\\Rules\\MissingTypehintCheck',
        'implementingClassName' => 'PHPStan\\Rules\\MissingTypehintCheck',
        'name' => 'checkMissingCallableSignature',
        'modifiers' => 4,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
          0 => 
          array (
            'name' => 'PHPStan\\DependencyInjection\\AutowiredParameter',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'startLine' => 53,
        'endLine' => 54,
        'startColumn' => 3,
        'endColumn' => 45,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'skipCheckGenericClasses' => 
      array (
        'declaringClassName' => 'PHPStan\\Rules\\MissingTypehintCheck',
        'implementingClassName' => 'PHPStan\\Rules\\MissingTypehintCheck',
        'name' => 'skipCheckGenericClasses',
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
          0 => 
          array (
            'name' => 'PHPStan\\DependencyInjection\\AutowiredParameter',
            'isRepeated' => false,
            'arguments' => 
            array (
              'ref' => 
              array (
                'code' => '\'%featureToggles.skipCheckGenericClasses%\'',
                'attributes' => 
                array (
                  'startLine' => 55,
                  'endLine' => 55,
                  'startTokenPos' => 264,
                  'startFilePos' => 1496,
                  'endTokenPos' => 264,
                  'endFilePos' => 1537,
                ),
              ),
            ),
          ),
        ),
        'startLine' => 55,
        'endLine' => 56,
        'startColumn' => 3,
        'endColumn' => 40,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'checkGenericIterableClasses' => 
      array (
        'declaringClassName' => 'PHPStan\\Rules\\MissingTypehintCheck',
        'implementingClassName' => 'PHPStan\\Rules\\MissingTypehintCheck',
        'name' => 'checkGenericIterableClasses',
        'modifiers' => 4,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
          0 => 
          array (
            'name' => 'PHPStan\\DependencyInjection\\AutowiredParameter',
            'isRepeated' => false,
            'arguments' => 
            array (
              'ref' => 
              array (
                'code' => '\'%featureToggles.checkGenericIterableClasses%\'',
                'attributes' => 
                array (
                  'startLine' => 57,
                  'endLine' => 57,
                  'startTokenPos' => 281,
                  'startFilePos' => 1611,
                  'endTokenPos' => 281,
                  'endFilePos' => 1656,
                ),
              ),
            ),
          ),
        ),
        'startLine' => 57,
        'endLine' => 58,
        'startColumn' => 3,
        'endColumn' => 43,
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
          'checkMissingCallableSignature' => 
          array (
            'name' => 'checkMissingCallableSignature',
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
            'isPromoted' => true,
            'attributes' => 
            array (
              0 => 
              array (
                'name' => 'PHPStan\\DependencyInjection\\AutowiredParameter',
                'isRepeated' => false,
                'arguments' => 
                array (
                ),
              ),
            ),
            'startLine' => 53,
            'endLine' => 54,
            'startColumn' => 3,
            'endColumn' => 45,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'skipCheckGenericClasses' => 
          array (
            'name' => 'skipCheckGenericClasses',
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
              0 => 
              array (
                'name' => 'PHPStan\\DependencyInjection\\AutowiredParameter',
                'isRepeated' => false,
                'arguments' => 
                array (
                  'ref' => 
                  array (
                    'code' => '\'%featureToggles.skipCheckGenericClasses%\'',
                    'attributes' => 
                    array (
                      'startLine' => 55,
                      'endLine' => 55,
                      'startTokenPos' => 264,
                      'startFilePos' => 1496,
                      'endTokenPos' => 264,
                      'endFilePos' => 1537,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 55,
            'endLine' => 56,
            'startColumn' => 3,
            'endColumn' => 40,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'checkGenericIterableClasses' => 
          array (
            'name' => 'checkGenericIterableClasses',
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
            'isPromoted' => true,
            'attributes' => 
            array (
              0 => 
              array (
                'name' => 'PHPStan\\DependencyInjection\\AutowiredParameter',
                'isRepeated' => false,
                'arguments' => 
                array (
                  'ref' => 
                  array (
                    'code' => '\'%featureToggles.checkGenericIterableClasses%\'',
                    'attributes' => 
                    array (
                      'startLine' => 57,
                      'endLine' => 57,
                      'startTokenPos' => 281,
                      'startFilePos' => 1611,
                      'endTokenPos' => 281,
                      'endFilePos' => 1656,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 57,
            'endLine' => 58,
            'startColumn' => 3,
            'endColumn' => 43,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @param string[] $skipCheckGenericClasses
 */',
        'startLine' => 52,
        'endLine' => 61,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Rules',
        'declaringClassName' => 'PHPStan\\Rules\\MissingTypehintCheck',
        'implementingClassName' => 'PHPStan\\Rules\\MissingTypehintCheck',
        'currentClassName' => 'PHPStan\\Rules\\MissingTypehintCheck',
        'aliasName' => NULL,
      ),
      'getIterableTypesWithMissingValueTypehint' => 
      array (
        'name' => 'getIterableTypesWithMissingValueTypehint',
        'parameters' => 
        array (
          'type' => 
          array (
            'name' => 'type',
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
            'startLine' => 66,
            'endLine' => 66,
            'startColumn' => 59,
            'endColumn' => 68,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @return Type[]
 */',
        'startLine' => 66,
        'endLine' => 119,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Rules',
        'declaringClassName' => 'PHPStan\\Rules\\MissingTypehintCheck',
        'implementingClassName' => 'PHPStan\\Rules\\MissingTypehintCheck',
        'currentClassName' => 'PHPStan\\Rules\\MissingTypehintCheck',
        'aliasName' => NULL,
      ),
      'getNonGenericObjectTypesWithGenericClass' => 
      array (
        'name' => 'getNonGenericObjectTypesWithGenericClass',
        'parameters' => 
        array (
          'type' => 
          array (
            'name' => 'type',
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
            'startLine' => 124,
            'endLine' => 124,
            'startColumn' => 59,
            'endColumn' => 68,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @return array<int, array{string, string}>
 */',
        'startLine' => 124,
        'endLine' => 188,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Rules',
        'declaringClassName' => 'PHPStan\\Rules\\MissingTypehintCheck',
        'implementingClassName' => 'PHPStan\\Rules\\MissingTypehintCheck',
        'currentClassName' => 'PHPStan\\Rules\\MissingTypehintCheck',
        'aliasName' => NULL,
      ),
      'getCallablesWithMissingSignature' => 
      array (
        'name' => 'getCallablesWithMissingSignature',
        'parameters' => 
        array (
          'type' => 
          array (
            'name' => 'type',
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
            'startLine' => 193,
            'endLine' => 193,
            'startColumn' => 51,
            'endColumn' => 60,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @return Type[]
 */',
        'startLine' => 193,
        'endLine' => 212,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Rules',
        'declaringClassName' => 'PHPStan\\Rules\\MissingTypehintCheck',
        'implementingClassName' => 'PHPStan\\Rules\\MissingTypehintCheck',
        'currentClassName' => 'PHPStan\\Rules\\MissingTypehintCheck',
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