<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/tests/PHPStan/DependencyInjection/InvalidIgnoredErrorExceptionTest.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\DependencyInjection\InvalidIgnoredErrorExceptionTest
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-4bacbbbb49ccd6ef8975f42ac588aaea9ab23852bb395a72b8abadeb6178a26c',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\DependencyInjection\\InvalidIgnoredErrorExceptionTest',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/tests/PHPStan/DependencyInjection/InvalidIgnoredErrorExceptionTest.php',
      ),
    ),
    'namespace' => 'PHPStan\\DependencyInjection',
    'name' => 'PHPStan\\DependencyInjection\\InvalidIgnoredErrorExceptionTest',
    'shortName' => 'InvalidIgnoredErrorExceptionTest',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => NULL,
    'attributes' => 
    array (
    ),
    'startLine' => 8,
    'endLine' => 88,
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
      'configFile' => 
      array (
        'declaringClassName' => 'PHPStan\\DependencyInjection\\InvalidIgnoredErrorExceptionTest',
        'implementingClassName' => 'PHPStan\\DependencyInjection\\InvalidIgnoredErrorExceptionTest',
        'name' => 'configFile',
        'modifiers' => 20,
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
        'default' => 
        array (
          'code' => 'null',
          'attributes' => 
          array (
            'startLine' => 11,
            'endLine' => 11,
            'startTokenPos' => 47,
            'startFilePos' => 263,
            'endTokenPos' => 47,
            'endFilePos' => 266,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 11,
        'endLine' => 11,
        'startColumn' => 2,
        'endColumn' => 43,
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
      'dataValidateIgnoreErrors' => 
      array (
        'name' => 'dataValidateIgnoreErrors',
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
        'docComment' => '/**
 * @return iterable<array{string, string}>
 */',
        'startLine' => 16,
        'endLine' => 66,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => true,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'PHPStan\\DependencyInjection',
        'declaringClassName' => 'PHPStan\\DependencyInjection\\InvalidIgnoredErrorExceptionTest',
        'implementingClassName' => 'PHPStan\\DependencyInjection\\InvalidIgnoredErrorExceptionTest',
        'currentClassName' => 'PHPStan\\DependencyInjection\\InvalidIgnoredErrorExceptionTest',
        'aliasName' => NULL,
      ),
      'testValidateIgnoreErrors' => 
      array (
        'name' => 'testValidateIgnoreErrors',
        'parameters' => 
        array (
          'file' => 
          array (
            'name' => 'file',
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
            'startLine' => 69,
            'endLine' => 69,
            'startColumn' => 43,
            'endColumn' => 54,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'expectedMessage' => 
          array (
            'name' => 'expectedMessage',
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
            'startLine' => 69,
            'endLine' => 69,
            'startColumn' => 57,
            'endColumn' => 79,
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
                'code' => '\'dataValidateIgnoreErrors\'',
                'attributes' => 
                array (
                  'startLine' => 68,
                  'endLine' => 68,
                  'startTokenPos' => 276,
                  'startFilePos' => 2403,
                  'endTokenPos' => 276,
                  'endFilePos' => 2428,
                ),
              ),
            ),
          ),
        ),
        'docComment' => NULL,
        'startLine' => 68,
        'endLine' => 74,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\DependencyInjection',
        'declaringClassName' => 'PHPStan\\DependencyInjection\\InvalidIgnoredErrorExceptionTest',
        'implementingClassName' => 'PHPStan\\DependencyInjection\\InvalidIgnoredErrorExceptionTest',
        'currentClassName' => 'PHPStan\\DependencyInjection\\InvalidIgnoredErrorExceptionTest',
        'aliasName' => NULL,
      ),
      'getAdditionalConfigFiles' => 
      array (
        'name' => 'getAdditionalConfigFiles',
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
        'startLine' => 76,
        'endLine' => 86,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'PHPStan\\DependencyInjection',
        'declaringClassName' => 'PHPStan\\DependencyInjection\\InvalidIgnoredErrorExceptionTest',
        'implementingClassName' => 'PHPStan\\DependencyInjection\\InvalidIgnoredErrorExceptionTest',
        'currentClassName' => 'PHPStan\\DependencyInjection\\InvalidIgnoredErrorExceptionTest',
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