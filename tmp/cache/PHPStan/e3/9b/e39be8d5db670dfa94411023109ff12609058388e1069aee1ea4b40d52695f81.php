<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/tests/PHPStan/Type/Enum/EnumCaseObjectTypeTest.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\Type\Enum\EnumCaseObjectTypeTest
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-7ad1356e397720e57c6436642cfbe691042cf5b31052cdd1b65d5a44ae1c3327',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\Type\\Enum\\EnumCaseObjectTypeTest',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/tests/PHPStan/Type/Enum/EnumCaseObjectTypeTest.php',
      ),
    ),
    'namespace' => 'PHPStan\\Type\\Enum',
    'name' => 'PHPStan\\Type\\Enum\\EnumCaseObjectTypeTest',
    'shortName' => 'EnumCaseObjectTypeTest',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => NULL,
    'attributes' => 
    array (
    ),
    'startLine' => 17,
    'endLine' => 219,
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
            'name' => 'iterable',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 20,
        'endLine' => 104,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => true,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'PHPStan\\Type\\Enum',
        'declaringClassName' => 'PHPStan\\Type\\Enum\\EnumCaseObjectTypeTest',
        'implementingClassName' => 'PHPStan\\Type\\Enum\\EnumCaseObjectTypeTest',
        'currentClassName' => 'PHPStan\\Type\\Enum\\EnumCaseObjectTypeTest',
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
            'startLine' => 108,
            'endLine' => 108,
            'startColumn' => 36,
            'endColumn' => 45,
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
            'startLine' => 108,
            'endLine' => 108,
            'startColumn' => 48,
            'endColumn' => 62,
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
            'startLine' => 108,
            'endLine' => 108,
            'startColumn' => 65,
            'endColumn' => 92,
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
            'name' => 'PHPUnit\\Framework\\Attributes\\RequiresPhp',
            'isRepeated' => false,
            'arguments' => 
            array (
              0 => 
              array (
                'code' => '\'>= 8.1.0\'',
                'attributes' => 
                array (
                  'startLine' => 106,
                  'endLine' => 106,
                  'startTokenPos' => 676,
                  'startFilePos' => 3225,
                  'endTokenPos' => 676,
                  'endFilePos' => 3234,
                ),
              ),
            ),
          ),
          1 => 
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
                  'startLine' => 107,
                  'endLine' => 107,
                  'startTokenPos' => 683,
                  'startFilePos' => 3254,
                  'endTokenPos' => 683,
                  'endFilePos' => 3272,
                ),
              ),
            ),
          ),
        ),
        'docComment' => NULL,
        'startLine' => 106,
        'endLine' => 116,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type\\Enum',
        'declaringClassName' => 'PHPStan\\Type\\Enum\\EnumCaseObjectTypeTest',
        'implementingClassName' => 'PHPStan\\Type\\Enum\\EnumCaseObjectTypeTest',
        'currentClassName' => 'PHPStan\\Type\\Enum\\EnumCaseObjectTypeTest',
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
        'startLine' => 118,
        'endLine' => 202,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => true,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'PHPStan\\Type\\Enum',
        'declaringClassName' => 'PHPStan\\Type\\Enum\\EnumCaseObjectTypeTest',
        'implementingClassName' => 'PHPStan\\Type\\Enum\\EnumCaseObjectTypeTest',
        'currentClassName' => 'PHPStan\\Type\\Enum\\EnumCaseObjectTypeTest',
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
            'startLine' => 207,
            'endLine' => 207,
            'startColumn' => 3,
            'endColumn' => 12,
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
            'startLine' => 208,
            'endLine' => 208,
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
            'startLine' => 209,
            'endLine' => 209,
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
            'name' => 'PHPUnit\\Framework\\Attributes\\RequiresPhp',
            'isRepeated' => false,
            'arguments' => 
            array (
              0 => 
              array (
                'code' => '\'>= 8.1.0\'',
                'attributes' => 
                array (
                  'startLine' => 204,
                  'endLine' => 204,
                  'startTokenPos' => 1372,
                  'startFilePos' => 6389,
                  'endTokenPos' => 1372,
                  'endFilePos' => 6398,
                ),
              ),
            ),
          ),
          1 => 
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
                  'startLine' => 205,
                  'endLine' => 205,
                  'startTokenPos' => 1379,
                  'startFilePos' => 6418,
                  'endTokenPos' => 1379,
                  'endFilePos' => 6430,
                ),
              ),
            ),
          ),
        ),
        'docComment' => NULL,
        'startLine' => 204,
        'endLine' => 217,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type\\Enum',
        'declaringClassName' => 'PHPStan\\Type\\Enum\\EnumCaseObjectTypeTest',
        'implementingClassName' => 'PHPStan\\Type\\Enum\\EnumCaseObjectTypeTest',
        'currentClassName' => 'PHPStan\\Type\\Enum\\EnumCaseObjectTypeTest',
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