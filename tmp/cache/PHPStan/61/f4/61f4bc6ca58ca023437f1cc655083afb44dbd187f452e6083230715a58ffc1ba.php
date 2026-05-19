<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/tests/PHPStan/Reflection/ReflectionProviderTest.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\Reflection\ReflectionProviderTest
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-11f81452c445ab4e7e245a09f58b0b3026dfdd10cd9e557ffbe183b0e2d4b554',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\Reflection\\ReflectionProviderTest',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/tests/PHPStan/Reflection/ReflectionProviderTest.php',
      ),
    ),
    'namespace' => 'PHPStan\\Reflection',
    'name' => 'PHPStan\\Reflection\\ReflectionProviderTest',
    'shortName' => 'ReflectionProviderTest',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => NULL,
    'attributes' => 
    array (
    ),
    'startLine' => 16,
    'endLine' => 186,
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
      'dataFunctionThrowType' => 
      array (
        'name' => 'dataFunctionThrowType',
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
        'startLine' => 19,
        'endLine' => 52,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => true,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'PHPStan\\Reflection',
        'declaringClassName' => 'PHPStan\\Reflection\\ReflectionProviderTest',
        'implementingClassName' => 'PHPStan\\Reflection\\ReflectionProviderTest',
        'currentClassName' => 'PHPStan\\Reflection\\ReflectionProviderTest',
        'aliasName' => NULL,
      ),
      'testFunctionThrowType' => 
      array (
        'name' => 'testFunctionThrowType',
        'parameters' => 
        array (
          'functionName' => 
          array (
            'name' => 'functionName',
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
            'startLine' => 58,
            'endLine' => 58,
            'startColumn' => 40,
            'endColumn' => 59,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'expectedThrowType' => 
          array (
            'name' => 'expectedThrowType',
            'default' => NULL,
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
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 58,
            'endLine' => 58,
            'startColumn' => 62,
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
                'code' => '\'dataFunctionThrowType\'',
                'attributes' => 
                array (
                  'startLine' => 57,
                  'endLine' => 57,
                  'startTokenPos' => 218,
                  'startFilePos' => 991,
                  'endTokenPos' => 218,
                  'endFilePos' => 1013,
                ),
              ),
            ),
          ),
        ),
        'docComment' => '/**
 * @param non-empty-string $functionName
 */',
        'startLine' => 57,
        'endLine' => 72,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection',
        'declaringClassName' => 'PHPStan\\Reflection\\ReflectionProviderTest',
        'implementingClassName' => 'PHPStan\\Reflection\\ReflectionProviderTest',
        'currentClassName' => 'PHPStan\\Reflection\\ReflectionProviderTest',
        'aliasName' => NULL,
      ),
      'dataFunctionDeprecated' => 
      array (
        'name' => 'dataFunctionDeprecated',
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
        'startLine' => 74,
        'endLine' => 98,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => true,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'PHPStan\\Reflection',
        'declaringClassName' => 'PHPStan\\Reflection\\ReflectionProviderTest',
        'implementingClassName' => 'PHPStan\\Reflection\\ReflectionProviderTest',
        'currentClassName' => 'PHPStan\\Reflection\\ReflectionProviderTest',
        'aliasName' => NULL,
      ),
      'testFunctionDeprecated' => 
      array (
        'name' => 'testFunctionDeprecated',
        'parameters' => 
        array (
          'functionName' => 
          array (
            'name' => 'functionName',
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
            'startLine' => 104,
            'endLine' => 104,
            'startColumn' => 41,
            'endColumn' => 60,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'isDeprecated' => 
          array (
            'name' => 'isDeprecated',
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
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 104,
            'endLine' => 104,
            'startColumn' => 63,
            'endColumn' => 80,
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
                'code' => '\'dataFunctionDeprecated\'',
                'attributes' => 
                array (
                  'startLine' => 103,
                  'endLine' => 103,
                  'startTokenPos' => 475,
                  'startFilePos' => 2026,
                  'endTokenPos' => 475,
                  'endFilePos' => 2049,
                ),
              ),
            ),
          ),
        ),
        'docComment' => '/**
 * @param non-empty-string $functionName
 */',
        'startLine' => 103,
        'endLine' => 109,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection',
        'declaringClassName' => 'PHPStan\\Reflection\\ReflectionProviderTest',
        'implementingClassName' => 'PHPStan\\Reflection\\ReflectionProviderTest',
        'currentClassName' => 'PHPStan\\Reflection\\ReflectionProviderTest',
        'aliasName' => NULL,
      ),
      'dataConstantDeprecated' => 
      array (
        'name' => 'dataConstantDeprecated',
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
        'startLine' => 111,
        'endLine' => 128,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => true,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'PHPStan\\Reflection',
        'declaringClassName' => 'PHPStan\\Reflection\\ReflectionProviderTest',
        'implementingClassName' => 'PHPStan\\Reflection\\ReflectionProviderTest',
        'currentClassName' => 'PHPStan\\Reflection\\ReflectionProviderTest',
        'aliasName' => NULL,
      ),
      'testConstantDeprecated' => 
      array (
        'name' => 'testConstantDeprecated',
        'parameters' => 
        array (
          'constantName' => 
          array (
            'name' => 'constantName',
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
            'startLine' => 134,
            'endLine' => 134,
            'startColumn' => 41,
            'endColumn' => 60,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'isDeprecated' => 
          array (
            'name' => 'isDeprecated',
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
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 134,
            'endLine' => 134,
            'startColumn' => 63,
            'endColumn' => 80,
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
                'code' => '\'dataConstantDeprecated\'',
                'attributes' => 
                array (
                  'startLine' => 133,
                  'endLine' => 133,
                  'startTokenPos' => 628,
                  'startFilePos' => 2797,
                  'endTokenPos' => 628,
                  'endFilePos' => 2820,
                ),
              ),
            ),
          ),
        ),
        'docComment' => '/**
 * @param non-empty-string $constantName
 */',
        'startLine' => 133,
        'endLine' => 139,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection',
        'declaringClassName' => 'PHPStan\\Reflection\\ReflectionProviderTest',
        'implementingClassName' => 'PHPStan\\Reflection\\ReflectionProviderTest',
        'currentClassName' => 'PHPStan\\Reflection\\ReflectionProviderTest',
        'aliasName' => NULL,
      ),
      'dataMethodThrowType' => 
      array (
        'name' => 'dataMethodThrowType',
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
        'startLine' => 141,
        'endLine' => 155,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'PHPStan\\Reflection',
        'declaringClassName' => 'PHPStan\\Reflection\\ReflectionProviderTest',
        'implementingClassName' => 'PHPStan\\Reflection\\ReflectionProviderTest',
        'currentClassName' => 'PHPStan\\Reflection\\ReflectionProviderTest',
        'aliasName' => NULL,
      ),
      'testMethodThrowType' => 
      array (
        'name' => 'testMethodThrowType',
        'parameters' => 
        array (
          'className' => 
          array (
            'name' => 'className',
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
            'startLine' => 158,
            'endLine' => 158,
            'startColumn' => 38,
            'endColumn' => 54,
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
            'startLine' => 158,
            'endLine' => 158,
            'startColumn' => 57,
            'endColumn' => 74,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'expectedThrowType' => 
          array (
            'name' => 'expectedThrowType',
            'default' => NULL,
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
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 158,
            'endLine' => 158,
            'startColumn' => 77,
            'endColumn' => 100,
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
                'code' => '\'dataMethodThrowType\'',
                'attributes' => 
                array (
                  'startLine' => 157,
                  'endLine' => 157,
                  'startTokenPos' => 795,
                  'startFilePos' => 3483,
                  'endTokenPos' => 795,
                  'endFilePos' => 3503,
                ),
              ),
            ),
          ),
        ),
        'docComment' => NULL,
        'startLine' => 157,
        'endLine' => 173,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection',
        'declaringClassName' => 'PHPStan\\Reflection\\ReflectionProviderTest',
        'implementingClassName' => 'PHPStan\\Reflection\\ReflectionProviderTest',
        'currentClassName' => 'PHPStan\\Reflection\\ReflectionProviderTest',
        'aliasName' => NULL,
      ),
      'testNativeClassConstantTypeInEvaledClass' => 
      array (
        'name' => 'testNativeClassConstantTypeInEvaledClass',
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
          0 => 
          array (
            'name' => 'PHPUnit\\Framework\\Attributes\\RequiresPhp',
            'isRepeated' => false,
            'arguments' => 
            array (
              0 => 
              array (
                'code' => '\'>= 8.3.0\'',
                'attributes' => 
                array (
                  'startLine' => 175,
                  'endLine' => 175,
                  'startTokenPos' => 942,
                  'startFilePos' => 4099,
                  'endTokenPos' => 942,
                  'endFilePos' => 4108,
                ),
              ),
            ),
          ),
        ),
        'docComment' => NULL,
        'startLine' => 175,
        'endLine' => 184,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection',
        'declaringClassName' => 'PHPStan\\Reflection\\ReflectionProviderTest',
        'implementingClassName' => 'PHPStan\\Reflection\\ReflectionProviderTest',
        'currentClassName' => 'PHPStan\\Reflection\\ReflectionProviderTest',
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