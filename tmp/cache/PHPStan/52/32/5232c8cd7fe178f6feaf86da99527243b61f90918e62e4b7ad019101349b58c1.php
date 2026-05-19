<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/tests/PHPStan/Type/BenevolentUnionTypeTest.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\Type\BenevolentUnionTypeTest
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-31359a921578e3c75622a4bb5d5c00fa077cc73d44532fba6b964b9820259ae2',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\Type\\BenevolentUnionTypeTest',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/tests/PHPStan/Type/BenevolentUnionTypeTest.php',
      ),
    ),
    'namespace' => 'PHPStan\\Type',
    'name' => 'PHPStan\\Type\\BenevolentUnionTypeTest',
    'shortName' => 'BenevolentUnionTypeTest',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => NULL,
    'attributes' => 
    array (
    ),
    'startLine' => 20,
    'endLine' => 586,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'PHPStan\\Testing\\PHPStanTestCase',
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
      'dataCanAccessProperties' => 
      array (
        'name' => 'dataCanAccessProperties',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Iterator',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @return Iterator<int, array{BenevolentUnionType, TrinaryLogic}>
 */',
        'startLine' => 26,
        'endLine' => 42,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => true,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'PHPStan\\Type',
        'declaringClassName' => 'PHPStan\\Type\\BenevolentUnionTypeTest',
        'implementingClassName' => 'PHPStan\\Type\\BenevolentUnionTypeTest',
        'currentClassName' => 'PHPStan\\Type\\BenevolentUnionTypeTest',
        'aliasName' => NULL,
      ),
      'testCanAccessProperties' => 
      array (
        'name' => 'testCanAccessProperties',
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
                'name' => 'PHPStan\\Type\\BenevolentUnionType',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 45,
            'endLine' => 45,
            'startColumn' => 42,
            'endColumn' => 66,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'expectedResult' => 
          array (
            'name' => 'expectedResult',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\TrinaryLogic',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 45,
            'endLine' => 45,
            'startColumn' => 69,
            'endColumn' => 96,
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
                'code' => '\'dataCanAccessProperties\'',
                'attributes' => 
                array (
                  'startLine' => 44,
                  'endLine' => 44,
                  'startTokenPos' => 225,
                  'startFilePos' => 1214,
                  'endTokenPos' => 225,
                  'endFilePos' => 1238,
                ),
              ),
            ),
          ),
        ),
        'docComment' => NULL,
        'startLine' => 44,
        'endLine' => 53,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type',
        'declaringClassName' => 'PHPStan\\Type\\BenevolentUnionTypeTest',
        'implementingClassName' => 'PHPStan\\Type\\BenevolentUnionTypeTest',
        'currentClassName' => 'PHPStan\\Type\\BenevolentUnionTypeTest',
        'aliasName' => NULL,
      ),
      'dataHasInstanceProperty' => 
      array (
        'name' => 'dataHasInstanceProperty',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Iterator',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @return Iterator<int, array{BenevolentUnionType, string, TrinaryLogic}>
 */',
        'startLine' => 58,
        'endLine' => 83,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => true,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'PHPStan\\Type',
        'declaringClassName' => 'PHPStan\\Type\\BenevolentUnionTypeTest',
        'implementingClassName' => 'PHPStan\\Type\\BenevolentUnionTypeTest',
        'currentClassName' => 'PHPStan\\Type\\BenevolentUnionTypeTest',
        'aliasName' => NULL,
      ),
      'testHasInstanceProperty' => 
      array (
        'name' => 'testHasInstanceProperty',
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
                'name' => 'PHPStan\\Type\\BenevolentUnionType',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 86,
            'endLine' => 86,
            'startColumn' => 42,
            'endColumn' => 66,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'propertyName' => 
          array (
            'name' => 'propertyName',
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
            'startLine' => 86,
            'endLine' => 86,
            'startColumn' => 69,
            'endColumn' => 88,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'expectedResult' => 
          array (
            'name' => 'expectedResult',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\TrinaryLogic',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 86,
            'endLine' => 86,
            'startColumn' => 91,
            'endColumn' => 118,
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
                'code' => '\'dataHasInstanceProperty\'',
                'attributes' => 
                array (
                  'startLine' => 85,
                  'endLine' => 85,
                  'startTokenPos' => 490,
                  'startFilePos' => 2323,
                  'endTokenPos' => 490,
                  'endFilePos' => 2347,
                ),
              ),
            ),
          ),
        ),
        'docComment' => NULL,
        'startLine' => 85,
        'endLine' => 94,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type',
        'declaringClassName' => 'PHPStan\\Type\\BenevolentUnionTypeTest',
        'implementingClassName' => 'PHPStan\\Type\\BenevolentUnionTypeTest',
        'currentClassName' => 'PHPStan\\Type\\BenevolentUnionTypeTest',
        'aliasName' => NULL,
      ),
      'dataCanCallMethods' => 
      array (
        'name' => 'dataCanCallMethods',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Iterator',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @return Iterator<int, array{BenevolentUnionType, TrinaryLogic}>
 */',
        'startLine' => 99,
        'endLine' => 115,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => true,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'PHPStan\\Type',
        'declaringClassName' => 'PHPStan\\Type\\BenevolentUnionTypeTest',
        'implementingClassName' => 'PHPStan\\Type\\BenevolentUnionTypeTest',
        'currentClassName' => 'PHPStan\\Type\\BenevolentUnionTypeTest',
        'aliasName' => NULL,
      ),
      'testCanCanCallMethods' => 
      array (
        'name' => 'testCanCanCallMethods',
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
                'name' => 'PHPStan\\Type\\BenevolentUnionType',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 118,
            'endLine' => 118,
            'startColumn' => 40,
            'endColumn' => 64,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'expectedResult' => 
          array (
            'name' => 'expectedResult',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\TrinaryLogic',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 118,
            'endLine' => 118,
            'startColumn' => 67,
            'endColumn' => 94,
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
                'code' => '\'dataCanCallMethods\'',
                'attributes' => 
                array (
                  'startLine' => 117,
                  'endLine' => 117,
                  'startTokenPos' => 701,
                  'startFilePos' => 3232,
                  'endTokenPos' => 701,
                  'endFilePos' => 3251,
                ),
              ),
            ),
          ),
        ),
        'docComment' => NULL,
        'startLine' => 117,
        'endLine' => 126,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type',
        'declaringClassName' => 'PHPStan\\Type\\BenevolentUnionTypeTest',
        'implementingClassName' => 'PHPStan\\Type\\BenevolentUnionTypeTest',
        'currentClassName' => 'PHPStan\\Type\\BenevolentUnionTypeTest',
        'aliasName' => NULL,
      ),
      'dataHasMethod' => 
      array (
        'name' => 'dataHasMethod',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Iterator',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @return Iterator<int, array{BenevolentUnionType, string, TrinaryLogic}>
 */',
        'startLine' => 131,
        'endLine' => 153,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => true,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'PHPStan\\Type',
        'declaringClassName' => 'PHPStan\\Type\\BenevolentUnionTypeTest',
        'implementingClassName' => 'PHPStan\\Type\\BenevolentUnionTypeTest',
        'currentClassName' => 'PHPStan\\Type\\BenevolentUnionTypeTest',
        'aliasName' => NULL,
      ),
      'testHasMethod' => 
      array (
        'name' => 'testHasMethod',
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
                'name' => 'PHPStan\\Type\\BenevolentUnionType',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 156,
            'endLine' => 156,
            'startColumn' => 32,
            'endColumn' => 56,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'methodName' => 
          array (
            'name' => 'methodName',
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
            'startLine' => 156,
            'endLine' => 156,
            'startColumn' => 59,
            'endColumn' => 76,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'expectedResult' => 
          array (
            'name' => 'expectedResult',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\TrinaryLogic',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 156,
            'endLine' => 156,
            'startColumn' => 79,
            'endColumn' => 106,
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
                'code' => '\'dataHasMethod\'',
                'attributes' => 
                array (
                  'startLine' => 155,
                  'endLine' => 155,
                  'startTokenPos' => 927,
                  'startFilePos' => 4189,
                  'endTokenPos' => 927,
                  'endFilePos' => 4203,
                ),
              ),
            ),
          ),
        ),
        'docComment' => NULL,
        'startLine' => 155,
        'endLine' => 164,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type',
        'declaringClassName' => 'PHPStan\\Type\\BenevolentUnionTypeTest',
        'implementingClassName' => 'PHPStan\\Type\\BenevolentUnionTypeTest',
        'currentClassName' => 'PHPStan\\Type\\BenevolentUnionTypeTest',
        'aliasName' => NULL,
      ),
      'dataCanAccessConstants' => 
      array (
        'name' => 'dataCanAccessConstants',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Iterator',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @return Iterator<int, array{BenevolentUnionType, TrinaryLogic}>
 */',
        'startLine' => 169,
        'endLine' => 185,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => true,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'PHPStan\\Type',
        'declaringClassName' => 'PHPStan\\Type\\BenevolentUnionTypeTest',
        'implementingClassName' => 'PHPStan\\Type\\BenevolentUnionTypeTest',
        'currentClassName' => 'PHPStan\\Type\\BenevolentUnionTypeTest',
        'aliasName' => NULL,
      ),
      'testCanAccessConstants' => 
      array (
        'name' => 'testCanAccessConstants',
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
                'name' => 'PHPStan\\Type\\BenevolentUnionType',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 188,
            'endLine' => 188,
            'startColumn' => 41,
            'endColumn' => 65,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'expectedResult' => 
          array (
            'name' => 'expectedResult',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\TrinaryLogic',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 188,
            'endLine' => 188,
            'startColumn' => 68,
            'endColumn' => 95,
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
                'code' => '\'dataCanAccessConstants\'',
                'attributes' => 
                array (
                  'startLine' => 187,
                  'endLine' => 187,
                  'startTokenPos' => 1138,
                  'startFilePos' => 5066,
                  'endTokenPos' => 1138,
                  'endFilePos' => 5089,
                ),
              ),
            ),
          ),
        ),
        'docComment' => NULL,
        'startLine' => 187,
        'endLine' => 196,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type',
        'declaringClassName' => 'PHPStan\\Type\\BenevolentUnionTypeTest',
        'implementingClassName' => 'PHPStan\\Type\\BenevolentUnionTypeTest',
        'currentClassName' => 'PHPStan\\Type\\BenevolentUnionTypeTest',
        'aliasName' => NULL,
      ),
      'dataIsIterable' => 
      array (
        'name' => 'dataIsIterable',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Iterator',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @return Iterator<int, array{BenevolentUnionType, TrinaryLogic}>
 */',
        'startLine' => 201,
        'endLine' => 223,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => true,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'PHPStan\\Type',
        'declaringClassName' => 'PHPStan\\Type\\BenevolentUnionTypeTest',
        'implementingClassName' => 'PHPStan\\Type\\BenevolentUnionTypeTest',
        'currentClassName' => 'PHPStan\\Type\\BenevolentUnionTypeTest',
        'aliasName' => NULL,
      ),
      'testIsIterable' => 
      array (
        'name' => 'testIsIterable',
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
                'name' => 'PHPStan\\Type\\BenevolentUnionType',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 226,
            'endLine' => 226,
            'startColumn' => 33,
            'endColumn' => 57,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'expectedResult' => 
          array (
            'name' => 'expectedResult',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\TrinaryLogic',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 226,
            'endLine' => 226,
            'startColumn' => 60,
            'endColumn' => 87,
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
                'code' => '\'dataIsIterable\'',
                'attributes' => 
                array (
                  'startLine' => 225,
                  'endLine' => 225,
                  'startTokenPos' => 1385,
                  'startFilePos' => 6025,
                  'endTokenPos' => 1385,
                  'endFilePos' => 6040,
                ),
              ),
            ),
          ),
        ),
        'docComment' => NULL,
        'startLine' => 225,
        'endLine' => 234,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type',
        'declaringClassName' => 'PHPStan\\Type\\BenevolentUnionTypeTest',
        'implementingClassName' => 'PHPStan\\Type\\BenevolentUnionTypeTest',
        'currentClassName' => 'PHPStan\\Type\\BenevolentUnionTypeTest',
        'aliasName' => NULL,
      ),
      'dataIsIterableAtLeastOnce' => 
      array (
        'name' => 'dataIsIterableAtLeastOnce',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Iterator',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @return Iterator<int, array{BenevolentUnionType, TrinaryLogic}>
 */',
        'startLine' => 239,
        'endLine' => 261,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => true,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'PHPStan\\Type',
        'declaringClassName' => 'PHPStan\\Type\\BenevolentUnionTypeTest',
        'implementingClassName' => 'PHPStan\\Type\\BenevolentUnionTypeTest',
        'currentClassName' => 'PHPStan\\Type\\BenevolentUnionTypeTest',
        'aliasName' => NULL,
      ),
      'testIsIterableAtLeastOnce' => 
      array (
        'name' => 'testIsIterableAtLeastOnce',
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
                'name' => 'PHPStan\\Type\\BenevolentUnionType',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 264,
            'endLine' => 264,
            'startColumn' => 44,
            'endColumn' => 68,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'expectedResult' => 
          array (
            'name' => 'expectedResult',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\TrinaryLogic',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 264,
            'endLine' => 264,
            'startColumn' => 71,
            'endColumn' => 98,
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
                'code' => '\'dataIsIterableAtLeastOnce\'',
                'attributes' => 
                array (
                  'startLine' => 263,
                  'endLine' => 263,
                  'startTokenPos' => 1674,
                  'startFilePos' => 7110,
                  'endTokenPos' => 1674,
                  'endFilePos' => 7136,
                ),
              ),
            ),
          ),
        ),
        'docComment' => NULL,
        'startLine' => 263,
        'endLine' => 272,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type',
        'declaringClassName' => 'PHPStan\\Type\\BenevolentUnionTypeTest',
        'implementingClassName' => 'PHPStan\\Type\\BenevolentUnionTypeTest',
        'currentClassName' => 'PHPStan\\Type\\BenevolentUnionTypeTest',
        'aliasName' => NULL,
      ),
      'dataIsArray' => 
      array (
        'name' => 'dataIsArray',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Iterator',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @return Iterator<int, array{BenevolentUnionType, TrinaryLogic}>
 */',
        'startLine' => 277,
        'endLine' => 293,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => true,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'PHPStan\\Type',
        'declaringClassName' => 'PHPStan\\Type\\BenevolentUnionTypeTest',
        'implementingClassName' => 'PHPStan\\Type\\BenevolentUnionTypeTest',
        'currentClassName' => 'PHPStan\\Type\\BenevolentUnionTypeTest',
        'aliasName' => NULL,
      ),
      'testIsArray' => 
      array (
        'name' => 'testIsArray',
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
                'name' => 'PHPStan\\Type\\BenevolentUnionType',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 296,
            'endLine' => 296,
            'startColumn' => 30,
            'endColumn' => 54,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'expectedResult' => 
          array (
            'name' => 'expectedResult',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\TrinaryLogic',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 296,
            'endLine' => 296,
            'startColumn' => 57,
            'endColumn' => 84,
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
                'code' => '\'dataIsArray\'',
                'attributes' => 
                array (
                  'startLine' => 295,
                  'endLine' => 295,
                  'startTokenPos' => 1909,
                  'startFilePos' => 8034,
                  'endTokenPos' => 1909,
                  'endFilePos' => 8046,
                ),
              ),
            ),
          ),
        ),
        'docComment' => NULL,
        'startLine' => 295,
        'endLine' => 304,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type',
        'declaringClassName' => 'PHPStan\\Type\\BenevolentUnionTypeTest',
        'implementingClassName' => 'PHPStan\\Type\\BenevolentUnionTypeTest',
        'currentClassName' => 'PHPStan\\Type\\BenevolentUnionTypeTest',
        'aliasName' => NULL,
      ),
      'dataIsString' => 
      array (
        'name' => 'dataIsString',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Iterator',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @return Iterator<int, array{BenevolentUnionType, TrinaryLogic}>
 */',
        'startLine' => 309,
        'endLine' => 328,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => true,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'PHPStan\\Type',
        'declaringClassName' => 'PHPStan\\Type\\BenevolentUnionTypeTest',
        'implementingClassName' => 'PHPStan\\Type\\BenevolentUnionTypeTest',
        'currentClassName' => 'PHPStan\\Type\\BenevolentUnionTypeTest',
        'aliasName' => NULL,
      ),
      'testIsString' => 
      array (
        'name' => 'testIsString',
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
                'name' => 'PHPStan\\Type\\BenevolentUnionType',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 331,
            'endLine' => 331,
            'startColumn' => 31,
            'endColumn' => 55,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'expectedResult' => 
          array (
            'name' => 'expectedResult',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\TrinaryLogic',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 331,
            'endLine' => 331,
            'startColumn' => 58,
            'endColumn' => 85,
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
                'code' => '\'dataIsString\'',
                'attributes' => 
                array (
                  'startLine' => 330,
                  'endLine' => 330,
                  'startTokenPos' => 2131,
                  'startFilePos' => 8910,
                  'endTokenPos' => 2131,
                  'endFilePos' => 8923,
                ),
              ),
            ),
          ),
        ),
        'docComment' => NULL,
        'startLine' => 330,
        'endLine' => 339,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type',
        'declaringClassName' => 'PHPStan\\Type\\BenevolentUnionTypeTest',
        'implementingClassName' => 'PHPStan\\Type\\BenevolentUnionTypeTest',
        'currentClassName' => 'PHPStan\\Type\\BenevolentUnionTypeTest',
        'aliasName' => NULL,
      ),
      'dataIsNumericString' => 
      array (
        'name' => 'dataIsNumericString',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Iterator',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @return Iterator<int, array{BenevolentUnionType, TrinaryLogic}>
 */',
        'startLine' => 344,
        'endLine' => 362,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => true,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'PHPStan\\Type',
        'declaringClassName' => 'PHPStan\\Type\\BenevolentUnionTypeTest',
        'implementingClassName' => 'PHPStan\\Type\\BenevolentUnionTypeTest',
        'currentClassName' => 'PHPStan\\Type\\BenevolentUnionTypeTest',
        'aliasName' => NULL,
      ),
      'testIsNumericString' => 
      array (
        'name' => 'testIsNumericString',
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
                'name' => 'PHPStan\\Type\\BenevolentUnionType',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 365,
            'endLine' => 365,
            'startColumn' => 38,
            'endColumn' => 62,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'expectedResult' => 
          array (
            'name' => 'expectedResult',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\TrinaryLogic',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 365,
            'endLine' => 365,
            'startColumn' => 65,
            'endColumn' => 92,
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
                'code' => '\'dataIsNumericString\'',
                'attributes' => 
                array (
                  'startLine' => 364,
                  'endLine' => 364,
                  'startTokenPos' => 2365,
                  'startFilePos' => 9849,
                  'endTokenPos' => 2365,
                  'endFilePos' => 9869,
                ),
              ),
            ),
          ),
        ),
        'docComment' => NULL,
        'startLine' => 364,
        'endLine' => 373,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type',
        'declaringClassName' => 'PHPStan\\Type\\BenevolentUnionTypeTest',
        'implementingClassName' => 'PHPStan\\Type\\BenevolentUnionTypeTest',
        'currentClassName' => 'PHPStan\\Type\\BenevolentUnionTypeTest',
        'aliasName' => NULL,
      ),
      'dataIsNonFalsyString' => 
      array (
        'name' => 'dataIsNonFalsyString',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Iterator',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @return Iterator<int, array{BenevolentUnionType, TrinaryLogic}>
 */',
        'startLine' => 378,
        'endLine' => 396,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => true,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'PHPStan\\Type',
        'declaringClassName' => 'PHPStan\\Type\\BenevolentUnionTypeTest',
        'implementingClassName' => 'PHPStan\\Type\\BenevolentUnionTypeTest',
        'currentClassName' => 'PHPStan\\Type\\BenevolentUnionTypeTest',
        'aliasName' => NULL,
      ),
      'testIsNonFalsyString' => 
      array (
        'name' => 'testIsNonFalsyString',
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
                'name' => 'PHPStan\\Type\\BenevolentUnionType',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 399,
            'endLine' => 399,
            'startColumn' => 39,
            'endColumn' => 63,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'expectedResult' => 
          array (
            'name' => 'expectedResult',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\TrinaryLogic',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 399,
            'endLine' => 399,
            'startColumn' => 66,
            'endColumn' => 93,
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
                'code' => '\'dataIsNonFalsyString\'',
                'attributes' => 
                array (
                  'startLine' => 398,
                  'endLine' => 398,
                  'startTokenPos' => 2599,
                  'startFilePos' => 10819,
                  'endTokenPos' => 2599,
                  'endFilePos' => 10840,
                ),
              ),
            ),
          ),
        ),
        'docComment' => NULL,
        'startLine' => 398,
        'endLine' => 407,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type',
        'declaringClassName' => 'PHPStan\\Type\\BenevolentUnionTypeTest',
        'implementingClassName' => 'PHPStan\\Type\\BenevolentUnionTypeTest',
        'currentClassName' => 'PHPStan\\Type\\BenevolentUnionTypeTest',
        'aliasName' => NULL,
      ),
      'dataIsLiteralString' => 
      array (
        'name' => 'dataIsLiteralString',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Iterator',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @return Iterator<int, array{BenevolentUnionType, TrinaryLogic}>
 */',
        'startLine' => 412,
        'endLine' => 430,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => true,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'PHPStan\\Type',
        'declaringClassName' => 'PHPStan\\Type\\BenevolentUnionTypeTest',
        'implementingClassName' => 'PHPStan\\Type\\BenevolentUnionTypeTest',
        'currentClassName' => 'PHPStan\\Type\\BenevolentUnionTypeTest',
        'aliasName' => NULL,
      ),
      'testIsLiteralString' => 
      array (
        'name' => 'testIsLiteralString',
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
                'name' => 'PHPStan\\Type\\BenevolentUnionType',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 433,
            'endLine' => 433,
            'startColumn' => 38,
            'endColumn' => 62,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'expectedResult' => 
          array (
            'name' => 'expectedResult',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\TrinaryLogic',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 433,
            'endLine' => 433,
            'startColumn' => 65,
            'endColumn' => 92,
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
                'code' => '\'dataIsLiteralString\'',
                'attributes' => 
                array (
                  'startLine' => 432,
                  'endLine' => 432,
                  'startTokenPos' => 2833,
                  'startFilePos' => 11790,
                  'endTokenPos' => 2833,
                  'endFilePos' => 11810,
                ),
              ),
            ),
          ),
        ),
        'docComment' => NULL,
        'startLine' => 432,
        'endLine' => 441,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type',
        'declaringClassName' => 'PHPStan\\Type\\BenevolentUnionTypeTest',
        'implementingClassName' => 'PHPStan\\Type\\BenevolentUnionTypeTest',
        'currentClassName' => 'PHPStan\\Type\\BenevolentUnionTypeTest',
        'aliasName' => NULL,
      ),
      'dataIsOffsetAccesible' => 
      array (
        'name' => 'dataIsOffsetAccesible',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Iterator',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @return Iterator<int, array{BenevolentUnionType, TrinaryLogic}>
 */',
        'startLine' => 446,
        'endLine' => 468,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => true,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'PHPStan\\Type',
        'declaringClassName' => 'PHPStan\\Type\\BenevolentUnionTypeTest',
        'implementingClassName' => 'PHPStan\\Type\\BenevolentUnionTypeTest',
        'currentClassName' => 'PHPStan\\Type\\BenevolentUnionTypeTest',
        'aliasName' => NULL,
      ),
      'testIsOffsetAccessible' => 
      array (
        'name' => 'testIsOffsetAccessible',
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
                'name' => 'PHPStan\\Type\\BenevolentUnionType',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 471,
            'endLine' => 471,
            'startColumn' => 41,
            'endColumn' => 65,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'expectedResult' => 
          array (
            'name' => 'expectedResult',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\TrinaryLogic',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 471,
            'endLine' => 471,
            'startColumn' => 68,
            'endColumn' => 95,
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
                'code' => '\'dataIsOffsetAccesible\'',
                'attributes' => 
                array (
                  'startLine' => 470,
                  'endLine' => 470,
                  'startTokenPos' => 3080,
                  'startFilePos' => 12765,
                  'endTokenPos' => 3080,
                  'endFilePos' => 12787,
                ),
              ),
            ),
          ),
        ),
        'docComment' => NULL,
        'startLine' => 470,
        'endLine' => 479,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type',
        'declaringClassName' => 'PHPStan\\Type\\BenevolentUnionTypeTest',
        'implementingClassName' => 'PHPStan\\Type\\BenevolentUnionTypeTest',
        'currentClassName' => 'PHPStan\\Type\\BenevolentUnionTypeTest',
        'aliasName' => NULL,
      ),
      'dataHasOffsetValueType' => 
      array (
        'name' => 'dataHasOffsetValueType',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Iterator',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @return Iterator<int, array{BenevolentUnionType, ConstantStringType, TrinaryLogic}>
 */',
        'startLine' => 484,
        'endLine' => 509,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => true,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'PHPStan\\Type',
        'declaringClassName' => 'PHPStan\\Type\\BenevolentUnionTypeTest',
        'implementingClassName' => 'PHPStan\\Type\\BenevolentUnionTypeTest',
        'currentClassName' => 'PHPStan\\Type\\BenevolentUnionTypeTest',
        'aliasName' => NULL,
      ),
      'testHasOffsetValue' => 
      array (
        'name' => 'testHasOffsetValue',
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
                'name' => 'PHPStan\\Type\\BenevolentUnionType',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 512,
            'endLine' => 512,
            'startColumn' => 37,
            'endColumn' => 61,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'offsetType' => 
          array (
            'name' => 'offsetType',
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
            'startLine' => 512,
            'endLine' => 512,
            'startColumn' => 64,
            'endColumn' => 79,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'expectedResult' => 
          array (
            'name' => 'expectedResult',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\TrinaryLogic',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 512,
            'endLine' => 512,
            'startColumn' => 82,
            'endColumn' => 109,
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
                'code' => '\'dataHasOffsetValueType\'',
                'attributes' => 
                array (
                  'startLine' => 511,
                  'endLine' => 511,
                  'startTokenPos' => 3366,
                  'startFilePos' => 13931,
                  'endTokenPos' => 3366,
                  'endFilePos' => 13954,
                ),
              ),
            ),
          ),
        ),
        'docComment' => NULL,
        'startLine' => 511,
        'endLine' => 520,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type',
        'declaringClassName' => 'PHPStan\\Type\\BenevolentUnionTypeTest',
        'implementingClassName' => 'PHPStan\\Type\\BenevolentUnionTypeTest',
        'currentClassName' => 'PHPStan\\Type\\BenevolentUnionTypeTest',
        'aliasName' => NULL,
      ),
      'dataIsCallable' => 
      array (
        'name' => 'dataIsCallable',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Iterator',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @return Iterator<int, array{BenevolentUnionType, TrinaryLogic}>
 */',
        'startLine' => 525,
        'endLine' => 541,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => true,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'PHPStan\\Type',
        'declaringClassName' => 'PHPStan\\Type\\BenevolentUnionTypeTest',
        'implementingClassName' => 'PHPStan\\Type\\BenevolentUnionTypeTest',
        'currentClassName' => 'PHPStan\\Type\\BenevolentUnionTypeTest',
        'aliasName' => NULL,
      ),
      'testIsCallable' => 
      array (
        'name' => 'testIsCallable',
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
                'name' => 'PHPStan\\Type\\BenevolentUnionType',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 544,
            'endLine' => 544,
            'startColumn' => 33,
            'endColumn' => 57,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'expectedResult' => 
          array (
            'name' => 'expectedResult',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\TrinaryLogic',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 544,
            'endLine' => 544,
            'startColumn' => 60,
            'endColumn' => 87,
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
                'code' => '\'dataIsCallable\'',
                'attributes' => 
                array (
                  'startLine' => 543,
                  'endLine' => 543,
                  'startTokenPos' => 3577,
                  'startFilePos' => 14800,
                  'endTokenPos' => 3577,
                  'endFilePos' => 14815,
                ),
              ),
            ),
          ),
        ),
        'docComment' => NULL,
        'startLine' => 543,
        'endLine' => 552,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type',
        'declaringClassName' => 'PHPStan\\Type\\BenevolentUnionTypeTest',
        'implementingClassName' => 'PHPStan\\Type\\BenevolentUnionTypeTest',
        'currentClassName' => 'PHPStan\\Type\\BenevolentUnionTypeTest',
        'aliasName' => NULL,
      ),
      'dataIsCloneable' => 
      array (
        'name' => 'dataIsCloneable',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Iterator',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @return Iterator<int, array{BenevolentUnionType, TrinaryLogic}>
 */',
        'startLine' => 557,
        'endLine' => 573,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => true,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'PHPStan\\Type',
        'declaringClassName' => 'PHPStan\\Type\\BenevolentUnionTypeTest',
        'implementingClassName' => 'PHPStan\\Type\\BenevolentUnionTypeTest',
        'currentClassName' => 'PHPStan\\Type\\BenevolentUnionTypeTest',
        'aliasName' => NULL,
      ),
      'testIsCloneable' => 
      array (
        'name' => 'testIsCloneable',
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
                'name' => 'PHPStan\\Type\\BenevolentUnionType',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 576,
            'endLine' => 576,
            'startColumn' => 34,
            'endColumn' => 58,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'expectedResult' => 
          array (
            'name' => 'expectedResult',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\TrinaryLogic',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 576,
            'endLine' => 576,
            'startColumn' => 61,
            'endColumn' => 88,
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
                'code' => '\'dataIsCloneable\'',
                'attributes' => 
                array (
                  'startLine' => 575,
                  'endLine' => 575,
                  'startTokenPos' => 3782,
                  'startFilePos' => 15643,
                  'endTokenPos' => 3782,
                  'endFilePos' => 15659,
                ),
              ),
            ),
          ),
        ),
        'docComment' => NULL,
        'startLine' => 575,
        'endLine' => 584,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type',
        'declaringClassName' => 'PHPStan\\Type\\BenevolentUnionTypeTest',
        'implementingClassName' => 'PHPStan\\Type\\BenevolentUnionTypeTest',
        'currentClassName' => 'PHPStan\\Type\\BenevolentUnionTypeTest',
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