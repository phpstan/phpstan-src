<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/tests/PHPStan/Command/Bisect/BinarySearchTest.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\Command\Bisect\BinarySearchTest
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-a683986f1fc334f1ddca6bacca9f64aec70bce38025ba65d40375953b55a14c4',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\Command\\Bisect\\BinarySearchTest',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/tests/PHPStan/Command/Bisect/BinarySearchTest.php',
      ),
    ),
    'namespace' => 'PHPStan\\Command\\Bisect',
    'name' => 'PHPStan\\Command\\Bisect\\BinarySearchTest',
    'shortName' => 'BinarySearchTest',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => NULL,
    'attributes' => 
    array (
    ),
    'startLine' => 14,
    'endLine' => 150,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'PHPUnit\\Framework\\TestCase',
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
      'testGetStep' => 
      array (
        'name' => 'testGetStep',
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
            'startLine' => 24,
            'endLine' => 24,
            'startColumn' => 3,
            'endColumn' => 14,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'expectedItem' => 
          array (
            'name' => 'expectedItem',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'string',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 25,
            'endLine' => 25,
            'startColumn' => 3,
            'endColumn' => 22,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'expectedIfGood' => 
          array (
            'name' => 'expectedIfGood',
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
            'startLine' => 26,
            'endLine' => 26,
            'startColumn' => 3,
            'endColumn' => 23,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'expectedIfBad' => 
          array (
            'name' => 'expectedIfBad',
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
            'startLine' => 27,
            'endLine' => 27,
            'startColumn' => 3,
            'endColumn' => 22,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
          'expectedStepsRemaining' => 
          array (
            'name' => 'expectedStepsRemaining',
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
            'startLine' => 28,
            'endLine' => 28,
            'startColumn' => 3,
            'endColumn' => 29,
            'parameterIndex' => 4,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
          0 => 
          array (
            'name' => 'PHPUnit\\Framework\\Attributes\\DataProvider',
            'isRepeated' => false,
            'arguments' => 
            array (
              0 => 
              array (
                'code' => '\'dataGetStep\'',
                'attributes' => 
                array (
                  'startLine' => 22,
                  'endLine' => 22,
                  'startTokenPos' => 81,
                  'startFilePos' => 471,
                  'endTokenPos' => 81,
                  'endFilePos' => 483,
                ),
              ),
            ),
          ),
        ),
        'docComment' => '/**
 * @param list<string> $items
 * @param list<string> $expectedIfGood
 * @param list<string> $expectedIfBad
 */',
        'startLine' => 22,
        'endLine' => 36,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Command\\Bisect',
        'declaringClassName' => 'PHPStan\\Command\\Bisect\\BinarySearchTest',
        'implementingClassName' => 'PHPStan\\Command\\Bisect\\BinarySearchTest',
        'currentClassName' => 'PHPStan\\Command\\Bisect\\BinarySearchTest',
        'aliasName' => NULL,
      ),
      'dataGetStep' => 
      array (
        'name' => 'dataGetStep',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'iterable',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 38,
        'endLine' => 87,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => true,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'PHPStan\\Command\\Bisect',
        'declaringClassName' => 'PHPStan\\Command\\Bisect\\BinarySearchTest',
        'implementingClassName' => 'PHPStan\\Command\\Bisect\\BinarySearchTest',
        'currentClassName' => 'PHPStan\\Command\\Bisect\\BinarySearchTest',
        'aliasName' => NULL,
      ),
      'testGetStepWithTooFewItems' => 
      array (
        'name' => 'testGetStepWithTooFewItems',
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
            'startLine' => 93,
            'endLine' => 93,
            'startColumn' => 45,
            'endColumn' => 56,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
          0 => 
          array (
            'name' => 'PHPUnit\\Framework\\Attributes\\DataProvider',
            'isRepeated' => false,
            'arguments' => 
            array (
              0 => 
              array (
                'code' => '\'dataTooFewItems\'',
                'attributes' => 
                array (
                  'startLine' => 92,
                  'endLine' => 92,
                  'startTokenPos' => 560,
                  'startFilePos' => 1770,
                  'endTokenPos' => 560,
                  'endFilePos' => 1786,
                ),
              ),
            ),
          ),
        ),
        'docComment' => '/**
 * @param list<string> $items
 */',
        'startLine' => 92,
        'endLine' => 97,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Command\\Bisect',
        'declaringClassName' => 'PHPStan\\Command\\Bisect\\BinarySearchTest',
        'implementingClassName' => 'PHPStan\\Command\\Bisect\\BinarySearchTest',
        'currentClassName' => 'PHPStan\\Command\\Bisect\\BinarySearchTest',
        'aliasName' => NULL,
      ),
      'dataTooFewItems' => 
      array (
        'name' => 'dataTooFewItems',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'iterable',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 99,
        'endLine' => 103,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => true,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'PHPStan\\Command\\Bisect',
        'declaringClassName' => 'PHPStan\\Command\\Bisect\\BinarySearchTest',
        'implementingClassName' => 'PHPStan\\Command\\Bisect\\BinarySearchTest',
        'currentClassName' => 'PHPStan\\Command\\Bisect\\BinarySearchTest',
        'aliasName' => NULL,
      ),
      'testFullBisect' => 
      array (
        'name' => 'testFullBisect',
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
            'startLine' => 109,
            'endLine' => 109,
            'startColumn' => 33,
            'endColumn' => 44,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'firstBadItem' => 
          array (
            'name' => 'firstBadItem',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'string',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 109,
            'endLine' => 109,
            'startColumn' => 47,
            'endColumn' => 66,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
          0 => 
          array (
            'name' => 'PHPUnit\\Framework\\Attributes\\DataProvider',
            'isRepeated' => false,
            'arguments' => 
            array (
              0 => 
              array (
                'code' => '\'dataFullBisect\'',
                'attributes' => 
                array (
                  'startLine' => 108,
                  'endLine' => 108,
                  'startTokenPos' => 647,
                  'startFilePos' => 2128,
                  'endTokenPos' => 647,
                  'endFilePos' => 2143,
                ),
              ),
            ),
          ),
        ),
        'docComment' => '/**
 * @param list<string> $items
 */',
        'startLine' => 108,
        'endLine' => 131,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Command\\Bisect',
        'declaringClassName' => 'PHPStan\\Command\\Bisect\\BinarySearchTest',
        'implementingClassName' => 'PHPStan\\Command\\Bisect\\BinarySearchTest',
        'currentClassName' => 'PHPStan\\Command\\Bisect\\BinarySearchTest',
        'aliasName' => NULL,
      ),
      'dataFullBisect' => 
      array (
        'name' => 'dataFullBisect',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'iterable',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 133,
        'endLine' => 148,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => true,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'PHPStan\\Command\\Bisect',
        'declaringClassName' => 'PHPStan\\Command\\Bisect\\BinarySearchTest',
        'implementingClassName' => 'PHPStan\\Command\\Bisect\\BinarySearchTest',
        'currentClassName' => 'PHPStan\\Command\\Bisect\\BinarySearchTest',
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