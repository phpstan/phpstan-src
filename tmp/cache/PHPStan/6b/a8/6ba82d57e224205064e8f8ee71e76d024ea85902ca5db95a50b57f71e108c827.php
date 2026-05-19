<?php declare(strict_types = 1);

// osfsl-/home/runner/work/phpstan-src/phpstan-src/vendor/composer/../ondrejmirtes/better-reflection/src/Reflection/ReflectionUnionType.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\BetterReflection\Reflection\ReflectionUnionType
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-1610a63b320d8d5d97b6a695300d58b6f6330711bdc947aabc4e2760eabaa513-8.4.21-6.70.0.1',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionUnionType',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/vendor/composer/../ondrejmirtes/better-reflection/src/Reflection/ReflectionUnionType.php',
      ),
    ),
    'namespace' => 'PHPStan\\BetterReflection\\Reflection',
    'name' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionUnionType',
    'shortName' => 'ReflectionUnionType',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/** @psalm-immutable */',
    'attributes' => 
    array (
    ),
    'startLine' => 20,
    'endLine' => 119,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionType',
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
      'types' => 
      array (
        'declaringClassName' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionUnionType',
        'implementingClassName' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionUnionType',
        'name' => 'types',
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
        'docComment' => '/** @var non-empty-list<ReflectionNamedType|ReflectionIntersectionType> */',
        'attributes' => 
        array (
        ),
        'startLine' => 23,
        'endLine' => 23,
        'startColumn' => 5,
        'endColumn' => 25,
        'isPromoted' => false,
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
          'reflector' => 
          array (
            'name' => 'reflector',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\BetterReflection\\Reflector\\Reflector',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 28,
            'endLine' => 28,
            'startColumn' => 9,
            'endColumn' => 28,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'owner' => 
          array (
            'name' => 'owner',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 29,
            'endLine' => 29,
            'startColumn' => 9,
            'endColumn' => 14,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'type' => 
          array (
            'name' => 'type',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PhpParser\\Node\\UnionType',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 30,
            'endLine' => 30,
            'startColumn' => 9,
            'endColumn' => 23,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/** @internal
 * @param \\PHPStan\\BetterReflection\\Reflection\\ReflectionParameter|\\PHPStan\\BetterReflection\\Reflection\\ReflectionMethod|\\PHPStan\\BetterReflection\\Reflection\\ReflectionFunction|\\PHPStan\\BetterReflection\\Reflection\\ReflectionEnum|\\PHPStan\\BetterReflection\\Reflection\\ReflectionProperty|\\PHPStan\\BetterReflection\\Reflection\\ReflectionClassConstant $owner */',
        'startLine' => 27,
        'endLine' => 41,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\BetterReflection\\Reflection',
        'declaringClassName' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionUnionType',
        'implementingClassName' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionUnionType',
        'currentClassName' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionUnionType',
        'aliasName' => NULL,
      ),
      'exportToCache' => 
      array (
        'name' => 'exportToCache',
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
 * @return array<string, mixed>
 */',
        'startLine' => 46,
        'endLine' => 57,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\BetterReflection\\Reflection',
        'declaringClassName' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionUnionType',
        'implementingClassName' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionUnionType',
        'currentClassName' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionUnionType',
        'aliasName' => NULL,
      ),
      'importFromCache' => 
      array (
        'name' => 'importFromCache',
        'parameters' => 
        array (
          'reflector' => 
          array (
            'name' => 'reflector',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\BetterReflection\\Reflector\\Reflector',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 63,
            'endLine' => 63,
            'startColumn' => 44,
            'endColumn' => 63,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'data' => 
          array (
            'name' => 'data',
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
            'startLine' => 63,
            'endLine' => 63,
            'startColumn' => 66,
            'endColumn' => 76,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'owner' => 
          array (
            'name' => 'owner',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 63,
            'endLine' => 63,
            'startColumn' => 79,
            'endColumn' => 84,
            'parameterIndex' => 2,
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
 * @param array<string, mixed> $data
 * @param ReflectionParameter|ReflectionMethod|ReflectionFunction|ReflectionEnum|ReflectionProperty|ReflectionClassConstant $owner
 */',
        'startLine' => 63,
        'endLine' => 77,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'PHPStan\\BetterReflection\\Reflection',
        'declaringClassName' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionUnionType',
        'implementingClassName' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionUnionType',
        'currentClassName' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionUnionType',
        'aliasName' => NULL,
      ),
      'withOwner' => 
      array (
        'name' => 'withOwner',
        'parameters' => 
        array (
          'owner' => 
          array (
            'name' => 'owner',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 82,
            'endLine' => 82,
            'startColumn' => 31,
            'endColumn' => 36,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/** @internal
 * @param \\PHPStan\\BetterReflection\\Reflection\\ReflectionParameter|\\PHPStan\\BetterReflection\\Reflection\\ReflectionMethod|\\PHPStan\\BetterReflection\\Reflection\\ReflectionFunction|\\PHPStan\\BetterReflection\\Reflection\\ReflectionEnum|\\PHPStan\\BetterReflection\\Reflection\\ReflectionProperty|\\PHPStan\\BetterReflection\\Reflection\\ReflectionClassConstant $owner
 * @return static */',
        'startLine' => 82,
        'endLine' => 89,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\BetterReflection\\Reflection',
        'declaringClassName' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionUnionType',
        'implementingClassName' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionUnionType',
        'currentClassName' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionUnionType',
        'aliasName' => NULL,
      ),
      'getTypes' => 
      array (
        'name' => 'getTypes',
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
        'docComment' => '/** @return non-empty-list<ReflectionNamedType|ReflectionIntersectionType> */',
        'startLine' => 92,
        'endLine' => 95,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\BetterReflection\\Reflection',
        'declaringClassName' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionUnionType',
        'implementingClassName' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionUnionType',
        'currentClassName' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionUnionType',
        'aliasName' => NULL,
      ),
      'allowsNull' => 
      array (
        'name' => 'allowsNull',
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
        'startLine' => 97,
        'endLine' => 106,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\BetterReflection\\Reflection',
        'declaringClassName' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionUnionType',
        'implementingClassName' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionUnionType',
        'currentClassName' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionUnionType',
        'aliasName' => NULL,
      ),
      '__toString' => 
      array (
        'name' => '__toString',
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
        'docComment' => '/** @return non-empty-string */',
        'startLine' => 109,
        'endLine' => 118,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\BetterReflection\\Reflection',
        'declaringClassName' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionUnionType',
        'implementingClassName' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionUnionType',
        'currentClassName' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionUnionType',
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