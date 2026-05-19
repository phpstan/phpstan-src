<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/tests/PHPStan/Analyser/ArgumentsNormalizerTest.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\Analyser\ArgumentsNormalizerTest
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-de242618b2c48d3b7603b1f75fa190dee5396b733ef6c40723292eb5f568ee64',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\Analyser\\ArgumentsNormalizerTest',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/tests/PHPStan/Analyser/ArgumentsNormalizerTest.php',
      ),
    ),
    'namespace' => 'PHPStan\\Analyser',
    'name' => 'PHPStan\\Analyser\\ArgumentsNormalizerTest',
    'shortName' => 'ArgumentsNormalizerTest',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => NULL,
    'attributes' => 
    array (
    ),
    'startLine' => 23,
    'endLine' => 395,
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
      'dataReorderValid' => 
      array (
        'name' => 'dataReorderValid',
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
        'startLine' => 26,
        'endLine' => 246,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => true,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'PHPStan\\Analyser',
        'declaringClassName' => 'PHPStan\\Analyser\\ArgumentsNormalizerTest',
        'implementingClassName' => 'PHPStan\\Analyser\\ArgumentsNormalizerTest',
        'currentClassName' => 'PHPStan\\Analyser\\ArgumentsNormalizerTest',
        'aliasName' => NULL,
      ),
      'testReorderValid' => 
      array (
        'name' => 'testReorderValid',
        'parameters' => 
        array (
          'parameterSettings' => 
          array (
            'name' => 'parameterSettings',
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
            'startLine' => 255,
            'endLine' => 255,
            'startColumn' => 3,
            'endColumn' => 26,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'argumentSettings' => 
          array (
            'name' => 'argumentSettings',
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
            'startLine' => 256,
            'endLine' => 256,
            'startColumn' => 3,
            'endColumn' => 25,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'expectedArgumentTypes' => 
          array (
            'name' => 'expectedArgumentTypes',
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
            'startLine' => 257,
            'endLine' => 257,
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
                'code' => '\'dataReorderValid\'',
                'attributes' => 
                array (
                  'startLine' => 253,
                  'endLine' => 253,
                  'startTokenPos' => 1641,
                  'startFilePos' => 4795,
                  'endTokenPos' => 1641,
                  'endFilePos' => 4812,
                ),
              ),
            ),
          ),
        ),
        'docComment' => '/**
 * @param array<int, array{non-empty-string, bool, bool, ?Type}> $parameterSettings
 * @param array<int, array{Type, ?non-empty-string}> $argumentSettings
 * @param array<int, Type> $expectedArgumentTypes
 */',
        'startLine' => 253,
        'endLine' => 300,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Analyser',
        'declaringClassName' => 'PHPStan\\Analyser\\ArgumentsNormalizerTest',
        'implementingClassName' => 'PHPStan\\Analyser\\ArgumentsNormalizerTest',
        'currentClassName' => 'PHPStan\\Analyser\\ArgumentsNormalizerTest',
        'aliasName' => NULL,
      ),
      'dataReorderInvalid' => 
      array (
        'name' => 'dataReorderInvalid',
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
        'startLine' => 302,
        'endLine' => 353,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => true,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'PHPStan\\Analyser',
        'declaringClassName' => 'PHPStan\\Analyser\\ArgumentsNormalizerTest',
        'implementingClassName' => 'PHPStan\\Analyser\\ArgumentsNormalizerTest',
        'currentClassName' => 'PHPStan\\Analyser\\ArgumentsNormalizerTest',
        'aliasName' => NULL,
      ),
      'testReorderInvalid' => 
      array (
        'name' => 'testReorderInvalid',
        'parameters' => 
        array (
          'parameterSettings' => 
          array (
            'name' => 'parameterSettings',
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
            'startLine' => 361,
            'endLine' => 361,
            'startColumn' => 3,
            'endColumn' => 26,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'argumentSettings' => 
          array (
            'name' => 'argumentSettings',
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
            'startLine' => 362,
            'endLine' => 362,
            'startColumn' => 3,
            'endColumn' => 25,
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
                'code' => '\'dataReorderInvalid\'',
                'attributes' => 
                array (
                  'startLine' => 359,
                  'endLine' => 359,
                  'startTokenPos' => 2355,
                  'startFilePos' => 7315,
                  'endTokenPos' => 2355,
                  'endFilePos' => 7334,
                ),
              ),
            ),
          ),
        ),
        'docComment' => '/**
 * @param array<int, array{non-empty-string, bool, bool, ?Type}> $parameterSettings
 * @param array<int, array{Type, ?non-empty-string}> $argumentSettings
 */',
        'startLine' => 359,
        'endLine' => 393,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Analyser',
        'declaringClassName' => 'PHPStan\\Analyser\\ArgumentsNormalizerTest',
        'implementingClassName' => 'PHPStan\\Analyser\\ArgumentsNormalizerTest',
        'currentClassName' => 'PHPStan\\Analyser\\ArgumentsNormalizerTest',
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