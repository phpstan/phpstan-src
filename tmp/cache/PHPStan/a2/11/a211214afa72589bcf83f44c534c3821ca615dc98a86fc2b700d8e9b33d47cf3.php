<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/tests/PHPStan/Type/UnionTypeTest.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\Type\UnionTypeTest
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-c7251ab5572d54b42529d7a6845af378c8e7974a529b0c4f79e8fd8bb719ef3d',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\Type\\UnionTypeTest',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/tests/PHPStan/Type/UnionTypeTest.php',
      ),
    ),
    'namespace' => 'PHPStan\\Type',
    'name' => 'PHPStan\\Type\\UnionTypeTest',
    'shortName' => 'UnionTypeTest',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => NULL,
    'attributes' => 
    array (
    ),
    'startLine' => 39,
    'endLine' => 1665,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 42,
        'endLine' => 77,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'PHPStan\\Type',
        'declaringClassName' => 'PHPStan\\Type\\UnionTypeTest',
        'implementingClassName' => 'PHPStan\\Type\\UnionTypeTest',
        'currentClassName' => 'PHPStan\\Type\\UnionTypeTest',
        'aliasName' => NULL,
      ),
      'testIsCallable' => 
      array (
        'name' => 'testIsCallable',
        'parameters' => 
        array (
          'unionType' => 
          array (
            'name' => 'unionType',
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
            'startLine' => 80,
            'endLine' => 80,
            'startColumn' => 33,
            'endColumn' => 47,
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
            'startLine' => 80,
            'endLine' => 80,
            'startColumn' => 50,
            'endColumn' => 77,
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
                  'startLine' => 79,
                  'endLine' => 79,
                  'startTokenPos' => 433,
                  'startFilePos' => 2142,
                  'endTokenPos' => 433,
                  'endFilePos' => 2157,
                ),
              ),
            ),
          ),
        ),
        'docComment' => NULL,
        'startLine' => 79,
        'endLine' => 90,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type',
        'declaringClassName' => 'PHPStan\\Type\\UnionTypeTest',
        'implementingClassName' => 'PHPStan\\Type\\UnionTypeTest',
        'currentClassName' => 'PHPStan\\Type\\UnionTypeTest',
        'aliasName' => NULL,
      ),
      'dataSelfCompare' => 
      array (
        'name' => 'dataSelfCompare',
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
 * @return Iterator<int, array{Type}>
 */',
        'startLine' => 95,
        'endLine' => 152,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => true,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'PHPStan\\Type',
        'declaringClassName' => 'PHPStan\\Type\\UnionTypeTest',
        'implementingClassName' => 'PHPStan\\Type\\UnionTypeTest',
        'currentClassName' => 'PHPStan\\Type\\UnionTypeTest',
        'aliasName' => NULL,
      ),
      'testSelfCompare' => 
      array (
        'name' => 'testSelfCompare',
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
            'startLine' => 155,
            'endLine' => 155,
            'startColumn' => 34,
            'endColumn' => 43,
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
                'code' => '\'dataSelfCompare\'',
                'attributes' => 
                array (
                  'startLine' => 154,
                  'endLine' => 154,
                  'startTokenPos' => 1334,
                  'startFilePos' => 5481,
                  'endTokenPos' => 1334,
                  'endFilePos' => 5497,
                ),
              ),
            ),
          ),
        ),
        'docComment' => NULL,
        'startLine' => 154,
        'endLine' => 172,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type',
        'declaringClassName' => 'PHPStan\\Type\\UnionTypeTest',
        'implementingClassName' => 'PHPStan\\Type\\UnionTypeTest',
        'currentClassName' => 'PHPStan\\Type\\UnionTypeTest',
        'aliasName' => NULL,
      ),
      'dataIsSuperTypeOf' => 
      array (
        'name' => 'dataIsSuperTypeOf',
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
 * @return Iterator<array-key, array{UnionType, Type, TrinaryLogic}>
 */',
        'startLine' => 177,
        'endLine' => 462,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => true,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'PHPStan\\Type',
        'declaringClassName' => 'PHPStan\\Type\\UnionTypeTest',
        'implementingClassName' => 'PHPStan\\Type\\UnionTypeTest',
        'currentClassName' => 'PHPStan\\Type\\UnionTypeTest',
        'aliasName' => NULL,
      ),
      'testIsSuperTypeOf' => 
      array (
        'name' => 'testIsSuperTypeOf',
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
                'name' => 'PHPStan\\Type\\UnionType',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 465,
            'endLine' => 465,
            'startColumn' => 36,
            'endColumn' => 50,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'otherType' => 
          array (
            'name' => 'otherType',
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
            'startLine' => 465,
            'endLine' => 465,
            'startColumn' => 53,
            'endColumn' => 67,
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
            'startLine' => 465,
            'endLine' => 465,
            'startColumn' => 70,
            'endColumn' => 97,
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
                'code' => '\'dataIsSuperTypeOf\'',
                'attributes' => 
                array (
                  'startLine' => 464,
                  'endLine' => 464,
                  'startTokenPos' => 2980,
                  'startFilePos' => 11930,
                  'endTokenPos' => 2980,
                  'endFilePos' => 11948,
                ),
              ),
            ),
          ),
        ),
        'docComment' => NULL,
        'startLine' => 464,
        'endLine' => 473,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type',
        'declaringClassName' => 'PHPStan\\Type\\UnionTypeTest',
        'implementingClassName' => 'PHPStan\\Type\\UnionTypeTest',
        'currentClassName' => 'PHPStan\\Type\\UnionTypeTest',
        'aliasName' => NULL,
      ),
      'dataIsSubTypeOf' => 
      array (
        'name' => 'dataIsSubTypeOf',
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
 * @return Iterator<int, array{UnionType, Type, TrinaryLogic}>
 */',
        'startLine' => 478,
        'endLine' => 635,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => true,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'PHPStan\\Type',
        'declaringClassName' => 'PHPStan\\Type\\UnionTypeTest',
        'implementingClassName' => 'PHPStan\\Type\\UnionTypeTest',
        'currentClassName' => 'PHPStan\\Type\\UnionTypeTest',
        'aliasName' => NULL,
      ),
      'testIsSubTypeOf' => 
      array (
        'name' => 'testIsSubTypeOf',
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
                'name' => 'PHPStan\\Type\\UnionType',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 638,
            'endLine' => 638,
            'startColumn' => 34,
            'endColumn' => 48,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'otherType' => 
          array (
            'name' => 'otherType',
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
            'startLine' => 638,
            'endLine' => 638,
            'startColumn' => 51,
            'endColumn' => 65,
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
            'startLine' => 638,
            'endLine' => 638,
            'startColumn' => 68,
            'endColumn' => 95,
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
                'code' => '\'dataIsSubTypeOf\'',
                'attributes' => 
                array (
                  'startLine' => 637,
                  'endLine' => 637,
                  'startTokenPos' => 3908,
                  'startFilePos' => 15268,
                  'endTokenPos' => 3908,
                  'endFilePos' => 15284,
                ),
              ),
            ),
          ),
        ),
        'docComment' => NULL,
        'startLine' => 637,
        'endLine' => 646,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type',
        'declaringClassName' => 'PHPStan\\Type\\UnionTypeTest',
        'implementingClassName' => 'PHPStan\\Type\\UnionTypeTest',
        'currentClassName' => 'PHPStan\\Type\\UnionTypeTest',
        'aliasName' => NULL,
      ),
      'testIsSubTypeOfInversed' => 
      array (
        'name' => 'testIsSubTypeOfInversed',
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
                'name' => 'PHPStan\\Type\\UnionType',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 649,
            'endLine' => 649,
            'startColumn' => 42,
            'endColumn' => 56,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'otherType' => 
          array (
            'name' => 'otherType',
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
            'startLine' => 649,
            'endLine' => 649,
            'startColumn' => 59,
            'endColumn' => 73,
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
            'startLine' => 649,
            'endLine' => 649,
            'startColumn' => 76,
            'endColumn' => 103,
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
                'code' => '\'dataIsSubTypeOf\'',
                'attributes' => 
                array (
                  'startLine' => 648,
                  'endLine' => 648,
                  'startTokenPos' => 4007,
                  'startFilePos' => 15682,
                  'endTokenPos' => 4007,
                  'endFilePos' => 15698,
                ),
              ),
            ),
          ),
        ),
        'docComment' => NULL,
        'startLine' => 648,
        'endLine' => 657,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type',
        'declaringClassName' => 'PHPStan\\Type\\UnionTypeTest',
        'implementingClassName' => 'PHPStan\\Type\\UnionTypeTest',
        'currentClassName' => 'PHPStan\\Type\\UnionTypeTest',
        'aliasName' => NULL,
      ),
      'dataIsScalar' => 
      array (
        'name' => 'dataIsScalar',
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
        'docComment' => NULL,
        'startLine' => 659,
        'endLine' => 709,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'PHPStan\\Type',
        'declaringClassName' => 'PHPStan\\Type\\UnionTypeTest',
        'implementingClassName' => 'PHPStan\\Type\\UnionTypeTest',
        'currentClassName' => 'PHPStan\\Type\\UnionTypeTest',
        'aliasName' => NULL,
      ),
      'testIsScalar' => 
      array (
        'name' => 'testIsScalar',
        'parameters' => 
        array (
          'unionType' => 
          array (
            'name' => 'unionType',
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
            'startLine' => 712,
            'endLine' => 712,
            'startColumn' => 31,
            'endColumn' => 45,
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
            'startLine' => 712,
            'endLine' => 712,
            'startColumn' => 48,
            'endColumn' => 75,
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
                'code' => '\'dataIsScalar\'',
                'attributes' => 
                array (
                  'startLine' => 711,
                  'endLine' => 711,
                  'startTokenPos' => 4402,
                  'startFilePos' => 17093,
                  'endTokenPos' => 4402,
                  'endFilePos' => 17106,
                ),
              ),
            ),
          ),
        ),
        'docComment' => NULL,
        'startLine' => 711,
        'endLine' => 722,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type',
        'declaringClassName' => 'PHPStan\\Type\\UnionTypeTest',
        'implementingClassName' => 'PHPStan\\Type\\UnionTypeTest',
        'currentClassName' => 'PHPStan\\Type\\UnionTypeTest',
        'aliasName' => NULL,
      ),
      'dataDescribe' => 
      array (
        'name' => 'dataDescribe',
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
        'docComment' => NULL,
        'startLine' => 724,
        'endLine' => 965,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'PHPStan\\Type',
        'declaringClassName' => 'PHPStan\\Type\\UnionTypeTest',
        'implementingClassName' => 'PHPStan\\Type\\UnionTypeTest',
        'currentClassName' => 'PHPStan\\Type\\UnionTypeTest',
        'aliasName' => NULL,
      ),
      'testDescribe' => 
      array (
        'name' => 'testDescribe',
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
            'startLine' => 969,
            'endLine' => 969,
            'startColumn' => 3,
            'endColumn' => 12,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'expectedCacheDescription' => 
          array (
            'name' => 'expectedCacheDescription',
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
            'startLine' => 970,
            'endLine' => 970,
            'startColumn' => 3,
            'endColumn' => 34,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'expectedPreciseDescription' => 
          array (
            'name' => 'expectedPreciseDescription',
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
            'startLine' => 971,
            'endLine' => 971,
            'startColumn' => 3,
            'endColumn' => 36,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'expectedValueDescription' => 
          array (
            'name' => 'expectedValueDescription',
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
            'startLine' => 972,
            'endLine' => 972,
            'startColumn' => 3,
            'endColumn' => 34,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
          'expectedTypeOnlyDescription' => 
          array (
            'name' => 'expectedTypeOnlyDescription',
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
            'startLine' => 973,
            'endLine' => 973,
            'startColumn' => 3,
            'endColumn' => 37,
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
                'code' => '\'dataDescribe\'',
                'attributes' => 
                array (
                  'startLine' => 967,
                  'endLine' => 967,
                  'startTokenPos' => 5741,
                  'startFilePos' => 24179,
                  'endTokenPos' => 5741,
                  'endFilePos' => 24192,
                ),
              ),
            ),
          ),
        ),
        'docComment' => NULL,
        'startLine' => 967,
        'endLine' => 980,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type',
        'declaringClassName' => 'PHPStan\\Type\\UnionTypeTest',
        'implementingClassName' => 'PHPStan\\Type\\UnionTypeTest',
        'currentClassName' => 'PHPStan\\Type\\UnionTypeTest',
        'aliasName' => NULL,
      ),
      'dataAccepts' => 
      array (
        'name' => 'dataAccepts',
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
        'startLine' => 982,
        'endLine' => 1321,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => true,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'PHPStan\\Type',
        'declaringClassName' => 'PHPStan\\Type\\UnionTypeTest',
        'implementingClassName' => 'PHPStan\\Type\\UnionTypeTest',
        'currentClassName' => 'PHPStan\\Type\\UnionTypeTest',
        'aliasName' => NULL,
      ),
      'testAccepts' => 
      array (
        'name' => 'testAccepts',
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
                'name' => 'PHPStan\\Type\\UnionType',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1325,
            'endLine' => 1325,
            'startColumn' => 3,
            'endColumn' => 17,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'acceptedType' => 
          array (
            'name' => 'acceptedType',
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
            'startLine' => 1326,
            'endLine' => 1326,
            'startColumn' => 3,
            'endColumn' => 20,
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
            'startLine' => 1327,
            'endLine' => 1327,
            'startColumn' => 3,
            'endColumn' => 30,
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
                'code' => '\'dataAccepts\'',
                'attributes' => 
                array (
                  'startLine' => 1323,
                  'endLine' => 1323,
                  'startTokenPos' => 8356,
                  'startFilePos' => 35196,
                  'endTokenPos' => 8356,
                  'endFilePos' => 35208,
                ),
              ),
            ),
          ),
        ),
        'docComment' => NULL,
        'startLine' => 1323,
        'endLine' => 1335,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type',
        'declaringClassName' => 'PHPStan\\Type\\UnionTypeTest',
        'implementingClassName' => 'PHPStan\\Type\\UnionTypeTest',
        'currentClassName' => 'PHPStan\\Type\\UnionTypeTest',
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 1337,
        'endLine' => 1361,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'PHPStan\\Type',
        'declaringClassName' => 'PHPStan\\Type\\UnionTypeTest',
        'implementingClassName' => 'PHPStan\\Type\\UnionTypeTest',
        'currentClassName' => 'PHPStan\\Type\\UnionTypeTest',
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
                'name' => 'PHPStan\\Type\\UnionType',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1365,
            'endLine' => 1365,
            'startColumn' => 3,
            'endColumn' => 17,
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
            'startLine' => 1366,
            'endLine' => 1366,
            'startColumn' => 3,
            'endColumn' => 20,
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
            'startLine' => 1367,
            'endLine' => 1367,
            'startColumn' => 3,
            'endColumn' => 30,
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
                  'startLine' => 1363,
                  'endLine' => 1363,
                  'startTokenPos' => 8636,
                  'startFilePos' => 36205,
                  'endTokenPos' => 8636,
                  'endFilePos' => 36219,
                ),
              ),
            ),
          ),
        ),
        'docComment' => NULL,
        'startLine' => 1363,
        'endLine' => 1371,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type',
        'declaringClassName' => 'PHPStan\\Type\\UnionTypeTest',
        'implementingClassName' => 'PHPStan\\Type\\UnionTypeTest',
        'currentClassName' => 'PHPStan\\Type\\UnionTypeTest',
        'aliasName' => NULL,
      ),
      'testSorting' => 
      array (
        'name' => 'testSorting',
        'parameters' => 
        array (
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
        ),
        'docComment' => NULL,
        'startLine' => 1373,
        'endLine' => 1411,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type',
        'declaringClassName' => 'PHPStan\\Type\\UnionTypeTest',
        'implementingClassName' => 'PHPStan\\Type\\UnionTypeTest',
        'currentClassName' => 'PHPStan\\Type\\UnionTypeTest',
        'aliasName' => NULL,
      ),
      'testGetConstantArrays' => 
      array (
        'name' => 'testGetConstantArrays',
        'parameters' => 
        array (
          'types' => 
          array (
            'name' => 'types',
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
            'startLine' => 1419,
            'endLine' => 1419,
            'startColumn' => 3,
            'endColumn' => 14,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'expectedDescriptions' => 
          array (
            'name' => 'expectedDescriptions',
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
            'startLine' => 1420,
            'endLine' => 1420,
            'startColumn' => 3,
            'endColumn' => 29,
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
                'code' => '\'dataGetConstantArrays\'',
                'attributes' => 
                array (
                  'startLine' => 1417,
                  'endLine' => 1417,
                  'startTokenPos' => 8990,
                  'startFilePos' => 37638,
                  'endTokenPos' => 8990,
                  'endFilePos' => 37660,
                ),
              ),
            ),
          ),
        ),
        'docComment' => '/**
 * @param list<Type> $types
 * @param list<string> $expectedDescriptions
 */',
        'startLine' => 1417,
        'endLine' => 1432,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type',
        'declaringClassName' => 'PHPStan\\Type\\UnionTypeTest',
        'implementingClassName' => 'PHPStan\\Type\\UnionTypeTest',
        'currentClassName' => 'PHPStan\\Type\\UnionTypeTest',
        'aliasName' => NULL,
      ),
      'dataGetConstantArrays' => 
      array (
        'name' => 'dataGetConstantArrays',
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
        'startLine' => 1434,
        'endLine' => 1474,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => true,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'PHPStan\\Type',
        'declaringClassName' => 'PHPStan\\Type\\UnionTypeTest',
        'implementingClassName' => 'PHPStan\\Type\\UnionTypeTest',
        'currentClassName' => 'PHPStan\\Type\\UnionTypeTest',
        'aliasName' => NULL,
      ),
      'testGetConstantStrings' => 
      array (
        'name' => 'testGetConstantStrings',
        'parameters' => 
        array (
          'unionType' => 
          array (
            'name' => 'unionType',
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
            'startLine' => 1481,
            'endLine' => 1481,
            'startColumn' => 3,
            'endColumn' => 17,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'expectedDescriptions' => 
          array (
            'name' => 'expectedDescriptions',
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
            'startLine' => 1482,
            'endLine' => 1482,
            'startColumn' => 3,
            'endColumn' => 29,
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
                'code' => '\'dataGetConstantStrings\'',
                'attributes' => 
                array (
                  'startLine' => 1479,
                  'endLine' => 1479,
                  'startTokenPos' => 9352,
                  'startFilePos' => 39071,
                  'endTokenPos' => 9352,
                  'endFilePos' => 39094,
                ),
              ),
            ),
          ),
        ),
        'docComment' => '/**
 * @param list<string> $expectedDescriptions
 */',
        'startLine' => 1479,
        'endLine' => 1493,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type',
        'declaringClassName' => 'PHPStan\\Type\\UnionTypeTest',
        'implementingClassName' => 'PHPStan\\Type\\UnionTypeTest',
        'currentClassName' => 'PHPStan\\Type\\UnionTypeTest',
        'aliasName' => NULL,
      ),
      'dataGetConstantStrings' => 
      array (
        'name' => 'dataGetConstantStrings',
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
        'startLine' => 1495,
        'endLine' => 1548,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => true,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'PHPStan\\Type',
        'declaringClassName' => 'PHPStan\\Type\\UnionTypeTest',
        'implementingClassName' => 'PHPStan\\Type\\UnionTypeTest',
        'currentClassName' => 'PHPStan\\Type\\UnionTypeTest',
        'aliasName' => NULL,
      ),
      'testGetObjectClassNames' => 
      array (
        'name' => 'testGetObjectClassNames',
        'parameters' => 
        array (
          'unionType' => 
          array (
            'name' => 'unionType',
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
            'startLine' => 1555,
            'endLine' => 1555,
            'startColumn' => 3,
            'endColumn' => 17,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'expectedObjectClassNames' => 
          array (
            'name' => 'expectedObjectClassNames',
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
            'startLine' => 1556,
            'endLine' => 1556,
            'startColumn' => 3,
            'endColumn' => 33,
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
                'code' => '\'dataGetObjectClassNames\'',
                'attributes' => 
                array (
                  'startLine' => 1553,
                  'endLine' => 1553,
                  'startTokenPos' => 9680,
                  'startFilePos' => 40483,
                  'endTokenPos' => 9680,
                  'endFilePos' => 40507,
                ),
              ),
            ),
          ),
        ),
        'docComment' => '/**
 * @param list<string> $expectedObjectClassNames
 */',
        'startLine' => 1553,
        'endLine' => 1560,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type',
        'declaringClassName' => 'PHPStan\\Type\\UnionTypeTest',
        'implementingClassName' => 'PHPStan\\Type\\UnionTypeTest',
        'currentClassName' => 'PHPStan\\Type\\UnionTypeTest',
        'aliasName' => NULL,
      ),
      'dataGetObjectClassNames' => 
      array (
        'name' => 'dataGetObjectClassNames',
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
        'startLine' => 1562,
        'endLine' => 1590,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => true,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'PHPStan\\Type',
        'declaringClassName' => 'PHPStan\\Type\\UnionTypeTest',
        'implementingClassName' => 'PHPStan\\Type\\UnionTypeTest',
        'currentClassName' => 'PHPStan\\Type\\UnionTypeTest',
        'aliasName' => NULL,
      ),
      'testGetArrays' => 
      array (
        'name' => 'testGetArrays',
        'parameters' => 
        array (
          'unionType' => 
          array (
            'name' => 'unionType',
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
            'startLine' => 1597,
            'endLine' => 1597,
            'startColumn' => 3,
            'endColumn' => 17,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'expectedDescriptions' => 
          array (
            'name' => 'expectedDescriptions',
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
            'startLine' => 1598,
            'endLine' => 1598,
            'startColumn' => 3,
            'endColumn' => 29,
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
                'code' => '\'dataGetArrays\'',
                'attributes' => 
                array (
                  'startLine' => 1595,
                  'endLine' => 1595,
                  'startTokenPos' => 9863,
                  'startFilePos' => 41260,
                  'endTokenPos' => 9863,
                  'endFilePos' => 41274,
                ),
              ),
            ),
          ),
        ),
        'docComment' => '/**
 * @param list<string> $expectedDescriptions
 */',
        'startLine' => 1595,
        'endLine' => 1609,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type',
        'declaringClassName' => 'PHPStan\\Type\\UnionTypeTest',
        'implementingClassName' => 'PHPStan\\Type\\UnionTypeTest',
        'currentClassName' => 'PHPStan\\Type\\UnionTypeTest',
        'aliasName' => NULL,
      ),
      'dataGetArrays' => 
      array (
        'name' => 'dataGetArrays',
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
        'startLine' => 1611,
        'endLine' => 1663,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => true,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'PHPStan\\Type',
        'declaringClassName' => 'PHPStan\\Type\\UnionTypeTest',
        'implementingClassName' => 'PHPStan\\Type\\UnionTypeTest',
        'currentClassName' => 'PHPStan\\Type\\UnionTypeTest',
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