<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/src/Command/Bisect/BinarySearch.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\Command\Bisect\BinarySearch
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-3b9b6b4b976c4f2a06c42c039dd0afd2218d841e2f7ddf66e23358e9ad3abf00',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\Command\\Bisect\\BinarySearch',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/src/Command/Bisect/BinarySearch.php',
      ),
    ),
    'namespace' => 'PHPStan\\Command\\Bisect',
    'name' => 'PHPStan\\Command\\Bisect\\BinarySearch',
    'shortName' => 'BinarySearch',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => NULL,
    'attributes' => 
    array (
    ),
    'startLine' => 11,
    'endLine' => 36,
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
    ),
    'immediateMethods' => 
    array (
      'getStep' => 
      array (
        'name' => 'getStep',
        'parameters' => 
        array (
          'items' => 
          array (
            'name' => 'items',
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
            'startLine' => 19,
            'endLine' => 19,
            'startColumn' => 33,
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
            'name' => 'PHPStan\\Command\\Bisect\\BinarySearchStep',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @template T
 * @param list<T> $items Items ordered from oldest to newest (at least 2)
 * @return BinarySearchStep<T>
 */',
        'startLine' => 19,
        'endLine' => 34,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'PHPStan\\Command\\Bisect',
        'declaringClassName' => 'PHPStan\\Command\\Bisect\\BinarySearch',
        'implementingClassName' => 'PHPStan\\Command\\Bisect\\BinarySearch',
        'currentClassName' => 'PHPStan\\Command\\Bisect\\BinarySearch',
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