<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Assert.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPUnit\Framework\Assert
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-f7de0fec7c02503304bc2da6fbbd788039f77b65a8a523377a000346b2a31839',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPUnit\\Framework\\Assert',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/vendor/phpunit/phpunit/src/Framework/Assert.php',
      ),
    ),
    'namespace' => 'PHPUnit\\Framework',
    'name' => 'PHPUnit\\Framework\\Assert',
    'shortName' => 'Assert',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 64,
    'docComment' => '/**
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for PHPUnit
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 73,
    'endLine' => 3151,
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
      'count' => 
      array (
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'name' => 'count',
        'modifiers' => 20,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'int',
            'isIdentifier' => true,
          ),
        ),
        'default' => 
        array (
          'code' => '0',
          'attributes' => 
          array (
            'startLine' => 75,
            'endLine' => 75,
            'startTokenPos' => 339,
            'startFilePos' => 2881,
            'endTokenPos' => 339,
            'endFilePos' => 2881,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 75,
        'endLine' => 75,
        'startColumn' => 5,
        'endColumn' => 34,
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
      'assertArrayIsEqualToArrayOnlyConsideringListOfKeys' => 
      array (
        'name' => 'assertArrayIsEqualToArrayOnlyConsideringListOfKeys',
        'parameters' => 
        array (
          'expected' => 
          array (
            'name' => 'expected',
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
            'startLine' => 87,
            'endLine' => 87,
            'startColumn' => 85,
            'endColumn' => 99,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'actual' => 
          array (
            'name' => 'actual',
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
            'startLine' => 87,
            'endLine' => 87,
            'startColumn' => 102,
            'endColumn' => 114,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'keysToBeConsidered' => 
          array (
            'name' => 'keysToBeConsidered',
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
            'startLine' => 87,
            'endLine' => 87,
            'startColumn' => 117,
            'endColumn' => 141,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 87,
                'endLine' => 87,
                'startTokenPos' => 375,
                'startFilePos' => 3381,
                'endTokenPos' => 375,
                'endFilePos' => 3382,
              ),
            ),
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
            'startLine' => 87,
            'endLine' => 87,
            'startColumn' => 144,
            'endColumn' => 163,
            'parameterIndex' => 3,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that two arrays are equal while only considering a list of keys.
 *
 * @param array<mixed>              $expected
 * @param array<mixed>              $actual
 * @param non-empty-list<array-key> $keysToBeConsidered
 *
 * @throws Exception
 * @throws ExpectationFailedException
 */',
        'startLine' => 87,
        'endLine' => 106,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertArrayIsEqualToArrayIgnoringListOfKeys' => 
      array (
        'name' => 'assertArrayIsEqualToArrayIgnoringListOfKeys',
        'parameters' => 
        array (
          'expected' => 
          array (
            'name' => 'expected',
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
            'startLine' => 118,
            'endLine' => 118,
            'startColumn' => 78,
            'endColumn' => 92,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'actual' => 
          array (
            'name' => 'actual',
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
            'startLine' => 118,
            'endLine' => 118,
            'startColumn' => 95,
            'endColumn' => 107,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'keysToBeIgnored' => 
          array (
            'name' => 'keysToBeIgnored',
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
            'startLine' => 118,
            'endLine' => 118,
            'startColumn' => 110,
            'endColumn' => 131,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 118,
                'endLine' => 118,
                'startTokenPos' => 534,
                'startFilePos' => 4359,
                'endTokenPos' => 534,
                'endFilePos' => 4360,
              ),
            ),
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
            'startLine' => 118,
            'endLine' => 118,
            'startColumn' => 134,
            'endColumn' => 153,
            'parameterIndex' => 3,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that two arrays are equal while ignoring a list of keys.
 *
 * @param array<mixed>              $expected
 * @param array<mixed>              $actual
 * @param non-empty-list<array-key> $keysToBeIgnored
 *
 * @throws Exception
 * @throws ExpectationFailedException
 */',
        'startLine' => 118,
        'endLine' => 125,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertArrayIsIdenticalToArrayOnlyConsideringListOfKeys' => 
      array (
        'name' => 'assertArrayIsIdenticalToArrayOnlyConsideringListOfKeys',
        'parameters' => 
        array (
          'expected' => 
          array (
            'name' => 'expected',
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
            'startLine' => 137,
            'endLine' => 137,
            'startColumn' => 89,
            'endColumn' => 103,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'actual' => 
          array (
            'name' => 'actual',
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
            'startLine' => 137,
            'endLine' => 137,
            'startColumn' => 106,
            'endColumn' => 118,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'keysToBeConsidered' => 
          array (
            'name' => 'keysToBeConsidered',
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
            'startLine' => 137,
            'endLine' => 137,
            'startColumn' => 121,
            'endColumn' => 145,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 137,
                'endLine' => 137,
                'startTokenPos' => 620,
                'startFilePos' => 5051,
                'endTokenPos' => 620,
                'endFilePos' => 5052,
              ),
            ),
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
            'startLine' => 137,
            'endLine' => 137,
            'startColumn' => 148,
            'endColumn' => 167,
            'parameterIndex' => 3,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that two arrays are identical while only considering a list of keys.
 *
 * @param array<mixed>              $expected
 * @param array<mixed>              $actual
 * @param non-empty-list<array-key> $keysToBeConsidered
 *
 * @throws Exception
 * @throws ExpectationFailedException
 */',
        'startLine' => 137,
        'endLine' => 144,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertArrayIsIdenticalToArrayIgnoringListOfKeys' => 
      array (
        'name' => 'assertArrayIsIdenticalToArrayIgnoringListOfKeys',
        'parameters' => 
        array (
          'expected' => 
          array (
            'name' => 'expected',
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
            'startLine' => 156,
            'endLine' => 156,
            'startColumn' => 82,
            'endColumn' => 96,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'actual' => 
          array (
            'name' => 'actual',
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
            'startLine' => 156,
            'endLine' => 156,
            'startColumn' => 99,
            'endColumn' => 111,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'keysToBeIgnored' => 
          array (
            'name' => 'keysToBeIgnored',
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
            'startLine' => 156,
            'endLine' => 156,
            'startColumn' => 114,
            'endColumn' => 135,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 156,
                'endLine' => 156,
                'startTokenPos' => 716,
                'startFilePos' => 5861,
                'endTokenPos' => 716,
                'endFilePos' => 5862,
              ),
            ),
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
            'startColumn' => 138,
            'endColumn' => 157,
            'parameterIndex' => 3,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that two arrays are equal while ignoring a list of keys.
 *
 * @param array<mixed>              $expected
 * @param array<mixed>              $actual
 * @param non-empty-list<array-key> $keysToBeIgnored
 *
 * @throws Exception
 * @throws ExpectationFailedException
 */',
        'startLine' => 156,
        'endLine' => 163,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertArrayHasKey' => 
      array (
        'name' => 'assertArrayHasKey',
        'parameters' => 
        array (
          'key' => 
          array (
            'name' => 'key',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'mixed',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 173,
            'endLine' => 173,
            'startColumn' => 52,
            'endColumn' => 61,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'array' => 
          array (
            'name' => 'array',
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
                      'name' => 'array',
                      'isIdentifier' => true,
                    ),
                  ),
                  1 => 
                  array (
                    'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                    'data' => 
                    array (
                      'name' => 'ArrayAccess',
                      'isIdentifier' => false,
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
            'startLine' => 173,
            'endLine' => 173,
            'startColumn' => 64,
            'endColumn' => 87,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 173,
                'endLine' => 173,
                'startTokenPos' => 799,
                'startFilePos' => 6365,
                'endTokenPos' => 799,
                'endFilePos' => 6366,
              ),
            ),
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
            'startLine' => 173,
            'endLine' => 173,
            'startColumn' => 90,
            'endColumn' => 109,
            'parameterIndex' => 2,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that an array has a specified key.
 *
 * @param array<mixed>|ArrayAccess<array-key, mixed> $array
 *
 * @throws Exception
 * @throws ExpectationFailedException
 */',
        'startLine' => 173,
        'endLine' => 178,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertArrayNotHasKey' => 
      array (
        'name' => 'assertArrayNotHasKey',
        'parameters' => 
        array (
          'key' => 
          array (
            'name' => 'key',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'mixed',
                'isIdentifier' => true,
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
            'startColumn' => 55,
            'endColumn' => 64,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'array' => 
          array (
            'name' => 'array',
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
                      'name' => 'array',
                      'isIdentifier' => true,
                    ),
                  ),
                  1 => 
                  array (
                    'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                    'data' => 
                    array (
                      'name' => 'ArrayAccess',
                      'isIdentifier' => false,
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
            'startLine' => 188,
            'endLine' => 188,
            'startColumn' => 67,
            'endColumn' => 90,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 188,
                'endLine' => 188,
                'startTokenPos' => 865,
                'startFilePos' => 6822,
                'endTokenPos' => 865,
                'endFilePos' => 6823,
              ),
            ),
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
            'startLine' => 188,
            'endLine' => 188,
            'startColumn' => 93,
            'endColumn' => 112,
            'parameterIndex' => 2,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that an array does not have a specified key.
 *
 * @param array<mixed>|ArrayAccess<array-key, mixed> $array
 *
 * @throws Exception
 * @throws ExpectationFailedException
 */',
        'startLine' => 188,
        'endLine' => 195,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertIsList' => 
      array (
        'name' => 'assertIsList',
        'parameters' => 
        array (
          'array' => 
          array (
            'name' => 'array',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'mixed',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 202,
            'endLine' => 202,
            'startColumn' => 47,
            'endColumn' => 58,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 202,
                'endLine' => 202,
                'startTokenPos' => 932,
                'startFilePos' => 7172,
                'endTokenPos' => 932,
                'endFilePos' => 7173,
              ),
            ),
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
            'startLine' => 202,
            'endLine' => 202,
            'startColumn' => 61,
            'endColumn' => 80,
            'parameterIndex' => 1,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * @phpstan-assert list<mixed> $array
 *
 * @throws ExpectationFailedException
 */',
        'startLine' => 202,
        'endLine' => 209,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertContains' => 
      array (
        'name' => 'assertContains',
        'parameters' => 
        array (
          'needle' => 
          array (
            'name' => 'needle',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'mixed',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 219,
            'endLine' => 219,
            'startColumn' => 49,
            'endColumn' => 61,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'haystack' => 
          array (
            'name' => 'haystack',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'iterable',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 219,
            'endLine' => 219,
            'startColumn' => 64,
            'endColumn' => 81,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 219,
                'endLine' => 219,
                'startTokenPos' => 989,
                'startFilePos' => 7586,
                'endTokenPos' => 989,
                'endFilePos' => 7587,
              ),
            ),
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
            'startLine' => 219,
            'endLine' => 219,
            'startColumn' => 84,
            'endColumn' => 103,
            'parameterIndex' => 2,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that a haystack contains a needle.
 *
 * @param iterable<mixed> $haystack
 *
 * @throws Exception
 * @throws ExpectationFailedException
 */',
        'startLine' => 219,
        'endLine' => 224,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertContainsEquals' => 
      array (
        'name' => 'assertContainsEquals',
        'parameters' => 
        array (
          'needle' => 
          array (
            'name' => 'needle',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'mixed',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 231,
            'endLine' => 231,
            'startColumn' => 55,
            'endColumn' => 67,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'haystack' => 
          array (
            'name' => 'haystack',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'iterable',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 231,
            'endLine' => 231,
            'startColumn' => 70,
            'endColumn' => 87,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 231,
                'endLine' => 231,
                'startTokenPos' => 1053,
                'startFilePos' => 7947,
                'endTokenPos' => 1053,
                'endFilePos' => 7948,
              ),
            ),
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
            'startLine' => 231,
            'endLine' => 231,
            'startColumn' => 90,
            'endColumn' => 109,
            'parameterIndex' => 2,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * @param iterable<mixed> $haystack
 *
 * @throws ExpectationFailedException
 */',
        'startLine' => 231,
        'endLine' => 236,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertNotContains' => 
      array (
        'name' => 'assertNotContains',
        'parameters' => 
        array (
          'needle' => 
          array (
            'name' => 'needle',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'mixed',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 246,
            'endLine' => 246,
            'startColumn' => 52,
            'endColumn' => 64,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'haystack' => 
          array (
            'name' => 'haystack',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'iterable',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 246,
            'endLine' => 246,
            'startColumn' => 67,
            'endColumn' => 84,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 246,
                'endLine' => 246,
                'startTokenPos' => 1117,
                'startFilePos' => 8391,
                'endTokenPos' => 1117,
                'endFilePos' => 8392,
              ),
            ),
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
            'startLine' => 246,
            'endLine' => 246,
            'startColumn' => 87,
            'endColumn' => 106,
            'parameterIndex' => 2,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that a haystack does not contain a needle.
 *
 * @param iterable<mixed> $haystack
 *
 * @throws Exception
 * @throws ExpectationFailedException
 */',
        'startLine' => 246,
        'endLine' => 253,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertNotContainsEquals' => 
      array (
        'name' => 'assertNotContainsEquals',
        'parameters' => 
        array (
          'needle' => 
          array (
            'name' => 'needle',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'mixed',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 260,
            'endLine' => 260,
            'startColumn' => 58,
            'endColumn' => 70,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'haystack' => 
          array (
            'name' => 'haystack',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'iterable',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 260,
            'endLine' => 260,
            'startColumn' => 73,
            'endColumn' => 90,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 260,
                'endLine' => 260,
                'startTokenPos' => 1189,
                'startFilePos' => 8794,
                'endTokenPos' => 1189,
                'endFilePos' => 8795,
              ),
            ),
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
            'startLine' => 260,
            'endLine' => 260,
            'startColumn' => 93,
            'endColumn' => 112,
            'parameterIndex' => 2,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * @param iterable<mixed> $haystack
 *
 * @throws ExpectationFailedException
 */',
        'startLine' => 260,
        'endLine' => 265,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertContainsOnly' => 
      array (
        'name' => 'assertContainsOnly',
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
            'startLine' => 278,
            'endLine' => 278,
            'startColumn' => 53,
            'endColumn' => 64,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'haystack' => 
          array (
            'name' => 'haystack',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'iterable',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 278,
            'endLine' => 278,
            'startColumn' => 67,
            'endColumn' => 84,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'isNativeType' => 
          array (
            'name' => 'isNativeType',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 278,
                'endLine' => 278,
                'startTokenPos' => 1259,
                'startFilePos' => 9680,
                'endTokenPos' => 1259,
                'endFilePos' => 9683,
              ),
            ),
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
                      'name' => 'bool',
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
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 278,
            'endLine' => 278,
            'startColumn' => 87,
            'endColumn' => 112,
            'parameterIndex' => 2,
            'isOptional' => true,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 278,
                'endLine' => 278,
                'startTokenPos' => 1268,
                'startFilePos' => 9704,
                'endTokenPos' => 1268,
                'endFilePos' => 9705,
              ),
            ),
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
            'startLine' => 278,
            'endLine' => 278,
            'startColumn' => 115,
            'endColumn' => 134,
            'parameterIndex' => 3,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that a haystack contains only values of a given type.
 *
 * @param \'array\'|\'bool\'|\'boolean\'|\'callable\'|\'double\'|\'float\'|\'int\'|\'integer\'|\'iterable\'|\'null\'|\'numeric\'|\'object\'|\'real\'|\'resource (closed)\'|\'resource\'|\'scalar\'|\'string\' $type
 * @param iterable<mixed>                                                                                                                                                   $haystack
 *
 * @throws Exception
 * @throws ExpectationFailedException
 *
 * @deprecated https://github.com/sebastianbergmann/phpunit/issues/6055
 */',
        'startLine' => 278,
        'endLine' => 292,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertContainsOnlyArray' => 
      array (
        'name' => 'assertContainsOnlyArray',
        'parameters' => 
        array (
          'haystack' => 
          array (
            'name' => 'haystack',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'iterable',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 303,
            'endLine' => 303,
            'startColumn' => 58,
            'endColumn' => 75,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 303,
                'endLine' => 303,
                'startTokenPos' => 1355,
                'startFilePos' => 10360,
                'endTokenPos' => 1355,
                'endFilePos' => 10361,
              ),
            ),
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
            'startLine' => 303,
            'endLine' => 303,
            'startColumn' => 78,
            'endColumn' => 97,
            'parameterIndex' => 1,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that a haystack contains only values of type array.
 *
 * @phpstan-assert iterable<array<mixed>> $haystack
 *
 * @param iterable<mixed> $haystack
 *
 * @throws ExpectationFailedException
 */',
        'startLine' => 303,
        'endLine' => 312,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertContainsOnlyBool' => 
      array (
        'name' => 'assertContainsOnlyBool',
        'parameters' => 
        array (
          'haystack' => 
          array (
            'name' => 'haystack',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'iterable',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 323,
            'endLine' => 323,
            'startColumn' => 57,
            'endColumn' => 74,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 323,
                'endLine' => 323,
                'startTokenPos' => 1417,
                'startFilePos' => 10890,
                'endTokenPos' => 1417,
                'endFilePos' => 10891,
              ),
            ),
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
            'startLine' => 323,
            'endLine' => 323,
            'startColumn' => 77,
            'endColumn' => 96,
            'parameterIndex' => 1,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that a haystack contains only values of type bool.
 *
 * @phpstan-assert iterable<bool> $haystack
 *
 * @param iterable<mixed> $haystack
 *
 * @throws ExpectationFailedException
 */',
        'startLine' => 323,
        'endLine' => 332,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertContainsOnlyCallable' => 
      array (
        'name' => 'assertContainsOnlyCallable',
        'parameters' => 
        array (
          'haystack' => 
          array (
            'name' => 'haystack',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'iterable',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 343,
            'endLine' => 343,
            'startColumn' => 61,
            'endColumn' => 78,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 343,
                'endLine' => 343,
                'startTokenPos' => 1479,
                'startFilePos' => 11431,
                'endTokenPos' => 1479,
                'endFilePos' => 11432,
              ),
            ),
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
            'startLine' => 343,
            'endLine' => 343,
            'startColumn' => 81,
            'endColumn' => 100,
            'parameterIndex' => 1,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that a haystack contains only values of type callable.
 *
 * @phpstan-assert iterable<callable> $haystack
 *
 * @param iterable<mixed> $haystack
 *
 * @throws ExpectationFailedException
 */',
        'startLine' => 343,
        'endLine' => 352,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertContainsOnlyFloat' => 
      array (
        'name' => 'assertContainsOnlyFloat',
        'parameters' => 
        array (
          'haystack' => 
          array (
            'name' => 'haystack',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'iterable',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 363,
            'endLine' => 363,
            'startColumn' => 58,
            'endColumn' => 75,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 363,
                'endLine' => 363,
                'startTokenPos' => 1541,
                'startFilePos' => 11967,
                'endTokenPos' => 1541,
                'endFilePos' => 11968,
              ),
            ),
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
            'startLine' => 363,
            'endLine' => 363,
            'startColumn' => 78,
            'endColumn' => 97,
            'parameterIndex' => 1,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that a haystack contains only values of type float.
 *
 * @phpstan-assert iterable<float> $haystack
 *
 * @param iterable<mixed> $haystack
 *
 * @throws ExpectationFailedException
 */',
        'startLine' => 363,
        'endLine' => 372,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertContainsOnlyInt' => 
      array (
        'name' => 'assertContainsOnlyInt',
        'parameters' => 
        array (
          'haystack' => 
          array (
            'name' => 'haystack',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'iterable',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 383,
            'endLine' => 383,
            'startColumn' => 56,
            'endColumn' => 73,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 383,
                'endLine' => 383,
                'startTokenPos' => 1603,
                'startFilePos' => 12494,
                'endTokenPos' => 1603,
                'endFilePos' => 12495,
              ),
            ),
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
            'startLine' => 383,
            'endLine' => 383,
            'startColumn' => 76,
            'endColumn' => 95,
            'parameterIndex' => 1,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that a haystack contains only values of type int.
 *
 * @phpstan-assert iterable<int> $haystack
 *
 * @param iterable<mixed> $haystack
 *
 * @throws ExpectationFailedException
 */',
        'startLine' => 383,
        'endLine' => 392,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertContainsOnlyIterable' => 
      array (
        'name' => 'assertContainsOnlyIterable',
        'parameters' => 
        array (
          'haystack' => 
          array (
            'name' => 'haystack',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'iterable',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 403,
            'endLine' => 403,
            'startColumn' => 61,
            'endColumn' => 78,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 403,
                'endLine' => 403,
                'startTokenPos' => 1665,
                'startFilePos' => 13041,
                'endTokenPos' => 1665,
                'endFilePos' => 13042,
              ),
            ),
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
            'startLine' => 403,
            'endLine' => 403,
            'startColumn' => 81,
            'endColumn' => 100,
            'parameterIndex' => 1,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that a haystack contains only values of type iterable.
 *
 * @phpstan-assert iterable<iterable<mixed>> $haystack
 *
 * @param iterable<mixed> $haystack
 *
 * @throws ExpectationFailedException
 */',
        'startLine' => 403,
        'endLine' => 412,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertContainsOnlyNull' => 
      array (
        'name' => 'assertContainsOnlyNull',
        'parameters' => 
        array (
          'haystack' => 
          array (
            'name' => 'haystack',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'iterable',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 423,
            'endLine' => 423,
            'startColumn' => 57,
            'endColumn' => 74,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 423,
                'endLine' => 423,
                'startTokenPos' => 1727,
                'startFilePos' => 13574,
                'endTokenPos' => 1727,
                'endFilePos' => 13575,
              ),
            ),
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
            'startLine' => 423,
            'endLine' => 423,
            'startColumn' => 77,
            'endColumn' => 96,
            'parameterIndex' => 1,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that a haystack contains only values of type null.
 *
 * @phpstan-assert iterable<null> $haystack
 *
 * @param iterable<mixed> $haystack
 *
 * @throws ExpectationFailedException
 */',
        'startLine' => 423,
        'endLine' => 432,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertContainsOnlyNumeric' => 
      array (
        'name' => 'assertContainsOnlyNumeric',
        'parameters' => 
        array (
          'haystack' => 
          array (
            'name' => 'haystack',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'iterable',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 443,
            'endLine' => 443,
            'startColumn' => 60,
            'endColumn' => 77,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 443,
                'endLine' => 443,
                'startTokenPos' => 1789,
                'startFilePos' => 14112,
                'endTokenPos' => 1789,
                'endFilePos' => 14113,
              ),
            ),
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
            'startLine' => 443,
            'endLine' => 443,
            'startColumn' => 80,
            'endColumn' => 99,
            'parameterIndex' => 1,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that a haystack contains only values of type numeric.
 *
 * @phpstan-assert iterable<numeric> $haystack
 *
 * @param iterable<mixed> $haystack
 *
 * @throws ExpectationFailedException
 */',
        'startLine' => 443,
        'endLine' => 452,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertContainsOnlyObject' => 
      array (
        'name' => 'assertContainsOnlyObject',
        'parameters' => 
        array (
          'haystack' => 
          array (
            'name' => 'haystack',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'iterable',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 463,
            'endLine' => 463,
            'startColumn' => 59,
            'endColumn' => 76,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 463,
                'endLine' => 463,
                'startTokenPos' => 1851,
                'startFilePos' => 14650,
                'endTokenPos' => 1851,
                'endFilePos' => 14651,
              ),
            ),
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
            'startLine' => 463,
            'endLine' => 463,
            'startColumn' => 79,
            'endColumn' => 98,
            'parameterIndex' => 1,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that a haystack contains only values of type object.
 *
 * @phpstan-assert iterable<object> $haystack
 *
 * @param iterable<mixed> $haystack
 *
 * @throws ExpectationFailedException
 */',
        'startLine' => 463,
        'endLine' => 472,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertContainsOnlyResource' => 
      array (
        'name' => 'assertContainsOnlyResource',
        'parameters' => 
        array (
          'haystack' => 
          array (
            'name' => 'haystack',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'iterable',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 483,
            'endLine' => 483,
            'startColumn' => 61,
            'endColumn' => 78,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 483,
                'endLine' => 483,
                'startTokenPos' => 1913,
                'startFilePos' => 15193,
                'endTokenPos' => 1913,
                'endFilePos' => 15194,
              ),
            ),
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
            'startLine' => 483,
            'endLine' => 483,
            'startColumn' => 81,
            'endColumn' => 100,
            'parameterIndex' => 1,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that a haystack contains only values of type resource.
 *
 * @phpstan-assert iterable<resource> $haystack
 *
 * @param iterable<mixed> $haystack
 *
 * @throws ExpectationFailedException
 */',
        'startLine' => 483,
        'endLine' => 492,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertContainsOnlyClosedResource' => 
      array (
        'name' => 'assertContainsOnlyClosedResource',
        'parameters' => 
        array (
          'haystack' => 
          array (
            'name' => 'haystack',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'iterable',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 503,
            'endLine' => 503,
            'startColumn' => 67,
            'endColumn' => 84,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 503,
                'endLine' => 503,
                'startTokenPos' => 1975,
                'startFilePos' => 15751,
                'endTokenPos' => 1975,
                'endFilePos' => 15752,
              ),
            ),
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
            'startLine' => 503,
            'endLine' => 503,
            'startColumn' => 87,
            'endColumn' => 106,
            'parameterIndex' => 1,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that a haystack contains only values of type closed resource.
 *
 * @phpstan-assert iterable<resource> $haystack
 *
 * @param iterable<mixed> $haystack
 *
 * @throws ExpectationFailedException
 */',
        'startLine' => 503,
        'endLine' => 512,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertContainsOnlyScalar' => 
      array (
        'name' => 'assertContainsOnlyScalar',
        'parameters' => 
        array (
          'haystack' => 
          array (
            'name' => 'haystack',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'iterable',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 523,
            'endLine' => 523,
            'startColumn' => 59,
            'endColumn' => 76,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 523,
                'endLine' => 523,
                'startTokenPos' => 2037,
                'startFilePos' => 16296,
                'endTokenPos' => 2037,
                'endFilePos' => 16297,
              ),
            ),
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
            'startLine' => 523,
            'endLine' => 523,
            'startColumn' => 79,
            'endColumn' => 98,
            'parameterIndex' => 1,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that a haystack contains only values of type scalar.
 *
 * @phpstan-assert iterable<scalar> $haystack
 *
 * @param iterable<mixed> $haystack
 *
 * @throws ExpectationFailedException
 */',
        'startLine' => 523,
        'endLine' => 532,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertContainsOnlyString' => 
      array (
        'name' => 'assertContainsOnlyString',
        'parameters' => 
        array (
          'haystack' => 
          array (
            'name' => 'haystack',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'iterable',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 543,
            'endLine' => 543,
            'startColumn' => 59,
            'endColumn' => 76,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 543,
                'endLine' => 543,
                'startTokenPos' => 2099,
                'startFilePos' => 16833,
                'endTokenPos' => 2099,
                'endFilePos' => 16834,
              ),
            ),
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
            'startLine' => 543,
            'endLine' => 543,
            'startColumn' => 79,
            'endColumn' => 98,
            'parameterIndex' => 1,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that a haystack contains only values of type string.
 *
 * @phpstan-assert iterable<string> $haystack
 *
 * @param iterable<mixed> $haystack
 *
 * @throws ExpectationFailedException
 */',
        'startLine' => 543,
        'endLine' => 552,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertContainsOnlyInstancesOf' => 
      array (
        'name' => 'assertContainsOnlyInstancesOf',
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
            'startLine' => 567,
            'endLine' => 567,
            'startColumn' => 64,
            'endColumn' => 80,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'haystack' => 
          array (
            'name' => 'haystack',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'iterable',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 567,
            'endLine' => 567,
            'startColumn' => 83,
            'endColumn' => 100,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 567,
                'endLine' => 567,
                'startTokenPos' => 2166,
                'startFilePos' => 17508,
                'endTokenPos' => 2166,
                'endFilePos' => 17509,
              ),
            ),
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
            'startLine' => 567,
            'endLine' => 567,
            'startColumn' => 103,
            'endColumn' => 122,
            'parameterIndex' => 2,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that a haystack contains only instances of a specified interface or class name.
 *
 * @template T
 *
 * @phpstan-assert iterable<T> $haystack
 *
 * @param class-string<T> $className
 * @param iterable<mixed> $haystack
 *
 * @throws Exception
 * @throws ExpectationFailedException
 */',
        'startLine' => 567,
        'endLine' => 577,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertNotContainsOnly' => 
      array (
        'name' => 'assertNotContainsOnly',
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
            'startLine' => 590,
            'endLine' => 590,
            'startColumn' => 56,
            'endColumn' => 67,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'haystack' => 
          array (
            'name' => 'haystack',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'iterable',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 590,
            'endLine' => 590,
            'startColumn' => 70,
            'endColumn' => 87,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'isNativeType' => 
          array (
            'name' => 'isNativeType',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 590,
                'endLine' => 590,
                'startTokenPos' => 2233,
                'startFilePos' => 18456,
                'endTokenPos' => 2233,
                'endFilePos' => 18459,
              ),
            ),
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
                      'name' => 'bool',
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
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 590,
            'endLine' => 590,
            'startColumn' => 90,
            'endColumn' => 115,
            'parameterIndex' => 2,
            'isOptional' => true,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 590,
                'endLine' => 590,
                'startTokenPos' => 2242,
                'startFilePos' => 18480,
                'endTokenPos' => 2242,
                'endFilePos' => 18481,
              ),
            ),
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
            'startLine' => 590,
            'endLine' => 590,
            'startColumn' => 118,
            'endColumn' => 137,
            'parameterIndex' => 3,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that a haystack does not contain only values of a given type.
 *
 * @param \'array\'|\'bool\'|\'boolean\'|\'callable\'|\'double\'|\'float\'|\'int\'|\'integer\'|\'iterable\'|\'null\'|\'numeric\'|\'object\'|\'real\'|\'resource (closed)\'|\'resource\'|\'scalar\'|\'string\' $type
 * @param iterable<mixed>                                                                                                                                                   $haystack
 *
 * @throws Exception
 * @throws ExpectationFailedException
 *
 * @deprecated https://github.com/sebastianbergmann/phpunit/issues/6055
 */',
        'startLine' => 590,
        'endLine' => 606,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertContainsNotOnlyArray' => 
      array (
        'name' => 'assertContainsNotOnlyArray',
        'parameters' => 
        array (
          'haystack' => 
          array (
            'name' => 'haystack',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'iterable',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 615,
            'endLine' => 615,
            'startColumn' => 61,
            'endColumn' => 78,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 615,
                'endLine' => 615,
                'startTokenPos' => 2337,
                'startFilePos' => 19143,
                'endTokenPos' => 2337,
                'endFilePos' => 19144,
              ),
            ),
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
            'startLine' => 615,
            'endLine' => 615,
            'startColumn' => 81,
            'endColumn' => 100,
            'parameterIndex' => 1,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that a haystack does not contain only values of type array.
 *
 * @param iterable<mixed> $haystack
 *
 * @throws ExpectationFailedException
 */',
        'startLine' => 615,
        'endLine' => 626,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertContainsNotOnlyBool' => 
      array (
        'name' => 'assertContainsNotOnlyBool',
        'parameters' => 
        array (
          'haystack' => 
          array (
            'name' => 'haystack',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'iterable',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 635,
            'endLine' => 635,
            'startColumn' => 60,
            'endColumn' => 77,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 635,
                'endLine' => 635,
                'startTokenPos' => 2407,
                'startFilePos' => 19684,
                'endTokenPos' => 2407,
                'endFilePos' => 19685,
              ),
            ),
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
            'startLine' => 635,
            'endLine' => 635,
            'startColumn' => 80,
            'endColumn' => 99,
            'parameterIndex' => 1,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that a haystack does not contain only values of type bool.
 *
 * @param iterable<mixed> $haystack
 *
 * @throws ExpectationFailedException
 */',
        'startLine' => 635,
        'endLine' => 646,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertContainsNotOnlyCallable' => 
      array (
        'name' => 'assertContainsNotOnlyCallable',
        'parameters' => 
        array (
          'haystack' => 
          array (
            'name' => 'haystack',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'iterable',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 655,
            'endLine' => 655,
            'startColumn' => 64,
            'endColumn' => 81,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 655,
                'endLine' => 655,
                'startTokenPos' => 2477,
                'startFilePos' => 20232,
                'endTokenPos' => 2477,
                'endFilePos' => 20233,
              ),
            ),
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
            'startLine' => 655,
            'endLine' => 655,
            'startColumn' => 84,
            'endColumn' => 103,
            'parameterIndex' => 1,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that a haystack does not contain only values of type callable.
 *
 * @param iterable<mixed> $haystack
 *
 * @throws ExpectationFailedException
 */',
        'startLine' => 655,
        'endLine' => 666,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertContainsNotOnlyFloat' => 
      array (
        'name' => 'assertContainsNotOnlyFloat',
        'parameters' => 
        array (
          'haystack' => 
          array (
            'name' => 'haystack',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'iterable',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 675,
            'endLine' => 675,
            'startColumn' => 61,
            'endColumn' => 78,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 675,
                'endLine' => 675,
                'startTokenPos' => 2547,
                'startFilePos' => 20778,
                'endTokenPos' => 2547,
                'endFilePos' => 20779,
              ),
            ),
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
            'startLine' => 675,
            'endLine' => 675,
            'startColumn' => 81,
            'endColumn' => 100,
            'parameterIndex' => 1,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that a haystack does not contain only values of type float.
 *
 * @param iterable<mixed> $haystack
 *
 * @throws ExpectationFailedException
 */',
        'startLine' => 675,
        'endLine' => 686,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertContainsNotOnlyInt' => 
      array (
        'name' => 'assertContainsNotOnlyInt',
        'parameters' => 
        array (
          'haystack' => 
          array (
            'name' => 'haystack',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'iterable',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 695,
            'endLine' => 695,
            'startColumn' => 59,
            'endColumn' => 76,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 695,
                'endLine' => 695,
                'startTokenPos' => 2617,
                'startFilePos' => 21317,
                'endTokenPos' => 2617,
                'endFilePos' => 21318,
              ),
            ),
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
            'startLine' => 695,
            'endLine' => 695,
            'startColumn' => 79,
            'endColumn' => 98,
            'parameterIndex' => 1,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that a haystack does not contain only values of type int.
 *
 * @param iterable<mixed> $haystack
 *
 * @throws ExpectationFailedException
 */',
        'startLine' => 695,
        'endLine' => 706,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertContainsNotOnlyIterable' => 
      array (
        'name' => 'assertContainsNotOnlyIterable',
        'parameters' => 
        array (
          'haystack' => 
          array (
            'name' => 'haystack',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'iterable',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 715,
            'endLine' => 715,
            'startColumn' => 64,
            'endColumn' => 81,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 715,
                'endLine' => 715,
                'startTokenPos' => 2687,
                'startFilePos' => 21864,
                'endTokenPos' => 2687,
                'endFilePos' => 21865,
              ),
            ),
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
            'startLine' => 715,
            'endLine' => 715,
            'startColumn' => 84,
            'endColumn' => 103,
            'parameterIndex' => 1,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that a haystack does not contain only values of type iterable.
 *
 * @param iterable<mixed> $haystack
 *
 * @throws ExpectationFailedException
 */',
        'startLine' => 715,
        'endLine' => 726,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertContainsNotOnlyNull' => 
      array (
        'name' => 'assertContainsNotOnlyNull',
        'parameters' => 
        array (
          'haystack' => 
          array (
            'name' => 'haystack',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'iterable',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 735,
            'endLine' => 735,
            'startColumn' => 60,
            'endColumn' => 77,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 735,
                'endLine' => 735,
                'startTokenPos' => 2757,
                'startFilePos' => 22408,
                'endTokenPos' => 2757,
                'endFilePos' => 22409,
              ),
            ),
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
            'startLine' => 735,
            'endLine' => 735,
            'startColumn' => 80,
            'endColumn' => 99,
            'parameterIndex' => 1,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that a haystack does not contain only values of type null.
 *
 * @param iterable<mixed> $haystack
 *
 * @throws ExpectationFailedException
 */',
        'startLine' => 735,
        'endLine' => 746,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertContainsNotOnlyNumeric' => 
      array (
        'name' => 'assertContainsNotOnlyNumeric',
        'parameters' => 
        array (
          'haystack' => 
          array (
            'name' => 'haystack',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'iterable',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 755,
            'endLine' => 755,
            'startColumn' => 63,
            'endColumn' => 80,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 755,
                'endLine' => 755,
                'startTokenPos' => 2827,
                'startFilePos' => 22954,
                'endTokenPos' => 2827,
                'endFilePos' => 22955,
              ),
            ),
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
            'startLine' => 755,
            'endLine' => 755,
            'startColumn' => 83,
            'endColumn' => 102,
            'parameterIndex' => 1,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that a haystack does not contain only values of type numeric.
 *
 * @param iterable<mixed> $haystack
 *
 * @throws ExpectationFailedException
 */',
        'startLine' => 755,
        'endLine' => 766,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertContainsNotOnlyObject' => 
      array (
        'name' => 'assertContainsNotOnlyObject',
        'parameters' => 
        array (
          'haystack' => 
          array (
            'name' => 'haystack',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'iterable',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 775,
            'endLine' => 775,
            'startColumn' => 62,
            'endColumn' => 79,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 775,
                'endLine' => 775,
                'startTokenPos' => 2897,
                'startFilePos' => 23501,
                'endTokenPos' => 2897,
                'endFilePos' => 23502,
              ),
            ),
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
            'startLine' => 775,
            'endLine' => 775,
            'startColumn' => 82,
            'endColumn' => 101,
            'parameterIndex' => 1,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that a haystack does not contain only values of type object.
 *
 * @param iterable<mixed> $haystack
 *
 * @throws ExpectationFailedException
 */',
        'startLine' => 775,
        'endLine' => 786,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertContainsNotOnlyResource' => 
      array (
        'name' => 'assertContainsNotOnlyResource',
        'parameters' => 
        array (
          'haystack' => 
          array (
            'name' => 'haystack',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'iterable',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 795,
            'endLine' => 795,
            'startColumn' => 64,
            'endColumn' => 81,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 795,
                'endLine' => 795,
                'startTokenPos' => 2967,
                'startFilePos' => 24051,
                'endTokenPos' => 2967,
                'endFilePos' => 24052,
              ),
            ),
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
            'startLine' => 795,
            'endLine' => 795,
            'startColumn' => 84,
            'endColumn' => 103,
            'parameterIndex' => 1,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that a haystack does not contain only values of type resource.
 *
 * @param iterable<mixed> $haystack
 *
 * @throws ExpectationFailedException
 */',
        'startLine' => 795,
        'endLine' => 806,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertContainsNotOnlyClosedResource' => 
      array (
        'name' => 'assertContainsNotOnlyClosedResource',
        'parameters' => 
        array (
          'haystack' => 
          array (
            'name' => 'haystack',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'iterable',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 815,
            'endLine' => 815,
            'startColumn' => 70,
            'endColumn' => 87,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 815,
                'endLine' => 815,
                'startTokenPos' => 3037,
                'startFilePos' => 24616,
                'endTokenPos' => 3037,
                'endFilePos' => 24617,
              ),
            ),
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
            'startLine' => 815,
            'endLine' => 815,
            'startColumn' => 90,
            'endColumn' => 109,
            'parameterIndex' => 1,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that a haystack does not contain only values of type closed resource.
 *
 * @param iterable<mixed> $haystack
 *
 * @throws ExpectationFailedException
 */',
        'startLine' => 815,
        'endLine' => 826,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertContainsNotOnlyScalar' => 
      array (
        'name' => 'assertContainsNotOnlyScalar',
        'parameters' => 
        array (
          'haystack' => 
          array (
            'name' => 'haystack',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'iterable',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 835,
            'endLine' => 835,
            'startColumn' => 62,
            'endColumn' => 79,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 835,
                'endLine' => 835,
                'startTokenPos' => 3107,
                'startFilePos' => 25170,
                'endTokenPos' => 3107,
                'endFilePos' => 25171,
              ),
            ),
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
            'startLine' => 835,
            'endLine' => 835,
            'startColumn' => 82,
            'endColumn' => 101,
            'parameterIndex' => 1,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that a haystack does not contain only values of type scalar.
 *
 * @param iterable<mixed> $haystack
 *
 * @throws ExpectationFailedException
 */',
        'startLine' => 835,
        'endLine' => 846,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertContainsNotOnlyString' => 
      array (
        'name' => 'assertContainsNotOnlyString',
        'parameters' => 
        array (
          'haystack' => 
          array (
            'name' => 'haystack',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'iterable',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 855,
            'endLine' => 855,
            'startColumn' => 62,
            'endColumn' => 79,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 855,
                'endLine' => 855,
                'startTokenPos' => 3177,
                'startFilePos' => 25716,
                'endTokenPos' => 3177,
                'endFilePos' => 25717,
              ),
            ),
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
            'startLine' => 855,
            'endLine' => 855,
            'startColumn' => 82,
            'endColumn' => 101,
            'parameterIndex' => 1,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that a haystack does not contain only values of type string.
 *
 * @param iterable<mixed> $haystack
 *
 * @throws ExpectationFailedException
 */',
        'startLine' => 855,
        'endLine' => 866,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertContainsNotOnlyInstancesOf' => 
      array (
        'name' => 'assertContainsNotOnlyInstancesOf',
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
            'startLine' => 877,
            'endLine' => 877,
            'startColumn' => 67,
            'endColumn' => 83,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'haystack' => 
          array (
            'name' => 'haystack',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'iterable',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 877,
            'endLine' => 877,
            'startColumn' => 86,
            'endColumn' => 103,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 877,
                'endLine' => 877,
                'startTokenPos' => 3252,
                'startFilePos' => 26379,
                'endTokenPos' => 3252,
                'endFilePos' => 26380,
              ),
            ),
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
            'startLine' => 877,
            'endLine' => 877,
            'startColumn' => 106,
            'endColumn' => 125,
            'parameterIndex' => 2,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that a haystack does not contain only instances of a specified interface or class name.
 *
 * @param class-string    $className
 * @param iterable<mixed> $haystack
 *
 * @throws Exception
 * @throws ExpectationFailedException
 */',
        'startLine' => 877,
        'endLine' => 889,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertCount' => 
      array (
        'name' => 'assertCount',
        'parameters' => 
        array (
          'expectedCount' => 
          array (
            'name' => 'expectedCount',
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
            'startLine' => 900,
            'endLine' => 900,
            'startColumn' => 46,
            'endColumn' => 63,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'haystack' => 
          array (
            'name' => 'haystack',
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
                      'name' => 'Countable',
                      'isIdentifier' => false,
                    ),
                  ),
                  1 => 
                  array (
                    'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                    'data' => 
                    array (
                      'name' => 'iterable',
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
            'startLine' => 900,
            'endLine' => 900,
            'startColumn' => 66,
            'endColumn' => 93,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 900,
                'endLine' => 900,
                'startTokenPos' => 3328,
                'startFilePos' => 27033,
                'endTokenPos' => 3328,
                'endFilePos' => 27034,
              ),
            ),
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
            'startLine' => 900,
            'endLine' => 900,
            'startColumn' => 96,
            'endColumn' => 115,
            'parameterIndex' => 2,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts the number of elements of an array, Countable or Traversable.
 *
 * @param Countable|iterable<mixed> $haystack
 *
 * @throws Exception
 * @throws ExpectationFailedException
 * @throws GeneratorNotSupportedException
 */',
        'startLine' => 900,
        'endLine' => 911,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertNotCount' => 
      array (
        'name' => 'assertNotCount',
        'parameters' => 
        array (
          'expectedCount' => 
          array (
            'name' => 'expectedCount',
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
            'startLine' => 922,
            'endLine' => 922,
            'startColumn' => 49,
            'endColumn' => 66,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'haystack' => 
          array (
            'name' => 'haystack',
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
                      'name' => 'Countable',
                      'isIdentifier' => false,
                    ),
                  ),
                  1 => 
                  array (
                    'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                    'data' => 
                    array (
                      'name' => 'iterable',
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
            'startLine' => 922,
            'endLine' => 922,
            'startColumn' => 69,
            'endColumn' => 96,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 922,
                'endLine' => 922,
                'startTokenPos' => 3414,
                'startFilePos' => 27702,
                'endTokenPos' => 3414,
                'endFilePos' => 27703,
              ),
            ),
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
            'startLine' => 922,
            'endLine' => 922,
            'startColumn' => 99,
            'endColumn' => 118,
            'parameterIndex' => 2,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts the number of elements of an array, Countable or Traversable.
 *
 * @param Countable|iterable<mixed> $haystack
 *
 * @throws Exception
 * @throws ExpectationFailedException
 * @throws GeneratorNotSupportedException
 */',
        'startLine' => 922,
        'endLine' => 933,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertEquals' => 
      array (
        'name' => 'assertEquals',
        'parameters' => 
        array (
          'expected' => 
          array (
            'name' => 'expected',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'mixed',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 940,
            'endLine' => 940,
            'startColumn' => 47,
            'endColumn' => 61,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'actual' => 
          array (
            'name' => 'actual',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'mixed',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 940,
            'endLine' => 940,
            'startColumn' => 64,
            'endColumn' => 76,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 940,
                'endLine' => 940,
                'startTokenPos' => 3510,
                'startFilePos' => 28219,
                'endTokenPos' => 3510,
                'endFilePos' => 28220,
              ),
            ),
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
            'startLine' => 940,
            'endLine' => 940,
            'startColumn' => 79,
            'endColumn' => 98,
            'parameterIndex' => 2,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that two variables are equal.
 *
 * @throws ExpectationFailedException
 */',
        'startLine' => 940,
        'endLine' => 945,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertEqualsCanonicalizing' => 
      array (
        'name' => 'assertEqualsCanonicalizing',
        'parameters' => 
        array (
          'expected' => 
          array (
            'name' => 'expected',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'mixed',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 952,
            'endLine' => 952,
            'startColumn' => 61,
            'endColumn' => 75,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'actual' => 
          array (
            'name' => 'actual',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'mixed',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 952,
            'endLine' => 952,
            'startColumn' => 78,
            'endColumn' => 90,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 952,
                'endLine' => 952,
                'startTokenPos' => 3574,
                'startFilePos' => 28584,
                'endTokenPos' => 3574,
                'endFilePos' => 28585,
              ),
            ),
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
            'startLine' => 952,
            'endLine' => 952,
            'startColumn' => 93,
            'endColumn' => 112,
            'parameterIndex' => 2,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that two variables are equal (canonicalizing).
 *
 * @throws ExpectationFailedException
 */',
        'startLine' => 952,
        'endLine' => 957,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertEqualsIgnoringCase' => 
      array (
        'name' => 'assertEqualsIgnoringCase',
        'parameters' => 
        array (
          'expected' => 
          array (
            'name' => 'expected',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'mixed',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 964,
            'endLine' => 964,
            'startColumn' => 59,
            'endColumn' => 73,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'actual' => 
          array (
            'name' => 'actual',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'mixed',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 964,
            'endLine' => 964,
            'startColumn' => 76,
            'endColumn' => 88,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 964,
                'endLine' => 964,
                'startTokenPos' => 3638,
                'startFilePos' => 28960,
                'endTokenPos' => 3638,
                'endFilePos' => 28961,
              ),
            ),
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
            'startLine' => 964,
            'endLine' => 964,
            'startColumn' => 91,
            'endColumn' => 110,
            'parameterIndex' => 2,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that two variables are equal (ignoring case).
 *
 * @throws ExpectationFailedException
 */',
        'startLine' => 964,
        'endLine' => 969,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertEqualsWithDelta' => 
      array (
        'name' => 'assertEqualsWithDelta',
        'parameters' => 
        array (
          'expected' => 
          array (
            'name' => 'expected',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'mixed',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 976,
            'endLine' => 976,
            'startColumn' => 56,
            'endColumn' => 70,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'actual' => 
          array (
            'name' => 'actual',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'mixed',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 976,
            'endLine' => 976,
            'startColumn' => 73,
            'endColumn' => 85,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'delta' => 
          array (
            'name' => 'delta',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'float',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 976,
            'endLine' => 976,
            'startColumn' => 88,
            'endColumn' => 99,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 976,
                'endLine' => 976,
                'startTokenPos' => 3707,
                'startFilePos' => 29342,
                'endTokenPos' => 3707,
                'endFilePos' => 29343,
              ),
            ),
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
            'startLine' => 976,
            'endLine' => 976,
            'startColumn' => 102,
            'endColumn' => 121,
            'parameterIndex' => 3,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that two variables are equal (with delta).
 *
 * @throws ExpectationFailedException
 */',
        'startLine' => 976,
        'endLine' => 984,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertNotEquals' => 
      array (
        'name' => 'assertNotEquals',
        'parameters' => 
        array (
          'expected' => 
          array (
            'name' => 'expected',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'mixed',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 991,
            'endLine' => 991,
            'startColumn' => 50,
            'endColumn' => 64,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'actual' => 
          array (
            'name' => 'actual',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'mixed',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 991,
            'endLine' => 991,
            'startColumn' => 67,
            'endColumn' => 79,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 991,
                'endLine' => 991,
                'startTokenPos' => 3777,
                'startFilePos' => 29735,
                'endTokenPos' => 3777,
                'endFilePos' => 29736,
              ),
            ),
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
            'startLine' => 991,
            'endLine' => 991,
            'startColumn' => 82,
            'endColumn' => 101,
            'parameterIndex' => 2,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that two variables are not equal.
 *
 * @throws ExpectationFailedException
 */',
        'startLine' => 991,
        'endLine' => 998,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertNotEqualsCanonicalizing' => 
      array (
        'name' => 'assertNotEqualsCanonicalizing',
        'parameters' => 
        array (
          'expected' => 
          array (
            'name' => 'expected',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'mixed',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1005,
            'endLine' => 1005,
            'startColumn' => 64,
            'endColumn' => 78,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'actual' => 
          array (
            'name' => 'actual',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'mixed',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1005,
            'endLine' => 1005,
            'startColumn' => 81,
            'endColumn' => 93,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 1005,
                'endLine' => 1005,
                'startTokenPos' => 3849,
                'startFilePos' => 30146,
                'endTokenPos' => 3849,
                'endFilePos' => 30147,
              ),
            ),
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
            'startLine' => 1005,
            'endLine' => 1005,
            'startColumn' => 96,
            'endColumn' => 115,
            'parameterIndex' => 2,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that two variables are not equal (canonicalizing).
 *
 * @throws ExpectationFailedException
 */',
        'startLine' => 1005,
        'endLine' => 1012,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertNotEqualsIgnoringCase' => 
      array (
        'name' => 'assertNotEqualsIgnoringCase',
        'parameters' => 
        array (
          'expected' => 
          array (
            'name' => 'expected',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'mixed',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1019,
            'endLine' => 1019,
            'startColumn' => 62,
            'endColumn' => 76,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'actual' => 
          array (
            'name' => 'actual',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'mixed',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1019,
            'endLine' => 1019,
            'startColumn' => 79,
            'endColumn' => 91,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 1019,
                'endLine' => 1019,
                'startTokenPos' => 3921,
                'startFilePos' => 30568,
                'endTokenPos' => 3921,
                'endFilePos' => 30569,
              ),
            ),
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
            'startLine' => 1019,
            'endLine' => 1019,
            'startColumn' => 94,
            'endColumn' => 113,
            'parameterIndex' => 2,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that two variables are not equal (ignoring case).
 *
 * @throws ExpectationFailedException
 */',
        'startLine' => 1019,
        'endLine' => 1026,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertNotEqualsWithDelta' => 
      array (
        'name' => 'assertNotEqualsWithDelta',
        'parameters' => 
        array (
          'expected' => 
          array (
            'name' => 'expected',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'mixed',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1033,
            'endLine' => 1033,
            'startColumn' => 59,
            'endColumn' => 73,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'actual' => 
          array (
            'name' => 'actual',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'mixed',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1033,
            'endLine' => 1033,
            'startColumn' => 76,
            'endColumn' => 88,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'delta' => 
          array (
            'name' => 'delta',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'float',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1033,
            'endLine' => 1033,
            'startColumn' => 91,
            'endColumn' => 102,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 1033,
                'endLine' => 1033,
                'startTokenPos' => 3998,
                'startFilePos' => 30996,
                'endTokenPos' => 3998,
                'endFilePos' => 30997,
              ),
            ),
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
            'startLine' => 1033,
            'endLine' => 1033,
            'startColumn' => 105,
            'endColumn' => 124,
            'parameterIndex' => 3,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that two variables are not equal (with delta).
 *
 * @throws ExpectationFailedException
 */',
        'startLine' => 1033,
        'endLine' => 1043,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertObjectEquals' => 
      array (
        'name' => 'assertObjectEquals',
        'parameters' => 
        array (
          'expected' => 
          array (
            'name' => 'expected',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'object',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1048,
            'endLine' => 1048,
            'startColumn' => 53,
            'endColumn' => 68,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'actual' => 
          array (
            'name' => 'actual',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'object',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1048,
            'endLine' => 1048,
            'startColumn' => 71,
            'endColumn' => 84,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'method' => 
          array (
            'name' => 'method',
            'default' => 
            array (
              'code' => '\'equals\'',
              'attributes' => 
              array (
                'startLine' => 1048,
                'endLine' => 1048,
                'startTokenPos' => 4076,
                'startFilePos' => 31388,
                'endTokenPos' => 4076,
                'endFilePos' => 31395,
              ),
            ),
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
            'startLine' => 1048,
            'endLine' => 1048,
            'startColumn' => 87,
            'endColumn' => 111,
            'parameterIndex' => 2,
            'isOptional' => true,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 1048,
                'endLine' => 1048,
                'startTokenPos' => 4085,
                'startFilePos' => 31416,
                'endTokenPos' => 4085,
                'endFilePos' => 31417,
              ),
            ),
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
            'startLine' => 1048,
            'endLine' => 1048,
            'startColumn' => 114,
            'endColumn' => 133,
            'parameterIndex' => 3,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * @throws ExpectationFailedException
 */',
        'startLine' => 1048,
        'endLine' => 1055,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertObjectNotEquals' => 
      array (
        'name' => 'assertObjectNotEquals',
        'parameters' => 
        array (
          'expected' => 
          array (
            'name' => 'expected',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'object',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1060,
            'endLine' => 1060,
            'startColumn' => 56,
            'endColumn' => 71,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'actual' => 
          array (
            'name' => 'actual',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'object',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1060,
            'endLine' => 1060,
            'startColumn' => 74,
            'endColumn' => 87,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'method' => 
          array (
            'name' => 'method',
            'default' => 
            array (
              'code' => '\'equals\'',
              'attributes' => 
              array (
                'startLine' => 1060,
                'endLine' => 1060,
                'startTokenPos' => 4148,
                'startFilePos' => 31735,
                'endTokenPos' => 4148,
                'endFilePos' => 31742,
              ),
            ),
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
            'startLine' => 1060,
            'endLine' => 1060,
            'startColumn' => 90,
            'endColumn' => 114,
            'parameterIndex' => 2,
            'isOptional' => true,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 1060,
                'endLine' => 1060,
                'startTokenPos' => 4157,
                'startFilePos' => 31763,
                'endTokenPos' => 4157,
                'endFilePos' => 31764,
              ),
            ),
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
            'startLine' => 1060,
            'endLine' => 1060,
            'startColumn' => 117,
            'endColumn' => 136,
            'parameterIndex' => 3,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * @throws ExpectationFailedException
 */',
        'startLine' => 1060,
        'endLine' => 1069,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertEmpty' => 
      array (
        'name' => 'assertEmpty',
        'parameters' => 
        array (
          'actual' => 
          array (
            'name' => 'actual',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'mixed',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1077,
            'endLine' => 1077,
            'startColumn' => 46,
            'endColumn' => 58,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 1077,
                'endLine' => 1077,
                'startTokenPos' => 4223,
                'startFilePos' => 32197,
                'endTokenPos' => 4223,
                'endFilePos' => 32198,
              ),
            ),
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
            'startLine' => 1077,
            'endLine' => 1077,
            'startColumn' => 61,
            'endColumn' => 80,
            'parameterIndex' => 1,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that a variable is empty.
 *
 * @throws ExpectationFailedException
 * @throws GeneratorNotSupportedException
 */',
        'startLine' => 1077,
        'endLine' => 1084,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertNotEmpty' => 
      array (
        'name' => 'assertNotEmpty',
        'parameters' => 
        array (
          'actual' => 
          array (
            'name' => 'actual',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'mixed',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1092,
            'endLine' => 1092,
            'startColumn' => 49,
            'endColumn' => 61,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 1092,
                'endLine' => 1092,
                'startTokenPos' => 4298,
                'startFilePos' => 32654,
                'endTokenPos' => 4298,
                'endFilePos' => 32655,
              ),
            ),
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
            'startLine' => 1092,
            'endLine' => 1092,
            'startColumn' => 64,
            'endColumn' => 83,
            'parameterIndex' => 1,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that a variable is not empty.
 *
 * @throws ExpectationFailedException
 * @throws GeneratorNotSupportedException
 */',
        'startLine' => 1092,
        'endLine' => 1099,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertGreaterThan' => 
      array (
        'name' => 'assertGreaterThan',
        'parameters' => 
        array (
          'minimum' => 
          array (
            'name' => 'minimum',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'mixed',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1106,
            'endLine' => 1106,
            'startColumn' => 52,
            'endColumn' => 65,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'actual' => 
          array (
            'name' => 'actual',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'mixed',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1106,
            'endLine' => 1106,
            'startColumn' => 68,
            'endColumn' => 80,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 1106,
                'endLine' => 1106,
                'startTokenPos' => 4383,
                'startFilePos' => 33116,
                'endTokenPos' => 4383,
                'endFilePos' => 33117,
              ),
            ),
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
            'startLine' => 1106,
            'endLine' => 1106,
            'startColumn' => 83,
            'endColumn' => 102,
            'parameterIndex' => 2,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that a value is greater than another value.
 *
 * @throws ExpectationFailedException
 */',
        'startLine' => 1106,
        'endLine' => 1109,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertGreaterThanOrEqual' => 
      array (
        'name' => 'assertGreaterThanOrEqual',
        'parameters' => 
        array (
          'minimum' => 
          array (
            'name' => 'minimum',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'mixed',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1116,
            'endLine' => 1116,
            'startColumn' => 59,
            'endColumn' => 72,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'actual' => 
          array (
            'name' => 'actual',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'mixed',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1116,
            'endLine' => 1116,
            'startColumn' => 75,
            'endColumn' => 87,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 1116,
                'endLine' => 1116,
                'startTokenPos' => 4440,
                'startFilePos' => 33456,
                'endTokenPos' => 4440,
                'endFilePos' => 33457,
              ),
            ),
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
            'startLine' => 1116,
            'endLine' => 1116,
            'startColumn' => 90,
            'endColumn' => 109,
            'parameterIndex' => 2,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that a value is greater than or equal to another value.
 *
 * @throws ExpectationFailedException
 */',
        'startLine' => 1116,
        'endLine' => 1123,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertLessThan' => 
      array (
        'name' => 'assertLessThan',
        'parameters' => 
        array (
          'maximum' => 
          array (
            'name' => 'maximum',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'mixed',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1130,
            'endLine' => 1130,
            'startColumn' => 49,
            'endColumn' => 62,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'actual' => 
          array (
            'name' => 'actual',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'mixed',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1130,
            'endLine' => 1130,
            'startColumn' => 65,
            'endColumn' => 77,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 1130,
                'endLine' => 1130,
                'startTokenPos' => 4500,
                'startFilePos' => 33828,
                'endTokenPos' => 4500,
                'endFilePos' => 33829,
              ),
            ),
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
            'startLine' => 1130,
            'endLine' => 1130,
            'startColumn' => 80,
            'endColumn' => 99,
            'parameterIndex' => 2,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that a value is smaller than another value.
 *
 * @throws ExpectationFailedException
 */',
        'startLine' => 1130,
        'endLine' => 1133,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertLessThanOrEqual' => 
      array (
        'name' => 'assertLessThanOrEqual',
        'parameters' => 
        array (
          'maximum' => 
          array (
            'name' => 'maximum',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'mixed',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1140,
            'endLine' => 1140,
            'startColumn' => 56,
            'endColumn' => 69,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'actual' => 
          array (
            'name' => 'actual',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'mixed',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1140,
            'endLine' => 1140,
            'startColumn' => 72,
            'endColumn' => 84,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 1140,
                'endLine' => 1140,
                'startTokenPos' => 4557,
                'startFilePos' => 34162,
                'endTokenPos' => 4557,
                'endFilePos' => 34163,
              ),
            ),
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
            'startLine' => 1140,
            'endLine' => 1140,
            'startColumn' => 87,
            'endColumn' => 106,
            'parameterIndex' => 2,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that a value is smaller than or equal to another value.
 *
 * @throws ExpectationFailedException
 */',
        'startLine' => 1140,
        'endLine' => 1143,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertFileEquals' => 
      array (
        'name' => 'assertFileEquals',
        'parameters' => 
        array (
          'expected' => 
          array (
            'name' => 'expected',
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
            'startLine' => 1151,
            'endLine' => 1151,
            'startColumn' => 51,
            'endColumn' => 66,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'actual' => 
          array (
            'name' => 'actual',
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
            'startLine' => 1151,
            'endLine' => 1151,
            'startColumn' => 69,
            'endColumn' => 82,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 1151,
                'endLine' => 1151,
                'startTokenPos' => 4614,
                'startFilePos' => 34524,
                'endTokenPos' => 4614,
                'endFilePos' => 34525,
              ),
            ),
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
            'startLine' => 1151,
            'endLine' => 1151,
            'startColumn' => 85,
            'endColumn' => 104,
            'parameterIndex' => 2,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that the contents of one file is equal to the contents of another
 * file.
 *
 * @throws ExpectationFailedException
 */',
        'startLine' => 1151,
        'endLine' => 1159,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertFileEqualsCanonicalizing' => 
      array (
        'name' => 'assertFileEqualsCanonicalizing',
        'parameters' => 
        array (
          'expected' => 
          array (
            'name' => 'expected',
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
            'startLine' => 1167,
            'endLine' => 1167,
            'startColumn' => 65,
            'endColumn' => 80,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'actual' => 
          array (
            'name' => 'actual',
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
            'startLine' => 1167,
            'endLine' => 1167,
            'startColumn' => 83,
            'endColumn' => 96,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 1167,
                'endLine' => 1167,
                'startTokenPos' => 4706,
                'startFilePos' => 35087,
                'endTokenPos' => 4706,
                'endFilePos' => 35088,
              ),
            ),
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
            'startLine' => 1167,
            'endLine' => 1167,
            'startColumn' => 99,
            'endColumn' => 118,
            'parameterIndex' => 2,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that the contents of one file is equal to the contents of another
 * file (canonicalizing).
 *
 * @throws ExpectationFailedException
 */',
        'startLine' => 1167,
        'endLine' => 1177,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertFileEqualsIgnoringCase' => 
      array (
        'name' => 'assertFileEqualsIgnoringCase',
        'parameters' => 
        array (
          'expected' => 
          array (
            'name' => 'expected',
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
            'startLine' => 1185,
            'endLine' => 1185,
            'startColumn' => 63,
            'endColumn' => 78,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'actual' => 
          array (
            'name' => 'actual',
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
            'startLine' => 1185,
            'endLine' => 1185,
            'startColumn' => 81,
            'endColumn' => 94,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 1185,
                'endLine' => 1185,
                'startTokenPos' => 4801,
                'startFilePos' => 35684,
                'endTokenPos' => 4801,
                'endFilePos' => 35685,
              ),
            ),
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
            'startLine' => 1185,
            'endLine' => 1185,
            'startColumn' => 97,
            'endColumn' => 116,
            'parameterIndex' => 2,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that the contents of one file is equal to the contents of another
 * file (ignoring case).
 *
 * @throws ExpectationFailedException
 */',
        'startLine' => 1185,
        'endLine' => 1193,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertFileNotEquals' => 
      array (
        'name' => 'assertFileNotEquals',
        'parameters' => 
        array (
          'expected' => 
          array (
            'name' => 'expected',
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
            'startLine' => 1201,
            'endLine' => 1201,
            'startColumn' => 54,
            'endColumn' => 69,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'actual' => 
          array (
            'name' => 'actual',
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
            'startLine' => 1201,
            'endLine' => 1201,
            'startColumn' => 72,
            'endColumn' => 85,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 1201,
                'endLine' => 1201,
                'startTokenPos' => 4893,
                'startFilePos' => 36235,
                'endTokenPos' => 4893,
                'endFilePos' => 36236,
              ),
            ),
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
            'startLine' => 1201,
            'endLine' => 1201,
            'startColumn' => 88,
            'endColumn' => 107,
            'parameterIndex' => 2,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that the contents of one file is not equal to the contents of
 * another file.
 *
 * @throws ExpectationFailedException
 */',
        'startLine' => 1201,
        'endLine' => 1211,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertFileNotEqualsCanonicalizing' => 
      array (
        'name' => 'assertFileNotEqualsCanonicalizing',
        'parameters' => 
        array (
          'expected' => 
          array (
            'name' => 'expected',
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
            'startLine' => 1219,
            'endLine' => 1219,
            'startColumn' => 68,
            'endColumn' => 83,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'actual' => 
          array (
            'name' => 'actual',
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
            'startLine' => 1219,
            'endLine' => 1219,
            'startColumn' => 86,
            'endColumn' => 99,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 1219,
                'endLine' => 1219,
                'startTokenPos' => 4993,
                'startFilePos' => 36844,
                'endTokenPos' => 4993,
                'endFilePos' => 36845,
              ),
            ),
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
            'startLine' => 1219,
            'endLine' => 1219,
            'startColumn' => 102,
            'endColumn' => 121,
            'parameterIndex' => 2,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that the contents of one file is not equal to the contents of another
 * file (canonicalizing).
 *
 * @throws ExpectationFailedException
 */',
        'startLine' => 1219,
        'endLine' => 1229,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertFileNotEqualsIgnoringCase' => 
      array (
        'name' => 'assertFileNotEqualsIgnoringCase',
        'parameters' => 
        array (
          'expected' => 
          array (
            'name' => 'expected',
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
            'startLine' => 1237,
            'endLine' => 1237,
            'startColumn' => 66,
            'endColumn' => 81,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'actual' => 
          array (
            'name' => 'actual',
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
            'startLine' => 1237,
            'endLine' => 1237,
            'startColumn' => 84,
            'endColumn' => 97,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 1237,
                'endLine' => 1237,
                'startTokenPos' => 5093,
                'startFilePos' => 37464,
                'endTokenPos' => 5093,
                'endFilePos' => 37465,
              ),
            ),
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
            'startLine' => 1237,
            'endLine' => 1237,
            'startColumn' => 100,
            'endColumn' => 119,
            'parameterIndex' => 2,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that the contents of one file is not equal to the contents of another
 * file (ignoring case).
 *
 * @throws ExpectationFailedException
 */',
        'startLine' => 1237,
        'endLine' => 1247,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertStringEqualsFile' => 
      array (
        'name' => 'assertStringEqualsFile',
        'parameters' => 
        array (
          'expectedFile' => 
          array (
            'name' => 'expectedFile',
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
            'startLine' => 1255,
            'endLine' => 1255,
            'startColumn' => 57,
            'endColumn' => 76,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'actualString' => 
          array (
            'name' => 'actualString',
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
            'startLine' => 1255,
            'endLine' => 1255,
            'startColumn' => 79,
            'endColumn' => 98,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 1255,
                'endLine' => 1255,
                'startTokenPos' => 5193,
                'startFilePos' => 38057,
                'endTokenPos' => 5193,
                'endFilePos' => 38058,
              ),
            ),
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
            'startLine' => 1255,
            'endLine' => 1255,
            'startColumn' => 101,
            'endColumn' => 120,
            'parameterIndex' => 2,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that the contents of a string is equal
 * to the contents of a file.
 *
 * @throws ExpectationFailedException
 */',
        'startLine' => 1255,
        'endLine' => 1262,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertStringEqualsFileCanonicalizing' => 
      array (
        'name' => 'assertStringEqualsFileCanonicalizing',
        'parameters' => 
        array (
          'expectedFile' => 
          array (
            'name' => 'expectedFile',
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
            'startLine' => 1270,
            'endLine' => 1270,
            'startColumn' => 71,
            'endColumn' => 90,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'actualString' => 
          array (
            'name' => 'actualString',
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
            'startLine' => 1270,
            'endLine' => 1270,
            'startColumn' => 93,
            'endColumn' => 112,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 1270,
                'endLine' => 1270,
                'startTokenPos' => 5271,
                'startFilePos' => 38574,
                'endTokenPos' => 5271,
                'endFilePos' => 38575,
              ),
            ),
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
            'startLine' => 1270,
            'endLine' => 1270,
            'startColumn' => 115,
            'endColumn' => 134,
            'parameterIndex' => 2,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that the contents of a string is equal
 * to the contents of a file (canonicalizing).
 *
 * @throws ExpectationFailedException
 */',
        'startLine' => 1270,
        'endLine' => 1277,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertStringEqualsFileIgnoringCase' => 
      array (
        'name' => 'assertStringEqualsFileIgnoringCase',
        'parameters' => 
        array (
          'expectedFile' => 
          array (
            'name' => 'expectedFile',
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
            'startLine' => 1285,
            'endLine' => 1285,
            'startColumn' => 69,
            'endColumn' => 88,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'actualString' => 
          array (
            'name' => 'actualString',
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
            'startLine' => 1285,
            'endLine' => 1285,
            'startColumn' => 91,
            'endColumn' => 110,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 1285,
                'endLine' => 1285,
                'startTokenPos' => 5349,
                'startFilePos' => 39102,
                'endTokenPos' => 5349,
                'endFilePos' => 39103,
              ),
            ),
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
            'startLine' => 1285,
            'endLine' => 1285,
            'startColumn' => 113,
            'endColumn' => 132,
            'parameterIndex' => 2,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that the contents of a string is equal
 * to the contents of a file (ignoring case).
 *
 * @throws ExpectationFailedException
 */',
        'startLine' => 1285,
        'endLine' => 1292,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertStringNotEqualsFile' => 
      array (
        'name' => 'assertStringNotEqualsFile',
        'parameters' => 
        array (
          'expectedFile' => 
          array (
            'name' => 'expectedFile',
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
            'startLine' => 1300,
            'endLine' => 1300,
            'startColumn' => 60,
            'endColumn' => 79,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'actualString' => 
          array (
            'name' => 'actualString',
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
            'startLine' => 1300,
            'endLine' => 1300,
            'startColumn' => 82,
            'endColumn' => 101,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 1300,
                'endLine' => 1300,
                'startTokenPos' => 5427,
                'startFilePos' => 39607,
                'endTokenPos' => 5427,
                'endFilePos' => 39608,
              ),
            ),
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
            'startLine' => 1300,
            'endLine' => 1300,
            'startColumn' => 104,
            'endColumn' => 123,
            'parameterIndex' => 2,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that the contents of a string is not equal
 * to the contents of a file.
 *
 * @throws ExpectationFailedException
 */',
        'startLine' => 1300,
        'endLine' => 1309,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertStringNotEqualsFileCanonicalizing' => 
      array (
        'name' => 'assertStringNotEqualsFileCanonicalizing',
        'parameters' => 
        array (
          'expectedFile' => 
          array (
            'name' => 'expectedFile',
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
            'startLine' => 1317,
            'endLine' => 1317,
            'startColumn' => 74,
            'endColumn' => 93,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'actualString' => 
          array (
            'name' => 'actualString',
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
            'startLine' => 1317,
            'endLine' => 1317,
            'startColumn' => 96,
            'endColumn' => 115,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 1317,
                'endLine' => 1317,
                'startTokenPos' => 5513,
                'startFilePos' => 40170,
                'endTokenPos' => 5513,
                'endFilePos' => 40171,
              ),
            ),
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
            'startLine' => 1317,
            'endLine' => 1317,
            'startColumn' => 118,
            'endColumn' => 137,
            'parameterIndex' => 2,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that the contents of a string is not equal
 * to the contents of a file (canonicalizing).
 *
 * @throws ExpectationFailedException
 */',
        'startLine' => 1317,
        'endLine' => 1326,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertStringNotEqualsFileIgnoringCase' => 
      array (
        'name' => 'assertStringNotEqualsFileIgnoringCase',
        'parameters' => 
        array (
          'expectedFile' => 
          array (
            'name' => 'expectedFile',
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
            'startLine' => 1334,
            'endLine' => 1334,
            'startColumn' => 72,
            'endColumn' => 91,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'actualString' => 
          array (
            'name' => 'actualString',
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
            'startLine' => 1334,
            'endLine' => 1334,
            'startColumn' => 94,
            'endColumn' => 113,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 1334,
                'endLine' => 1334,
                'startTokenPos' => 5599,
                'startFilePos' => 40744,
                'endTokenPos' => 5599,
                'endFilePos' => 40745,
              ),
            ),
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
            'startLine' => 1334,
            'endLine' => 1334,
            'startColumn' => 116,
            'endColumn' => 135,
            'parameterIndex' => 2,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that the contents of a string is not equal
 * to the contents of a file (ignoring case).
 *
 * @throws ExpectationFailedException
 */',
        'startLine' => 1334,
        'endLine' => 1343,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertIsReadable' => 
      array (
        'name' => 'assertIsReadable',
        'parameters' => 
        array (
          'filename' => 
          array (
            'name' => 'filename',
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
            'startLine' => 1350,
            'endLine' => 1350,
            'startColumn' => 51,
            'endColumn' => 66,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 1350,
                'endLine' => 1350,
                'startTokenPos' => 5680,
                'startFilePos' => 41205,
                'endTokenPos' => 5680,
                'endFilePos' => 41206,
              ),
            ),
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
            'startLine' => 1350,
            'endLine' => 1350,
            'startColumn' => 69,
            'endColumn' => 88,
            'parameterIndex' => 1,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that a file/dir is readable.
 *
 * @throws ExpectationFailedException
 */',
        'startLine' => 1350,
        'endLine' => 1353,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertIsNotReadable' => 
      array (
        'name' => 'assertIsNotReadable',
        'parameters' => 
        array (
          'filename' => 
          array (
            'name' => 'filename',
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
            'startLine' => 1360,
            'endLine' => 1360,
            'startColumn' => 54,
            'endColumn' => 69,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 1360,
                'endLine' => 1360,
                'startTokenPos' => 5729,
                'startFilePos' => 41504,
                'endTokenPos' => 5729,
                'endFilePos' => 41505,
              ),
            ),
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
            'startLine' => 1360,
            'endLine' => 1360,
            'startColumn' => 72,
            'endColumn' => 91,
            'parameterIndex' => 1,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that a file/dir exists and is not readable.
 *
 * @throws ExpectationFailedException
 */',
        'startLine' => 1360,
        'endLine' => 1363,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertIsWritable' => 
      array (
        'name' => 'assertIsWritable',
        'parameters' => 
        array (
          'filename' => 
          array (
            'name' => 'filename',
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
            'startLine' => 1370,
            'endLine' => 1370,
            'startColumn' => 51,
            'endColumn' => 66,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 1370,
                'endLine' => 1370,
                'startTokenPos' => 5783,
                'startFilePos' => 41812,
                'endTokenPos' => 5783,
                'endFilePos' => 41813,
              ),
            ),
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
            'startLine' => 1370,
            'endLine' => 1370,
            'startColumn' => 69,
            'endColumn' => 88,
            'parameterIndex' => 1,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that a file/dir exists and is writable.
 *
 * @throws ExpectationFailedException
 */',
        'startLine' => 1370,
        'endLine' => 1373,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertIsNotWritable' => 
      array (
        'name' => 'assertIsNotWritable',
        'parameters' => 
        array (
          'filename' => 
          array (
            'name' => 'filename',
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
            'startLine' => 1380,
            'endLine' => 1380,
            'startColumn' => 54,
            'endColumn' => 69,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 1380,
                'endLine' => 1380,
                'startTokenPos' => 5832,
                'startFilePos' => 42111,
                'endTokenPos' => 5832,
                'endFilePos' => 42112,
              ),
            ),
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
            'startLine' => 1380,
            'endLine' => 1380,
            'startColumn' => 72,
            'endColumn' => 91,
            'parameterIndex' => 1,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that a file/dir exists and is not writable.
 *
 * @throws ExpectationFailedException
 */',
        'startLine' => 1380,
        'endLine' => 1383,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertDirectoryExists' => 
      array (
        'name' => 'assertDirectoryExists',
        'parameters' => 
        array (
          'directory' => 
          array (
            'name' => 'directory',
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
            'startLine' => 1390,
            'endLine' => 1390,
            'startColumn' => 56,
            'endColumn' => 72,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 1390,
                'endLine' => 1390,
                'startTokenPos' => 5886,
                'startFilePos' => 42410,
                'endTokenPos' => 5886,
                'endFilePos' => 42411,
              ),
            ),
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
            'startLine' => 1390,
            'endLine' => 1390,
            'startColumn' => 75,
            'endColumn' => 94,
            'parameterIndex' => 1,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that a directory exists.
 *
 * @throws ExpectationFailedException
 */',
        'startLine' => 1390,
        'endLine' => 1393,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertDirectoryDoesNotExist' => 
      array (
        'name' => 'assertDirectoryDoesNotExist',
        'parameters' => 
        array (
          'directory' => 
          array (
            'name' => 'directory',
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
            'startLine' => 1400,
            'endLine' => 1400,
            'startColumn' => 62,
            'endColumn' => 78,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 1400,
                'endLine' => 1400,
                'startTokenPos' => 5935,
                'startFilePos' => 42713,
                'endTokenPos' => 5935,
                'endFilePos' => 42714,
              ),
            ),
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
            'startLine' => 1400,
            'endLine' => 1400,
            'startColumn' => 81,
            'endColumn' => 100,
            'parameterIndex' => 1,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that a directory does not exist.
 *
 * @throws ExpectationFailedException
 */',
        'startLine' => 1400,
        'endLine' => 1403,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertDirectoryIsReadable' => 
      array (
        'name' => 'assertDirectoryIsReadable',
        'parameters' => 
        array (
          'directory' => 
          array (
            'name' => 'directory',
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
            'startLine' => 1410,
            'endLine' => 1410,
            'startColumn' => 60,
            'endColumn' => 76,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 1410,
                'endLine' => 1410,
                'startTokenPos' => 5989,
                'startFilePos' => 43038,
                'endTokenPos' => 5989,
                'endFilePos' => 43039,
              ),
            ),
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
            'startLine' => 1410,
            'endLine' => 1410,
            'startColumn' => 79,
            'endColumn' => 98,
            'parameterIndex' => 1,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that a directory exists and is readable.
 *
 * @throws ExpectationFailedException
 */',
        'startLine' => 1410,
        'endLine' => 1414,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertDirectoryIsNotReadable' => 
      array (
        'name' => 'assertDirectoryIsNotReadable',
        'parameters' => 
        array (
          'directory' => 
          array (
            'name' => 'directory',
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
            'startLine' => 1421,
            'endLine' => 1421,
            'startColumn' => 63,
            'endColumn' => 79,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 1421,
                'endLine' => 1421,
                'startTokenPos' => 6044,
                'startFilePos' => 43398,
                'endTokenPos' => 6044,
                'endFilePos' => 43399,
              ),
            ),
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
            'startLine' => 1421,
            'endLine' => 1421,
            'startColumn' => 82,
            'endColumn' => 101,
            'parameterIndex' => 1,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that a directory exists and is not readable.
 *
 * @throws ExpectationFailedException
 */',
        'startLine' => 1421,
        'endLine' => 1425,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertDirectoryIsWritable' => 
      array (
        'name' => 'assertDirectoryIsWritable',
        'parameters' => 
        array (
          'directory' => 
          array (
            'name' => 'directory',
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
            'startLine' => 1432,
            'endLine' => 1432,
            'startColumn' => 60,
            'endColumn' => 76,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 1432,
                'endLine' => 1432,
                'startTokenPos' => 6099,
                'startFilePos' => 43754,
                'endTokenPos' => 6099,
                'endFilePos' => 43755,
              ),
            ),
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
            'startLine' => 1432,
            'endLine' => 1432,
            'startColumn' => 79,
            'endColumn' => 98,
            'parameterIndex' => 1,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that a directory exists and is writable.
 *
 * @throws ExpectationFailedException
 */',
        'startLine' => 1432,
        'endLine' => 1436,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertDirectoryIsNotWritable' => 
      array (
        'name' => 'assertDirectoryIsNotWritable',
        'parameters' => 
        array (
          'directory' => 
          array (
            'name' => 'directory',
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
            'startLine' => 1443,
            'endLine' => 1443,
            'startColumn' => 63,
            'endColumn' => 79,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 1443,
                'endLine' => 1443,
                'startTokenPos' => 6154,
                'startFilePos' => 44114,
                'endTokenPos' => 6154,
                'endFilePos' => 44115,
              ),
            ),
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
            'startLine' => 1443,
            'endLine' => 1443,
            'startColumn' => 82,
            'endColumn' => 101,
            'parameterIndex' => 1,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that a directory exists and is not writable.
 *
 * @throws ExpectationFailedException
 */',
        'startLine' => 1443,
        'endLine' => 1447,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertFileExists' => 
      array (
        'name' => 'assertFileExists',
        'parameters' => 
        array (
          'filename' => 
          array (
            'name' => 'filename',
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
            'startLine' => 1454,
            'endLine' => 1454,
            'startColumn' => 51,
            'endColumn' => 66,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 1454,
                'endLine' => 1454,
                'startTokenPos' => 6209,
                'startFilePos' => 44439,
                'endTokenPos' => 6209,
                'endFilePos' => 44440,
              ),
            ),
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
            'startLine' => 1454,
            'endLine' => 1454,
            'startColumn' => 69,
            'endColumn' => 88,
            'parameterIndex' => 1,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that a file exists.
 *
 * @throws ExpectationFailedException
 */',
        'startLine' => 1454,
        'endLine' => 1457,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertFileDoesNotExist' => 
      array (
        'name' => 'assertFileDoesNotExist',
        'parameters' => 
        array (
          'filename' => 
          array (
            'name' => 'filename',
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
            'startLine' => 1464,
            'endLine' => 1464,
            'startColumn' => 57,
            'endColumn' => 72,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 1464,
                'endLine' => 1464,
                'startTokenPos' => 6258,
                'startFilePos' => 44725,
                'endTokenPos' => 6258,
                'endFilePos' => 44726,
              ),
            ),
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
            'startLine' => 1464,
            'endLine' => 1464,
            'startColumn' => 75,
            'endColumn' => 94,
            'parameterIndex' => 1,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that a file does not exist.
 *
 * @throws ExpectationFailedException
 */',
        'startLine' => 1464,
        'endLine' => 1467,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertFileIsReadable' => 
      array (
        'name' => 'assertFileIsReadable',
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
            'startLine' => 1474,
            'endLine' => 1474,
            'startColumn' => 55,
            'endColumn' => 66,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 1474,
                'endLine' => 1474,
                'startTokenPos' => 6312,
                'startFilePos' => 45029,
                'endTokenPos' => 6312,
                'endFilePos' => 45030,
              ),
            ),
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
            'startLine' => 1474,
            'endLine' => 1474,
            'startColumn' => 69,
            'endColumn' => 88,
            'parameterIndex' => 1,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that a file exists and is readable.
 *
 * @throws ExpectationFailedException
 */',
        'startLine' => 1474,
        'endLine' => 1478,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertFileIsNotReadable' => 
      array (
        'name' => 'assertFileIsNotReadable',
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
            'startLine' => 1485,
            'endLine' => 1485,
            'startColumn' => 58,
            'endColumn' => 69,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 1485,
                'endLine' => 1485,
                'startTokenPos' => 6367,
                'startFilePos' => 45359,
                'endTokenPos' => 6367,
                'endFilePos' => 45360,
              ),
            ),
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
            'startLine' => 1485,
            'endLine' => 1485,
            'startColumn' => 72,
            'endColumn' => 91,
            'parameterIndex' => 1,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that a file exists and is not readable.
 *
 * @throws ExpectationFailedException
 */',
        'startLine' => 1485,
        'endLine' => 1489,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertFileIsWritable' => 
      array (
        'name' => 'assertFileIsWritable',
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
            'startLine' => 1496,
            'endLine' => 1496,
            'startColumn' => 55,
            'endColumn' => 66,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 1496,
                'endLine' => 1496,
                'startTokenPos' => 6422,
                'startFilePos' => 45685,
                'endTokenPos' => 6422,
                'endFilePos' => 45686,
              ),
            ),
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
            'startLine' => 1496,
            'endLine' => 1496,
            'startColumn' => 69,
            'endColumn' => 88,
            'parameterIndex' => 1,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that a file exists and is writable.
 *
 * @throws ExpectationFailedException
 */',
        'startLine' => 1496,
        'endLine' => 1500,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertFileIsNotWritable' => 
      array (
        'name' => 'assertFileIsNotWritable',
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
            'startLine' => 1507,
            'endLine' => 1507,
            'startColumn' => 58,
            'endColumn' => 69,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 1507,
                'endLine' => 1507,
                'startTokenPos' => 6477,
                'startFilePos' => 46015,
                'endTokenPos' => 6477,
                'endFilePos' => 46016,
              ),
            ),
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
            'startLine' => 1507,
            'endLine' => 1507,
            'startColumn' => 72,
            'endColumn' => 91,
            'parameterIndex' => 1,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that a file exists and is not writable.
 *
 * @throws ExpectationFailedException
 */',
        'startLine' => 1507,
        'endLine' => 1511,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertTrue' => 
      array (
        'name' => 'assertTrue',
        'parameters' => 
        array (
          'condition' => 
          array (
            'name' => 'condition',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'mixed',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1520,
            'endLine' => 1520,
            'startColumn' => 45,
            'endColumn' => 60,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 1520,
                'endLine' => 1520,
                'startTokenPos' => 6532,
                'startFilePos' => 46371,
                'endTokenPos' => 6532,
                'endFilePos' => 46372,
              ),
            ),
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
            'startLine' => 1520,
            'endLine' => 1520,
            'startColumn' => 63,
            'endColumn' => 82,
            'parameterIndex' => 1,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that a condition is true.
 *
 * @throws ExpectationFailedException
 *
 * @phpstan-assert true $condition
 */',
        'startLine' => 1520,
        'endLine' => 1523,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertNotTrue' => 
      array (
        'name' => 'assertNotTrue',
        'parameters' => 
        array (
          'condition' => 
          array (
            'name' => 'condition',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'mixed',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1532,
            'endLine' => 1532,
            'startColumn' => 48,
            'endColumn' => 63,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 1532,
                'endLine' => 1532,
                'startTokenPos' => 6583,
                'startFilePos' => 46698,
                'endTokenPos' => 6583,
                'endFilePos' => 46699,
              ),
            ),
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
            'startLine' => 1532,
            'endLine' => 1532,
            'startColumn' => 66,
            'endColumn' => 85,
            'parameterIndex' => 1,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that a condition is not true.
 *
 * @throws ExpectationFailedException
 *
 * @phpstan-assert !true $condition
 */',
        'startLine' => 1532,
        'endLine' => 1535,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertFalse' => 
      array (
        'name' => 'assertFalse',
        'parameters' => 
        array (
          'condition' => 
          array (
            'name' => 'condition',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'mixed',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1544,
            'endLine' => 1544,
            'startColumn' => 46,
            'endColumn' => 61,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 1544,
                'endLine' => 1544,
                'startTokenPos' => 6639,
                'startFilePos' => 47038,
                'endTokenPos' => 6639,
                'endFilePos' => 47039,
              ),
            ),
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
            'startLine' => 1544,
            'endLine' => 1544,
            'startColumn' => 64,
            'endColumn' => 83,
            'parameterIndex' => 1,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that a condition is false.
 *
 * @throws ExpectationFailedException
 *
 * @phpstan-assert false $condition
 */',
        'startLine' => 1544,
        'endLine' => 1547,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertNotFalse' => 
      array (
        'name' => 'assertNotFalse',
        'parameters' => 
        array (
          'condition' => 
          array (
            'name' => 'condition',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'mixed',
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
            'startColumn' => 49,
            'endColumn' => 64,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 1556,
                'endLine' => 1556,
                'startTokenPos' => 6690,
                'startFilePos' => 47369,
                'endTokenPos' => 6690,
                'endFilePos' => 47370,
              ),
            ),
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
            'startLine' => 1556,
            'endLine' => 1556,
            'startColumn' => 67,
            'endColumn' => 86,
            'parameterIndex' => 1,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that a condition is not false.
 *
 * @throws ExpectationFailedException
 *
 * @phpstan-assert !false $condition
 */',
        'startLine' => 1556,
        'endLine' => 1559,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertNull' => 
      array (
        'name' => 'assertNull',
        'parameters' => 
        array (
          'actual' => 
          array (
            'name' => 'actual',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'mixed',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1568,
            'endLine' => 1568,
            'startColumn' => 45,
            'endColumn' => 57,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 1568,
                'endLine' => 1568,
                'startTokenPos' => 6746,
                'startFilePos' => 47700,
                'endTokenPos' => 6746,
                'endFilePos' => 47701,
              ),
            ),
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
            'startLine' => 1568,
            'endLine' => 1568,
            'startColumn' => 60,
            'endColumn' => 79,
            'parameterIndex' => 1,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that a variable is null.
 *
 * @throws ExpectationFailedException
 *
 * @phpstan-assert null $actual
 */',
        'startLine' => 1568,
        'endLine' => 1571,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertNotNull' => 
      array (
        'name' => 'assertNotNull',
        'parameters' => 
        array (
          'actual' => 
          array (
            'name' => 'actual',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'mixed',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1580,
            'endLine' => 1580,
            'startColumn' => 48,
            'endColumn' => 60,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 1580,
                'endLine' => 1580,
                'startTokenPos' => 6797,
                'startFilePos' => 48017,
                'endTokenPos' => 6797,
                'endFilePos' => 48018,
              ),
            ),
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
            'startLine' => 1580,
            'endLine' => 1580,
            'startColumn' => 63,
            'endColumn' => 82,
            'parameterIndex' => 1,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that a variable is not null.
 *
 * @throws ExpectationFailedException
 *
 * @phpstan-assert !null $actual
 */',
        'startLine' => 1580,
        'endLine' => 1583,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertFinite' => 
      array (
        'name' => 'assertFinite',
        'parameters' => 
        array (
          'actual' => 
          array (
            'name' => 'actual',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'mixed',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1590,
            'endLine' => 1590,
            'startColumn' => 47,
            'endColumn' => 59,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 1590,
                'endLine' => 1590,
                'startTokenPos' => 6853,
                'startFilePos' => 48305,
                'endTokenPos' => 6853,
                'endFilePos' => 48306,
              ),
            ),
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
            'startLine' => 1590,
            'endLine' => 1590,
            'startColumn' => 62,
            'endColumn' => 81,
            'parameterIndex' => 1,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that a variable is finite.
 *
 * @throws ExpectationFailedException
 */',
        'startLine' => 1590,
        'endLine' => 1593,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertInfinite' => 
      array (
        'name' => 'assertInfinite',
        'parameters' => 
        array (
          'actual' => 
          array (
            'name' => 'actual',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'mixed',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1600,
            'endLine' => 1600,
            'startColumn' => 49,
            'endColumn' => 61,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 1600,
                'endLine' => 1600,
                'startTokenPos' => 6904,
                'startFilePos' => 48581,
                'endTokenPos' => 6904,
                'endFilePos' => 48582,
              ),
            ),
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
            'startLine' => 1600,
            'endLine' => 1600,
            'startColumn' => 64,
            'endColumn' => 83,
            'parameterIndex' => 1,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that a variable is infinite.
 *
 * @throws ExpectationFailedException
 */',
        'startLine' => 1600,
        'endLine' => 1603,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertNan' => 
      array (
        'name' => 'assertNan',
        'parameters' => 
        array (
          'actual' => 
          array (
            'name' => 'actual',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'mixed',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1610,
            'endLine' => 1610,
            'startColumn' => 44,
            'endColumn' => 56,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 1610,
                'endLine' => 1610,
                'startTokenPos' => 6955,
                'startFilePos' => 48849,
                'endTokenPos' => 6955,
                'endFilePos' => 48850,
              ),
            ),
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
            'startLine' => 1610,
            'endLine' => 1610,
            'startColumn' => 59,
            'endColumn' => 78,
            'parameterIndex' => 1,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that a variable is nan.
 *
 * @throws ExpectationFailedException
 */',
        'startLine' => 1610,
        'endLine' => 1613,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertObjectHasProperty' => 
      array (
        'name' => 'assertObjectHasProperty',
        'parameters' => 
        array (
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
            'startLine' => 1620,
            'endLine' => 1620,
            'startColumn' => 58,
            'endColumn' => 77,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'object' => 
          array (
            'name' => 'object',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'object',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1620,
            'endLine' => 1620,
            'startColumn' => 80,
            'endColumn' => 93,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 1620,
                'endLine' => 1620,
                'startTokenPos' => 7011,
                'startFilePos' => 49166,
                'endTokenPos' => 7011,
                'endFilePos' => 49167,
              ),
            ),
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
            'startLine' => 1620,
            'endLine' => 1620,
            'startColumn' => 96,
            'endColumn' => 115,
            'parameterIndex' => 2,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that an object has a specified property.
 *
 * @throws ExpectationFailedException
 */',
        'startLine' => 1620,
        'endLine' => 1627,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertObjectNotHasProperty' => 
      array (
        'name' => 'assertObjectNotHasProperty',
        'parameters' => 
        array (
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
            'startLine' => 1634,
            'endLine' => 1634,
            'startColumn' => 61,
            'endColumn' => 80,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'object' => 
          array (
            'name' => 'object',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'object',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1634,
            'endLine' => 1634,
            'startColumn' => 83,
            'endColumn' => 96,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 1634,
                'endLine' => 1634,
                'startTokenPos' => 7071,
                'startFilePos' => 49566,
                'endTokenPos' => 7071,
                'endFilePos' => 49567,
              ),
            ),
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
            'startLine' => 1634,
            'endLine' => 1634,
            'startColumn' => 99,
            'endColumn' => 118,
            'parameterIndex' => 2,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that an object does not have a specified property.
 *
 * @throws ExpectationFailedException
 */',
        'startLine' => 1634,
        'endLine' => 1643,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertSame' => 
      array (
        'name' => 'assertSame',
        'parameters' => 
        array (
          'expected' => 
          array (
            'name' => 'expected',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'mixed',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1658,
            'endLine' => 1658,
            'startColumn' => 45,
            'endColumn' => 59,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'actual' => 
          array (
            'name' => 'actual',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'mixed',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1658,
            'endLine' => 1658,
            'startColumn' => 62,
            'endColumn' => 74,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 1658,
                'endLine' => 1658,
                'startTokenPos' => 7139,
                'startFilePos' => 50210,
                'endTokenPos' => 7139,
                'endFilePos' => 50211,
              ),
            ),
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
            'startLine' => 1658,
            'endLine' => 1658,
            'startColumn' => 77,
            'endColumn' => 96,
            'parameterIndex' => 2,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that two variables have the same type and value.
 * Used on objects, it asserts that two variables reference
 * the same object.
 *
 * @template ExpectedType
 *
 * @param ExpectedType $expected
 *
 * @throws ExpectationFailedException
 *
 * @phpstan-assert =ExpectedType $actual
 */',
        'startLine' => 1658,
        'endLine' => 1665,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertNotSame' => 
      array (
        'name' => 'assertNotSame',
        'parameters' => 
        array (
          'expected' => 
          array (
            'name' => 'expected',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'mixed',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1674,
            'endLine' => 1674,
            'startColumn' => 48,
            'endColumn' => 62,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'actual' => 
          array (
            'name' => 'actual',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'mixed',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1674,
            'endLine' => 1674,
            'startColumn' => 65,
            'endColumn' => 77,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 1674,
                'endLine' => 1674,
                'startTokenPos' => 7199,
                'startFilePos' => 50681,
                'endTokenPos' => 7199,
                'endFilePos' => 50682,
              ),
            ),
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
            'startLine' => 1674,
            'endLine' => 1674,
            'startColumn' => 80,
            'endColumn' => 99,
            'parameterIndex' => 2,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that two variables do not have the same type and value.
 * Used on objects, it asserts that two variables do not reference
 * the same object.
 *
 * @throws ExpectationFailedException
 */',
        'startLine' => 1674,
        'endLine' => 1687,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertInstanceOf' => 
      array (
        'name' => 'assertInstanceOf',
        'parameters' => 
        array (
          'expected' => 
          array (
            'name' => 'expected',
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
            'startLine' => 1702,
            'endLine' => 1702,
            'startColumn' => 51,
            'endColumn' => 66,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'actual' => 
          array (
            'name' => 'actual',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'mixed',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1702,
            'endLine' => 1702,
            'startColumn' => 69,
            'endColumn' => 81,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 1702,
                'endLine' => 1702,
                'startTokenPos' => 7301,
                'startFilePos' => 51448,
                'endTokenPos' => 7301,
                'endFilePos' => 51449,
              ),
            ),
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
            'startLine' => 1702,
            'endLine' => 1702,
            'startColumn' => 84,
            'endColumn' => 103,
            'parameterIndex' => 2,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that a variable is of a given type.
 *
 * @template ExpectedType of object
 *
 * @param class-string<ExpectedType> $expected
 *
 * @throws Exception
 * @throws ExpectationFailedException
 * @throws UnknownClassOrInterfaceException
 *
 * @phpstan-assert =ExpectedType $actual
 */',
        'startLine' => 1702,
        'endLine' => 1713,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertNotInstanceOf' => 
      array (
        'name' => 'assertNotInstanceOf',
        'parameters' => 
        array (
          'expected' => 
          array (
            'name' => 'expected',
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
            'startLine' => 1727,
            'endLine' => 1727,
            'startColumn' => 54,
            'endColumn' => 69,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'actual' => 
          array (
            'name' => 'actual',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'mixed',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1727,
            'endLine' => 1727,
            'startColumn' => 72,
            'endColumn' => 84,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 1727,
                'endLine' => 1727,
                'startTokenPos' => 7393,
                'startFilePos' => 52148,
                'endTokenPos' => 7393,
                'endFilePos' => 52149,
              ),
            ),
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
            'startLine' => 1727,
            'endLine' => 1727,
            'startColumn' => 87,
            'endColumn' => 106,
            'parameterIndex' => 2,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that a variable is not of a given type.
 *
 * @template ExpectedType of object
 *
 * @param class-string<ExpectedType> $expected
 *
 * @throws Exception
 * @throws ExpectationFailedException
 *
 * @phpstan-assert !ExpectedType $actual
 */',
        'startLine' => 1727,
        'endLine' => 1740,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertIsArray' => 
      array (
        'name' => 'assertIsArray',
        'parameters' => 
        array (
          'actual' => 
          array (
            'name' => 'actual',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'mixed',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1750,
            'endLine' => 1750,
            'startColumn' => 48,
            'endColumn' => 60,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 1750,
                'endLine' => 1750,
                'startTokenPos' => 7488,
                'startFilePos' => 52759,
                'endTokenPos' => 7488,
                'endFilePos' => 52760,
              ),
            ),
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
            'startLine' => 1750,
            'endLine' => 1750,
            'startColumn' => 63,
            'endColumn' => 82,
            'parameterIndex' => 1,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that a variable is of type array.
 *
 * @throws Exception
 * @throws ExpectationFailedException
 *
 * @phpstan-assert array<mixed> $actual
 */',
        'startLine' => 1750,
        'endLine' => 1757,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertIsBool' => 
      array (
        'name' => 'assertIsBool',
        'parameters' => 
        array (
          'actual' => 
          array (
            'name' => 'actual',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'mixed',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1767,
            'endLine' => 1767,
            'startColumn' => 47,
            'endColumn' => 59,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 1767,
                'endLine' => 1767,
                'startTokenPos' => 7545,
                'startFilePos' => 53166,
                'endTokenPos' => 7545,
                'endFilePos' => 53167,
              ),
            ),
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
            'startLine' => 1767,
            'endLine' => 1767,
            'startColumn' => 62,
            'endColumn' => 81,
            'parameterIndex' => 1,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that a variable is of type bool.
 *
 * @throws Exception
 * @throws ExpectationFailedException
 *
 * @phpstan-assert bool $actual
 */',
        'startLine' => 1767,
        'endLine' => 1774,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertIsFloat' => 
      array (
        'name' => 'assertIsFloat',
        'parameters' => 
        array (
          'actual' => 
          array (
            'name' => 'actual',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'mixed',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1784,
            'endLine' => 1784,
            'startColumn' => 48,
            'endColumn' => 60,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 1784,
                'endLine' => 1784,
                'startTokenPos' => 7602,
                'startFilePos' => 53575,
                'endTokenPos' => 7602,
                'endFilePos' => 53576,
              ),
            ),
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
            'startLine' => 1784,
            'endLine' => 1784,
            'startColumn' => 63,
            'endColumn' => 82,
            'parameterIndex' => 1,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that a variable is of type float.
 *
 * @throws Exception
 * @throws ExpectationFailedException
 *
 * @phpstan-assert float $actual
 */',
        'startLine' => 1784,
        'endLine' => 1791,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertIsInt' => 
      array (
        'name' => 'assertIsInt',
        'parameters' => 
        array (
          'actual' => 
          array (
            'name' => 'actual',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'mixed',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1801,
            'endLine' => 1801,
            'startColumn' => 46,
            'endColumn' => 58,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 1801,
                'endLine' => 1801,
                'startTokenPos' => 7659,
                'startFilePos' => 53979,
                'endTokenPos' => 7659,
                'endFilePos' => 53980,
              ),
            ),
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
            'startLine' => 1801,
            'endLine' => 1801,
            'startColumn' => 61,
            'endColumn' => 80,
            'parameterIndex' => 1,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that a variable is of type int.
 *
 * @throws Exception
 * @throws ExpectationFailedException
 *
 * @phpstan-assert int $actual
 */',
        'startLine' => 1801,
        'endLine' => 1808,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertIsNumeric' => 
      array (
        'name' => 'assertIsNumeric',
        'parameters' => 
        array (
          'actual' => 
          array (
            'name' => 'actual',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'mixed',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1818,
            'endLine' => 1818,
            'startColumn' => 50,
            'endColumn' => 62,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 1818,
                'endLine' => 1818,
                'startTokenPos' => 7716,
                'startFilePos' => 54393,
                'endTokenPos' => 7716,
                'endFilePos' => 54394,
              ),
            ),
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
            'startLine' => 1818,
            'endLine' => 1818,
            'startColumn' => 65,
            'endColumn' => 84,
            'parameterIndex' => 1,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that a variable is of type numeric.
 *
 * @throws Exception
 * @throws ExpectationFailedException
 *
 * @phpstan-assert numeric $actual
 */',
        'startLine' => 1818,
        'endLine' => 1825,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertIsObject' => 
      array (
        'name' => 'assertIsObject',
        'parameters' => 
        array (
          'actual' => 
          array (
            'name' => 'actual',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'mixed',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1835,
            'endLine' => 1835,
            'startColumn' => 49,
            'endColumn' => 61,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 1835,
                'endLine' => 1835,
                'startTokenPos' => 7773,
                'startFilePos' => 54808,
                'endTokenPos' => 7773,
                'endFilePos' => 54809,
              ),
            ),
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
            'startLine' => 1835,
            'endLine' => 1835,
            'startColumn' => 64,
            'endColumn' => 83,
            'parameterIndex' => 1,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that a variable is of type object.
 *
 * @throws Exception
 * @throws ExpectationFailedException
 *
 * @phpstan-assert object $actual
 */',
        'startLine' => 1835,
        'endLine' => 1842,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertIsResource' => 
      array (
        'name' => 'assertIsResource',
        'parameters' => 
        array (
          'actual' => 
          array (
            'name' => 'actual',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'mixed',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1852,
            'endLine' => 1852,
            'startColumn' => 51,
            'endColumn' => 63,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 1852,
                'endLine' => 1852,
                'startTokenPos' => 7830,
                'startFilePos' => 55228,
                'endTokenPos' => 7830,
                'endFilePos' => 55229,
              ),
            ),
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
            'startLine' => 1852,
            'endLine' => 1852,
            'startColumn' => 66,
            'endColumn' => 85,
            'parameterIndex' => 1,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that a variable is of type resource.
 *
 * @throws Exception
 * @throws ExpectationFailedException
 *
 * @phpstan-assert resource $actual
 */',
        'startLine' => 1852,
        'endLine' => 1859,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertIsClosedResource' => 
      array (
        'name' => 'assertIsClosedResource',
        'parameters' => 
        array (
          'actual' => 
          array (
            'name' => 'actual',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'mixed',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1869,
            'endLine' => 1869,
            'startColumn' => 57,
            'endColumn' => 69,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 1869,
                'endLine' => 1869,
                'startTokenPos' => 7887,
                'startFilePos' => 55670,
                'endTokenPos' => 7887,
                'endFilePos' => 55671,
              ),
            ),
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
            'startLine' => 1869,
            'endLine' => 1869,
            'startColumn' => 72,
            'endColumn' => 91,
            'parameterIndex' => 1,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that a variable is of type resource and is closed.
 *
 * @throws Exception
 * @throws ExpectationFailedException
 *
 * @phpstan-assert resource $actual
 */',
        'startLine' => 1869,
        'endLine' => 1876,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertIsString' => 
      array (
        'name' => 'assertIsString',
        'parameters' => 
        array (
          'actual' => 
          array (
            'name' => 'actual',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'mixed',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1886,
            'endLine' => 1886,
            'startColumn' => 49,
            'endColumn' => 61,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 1886,
                'endLine' => 1886,
                'startTokenPos' => 7944,
                'startFilePos' => 56093,
                'endTokenPos' => 7944,
                'endFilePos' => 56094,
              ),
            ),
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
            'startLine' => 1886,
            'endLine' => 1886,
            'startColumn' => 64,
            'endColumn' => 83,
            'parameterIndex' => 1,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that a variable is of type string.
 *
 * @throws Exception
 * @throws ExpectationFailedException
 *
 * @phpstan-assert string $actual
 */',
        'startLine' => 1886,
        'endLine' => 1893,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertIsScalar' => 
      array (
        'name' => 'assertIsScalar',
        'parameters' => 
        array (
          'actual' => 
          array (
            'name' => 'actual',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'mixed',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1903,
            'endLine' => 1903,
            'startColumn' => 49,
            'endColumn' => 61,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 1903,
                'endLine' => 1903,
                'startTokenPos' => 8001,
                'startFilePos' => 56507,
                'endTokenPos' => 8001,
                'endFilePos' => 56508,
              ),
            ),
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
            'startLine' => 1903,
            'endLine' => 1903,
            'startColumn' => 64,
            'endColumn' => 83,
            'parameterIndex' => 1,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that a variable is of type scalar.
 *
 * @throws Exception
 * @throws ExpectationFailedException
 *
 * @phpstan-assert scalar $actual
 */',
        'startLine' => 1903,
        'endLine' => 1910,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertIsCallable' => 
      array (
        'name' => 'assertIsCallable',
        'parameters' => 
        array (
          'actual' => 
          array (
            'name' => 'actual',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'mixed',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1920,
            'endLine' => 1920,
            'startColumn' => 51,
            'endColumn' => 63,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 1920,
                'endLine' => 1920,
                'startTokenPos' => 8058,
                'startFilePos' => 56927,
                'endTokenPos' => 8058,
                'endFilePos' => 56928,
              ),
            ),
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
            'startLine' => 1920,
            'endLine' => 1920,
            'startColumn' => 66,
            'endColumn' => 85,
            'parameterIndex' => 1,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that a variable is of type callable.
 *
 * @throws Exception
 * @throws ExpectationFailedException
 *
 * @phpstan-assert callable $actual
 */',
        'startLine' => 1920,
        'endLine' => 1927,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertIsIterable' => 
      array (
        'name' => 'assertIsIterable',
        'parameters' => 
        array (
          'actual' => 
          array (
            'name' => 'actual',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'mixed',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1937,
            'endLine' => 1937,
            'startColumn' => 51,
            'endColumn' => 63,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 1937,
                'endLine' => 1937,
                'startTokenPos' => 8115,
                'startFilePos' => 57356,
                'endTokenPos' => 8115,
                'endFilePos' => 57357,
              ),
            ),
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
            'startLine' => 1937,
            'endLine' => 1937,
            'startColumn' => 66,
            'endColumn' => 85,
            'parameterIndex' => 1,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that a variable is of type iterable.
 *
 * @throws Exception
 * @throws ExpectationFailedException
 *
 * @phpstan-assert iterable<mixed> $actual
 */',
        'startLine' => 1937,
        'endLine' => 1944,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertIsNotArray' => 
      array (
        'name' => 'assertIsNotArray',
        'parameters' => 
        array (
          'actual' => 
          array (
            'name' => 'actual',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'mixed',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1954,
            'endLine' => 1954,
            'startColumn' => 51,
            'endColumn' => 63,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 1954,
                'endLine' => 1954,
                'startTokenPos' => 8172,
                'startFilePos' => 57784,
                'endTokenPos' => 8172,
                'endFilePos' => 57785,
              ),
            ),
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
            'startLine' => 1954,
            'endLine' => 1954,
            'startColumn' => 66,
            'endColumn' => 85,
            'parameterIndex' => 1,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that a variable is not of type array.
 *
 * @throws Exception
 * @throws ExpectationFailedException
 *
 * @phpstan-assert !array<mixed> $actual
 */',
        'startLine' => 1954,
        'endLine' => 1961,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertIsNotBool' => 
      array (
        'name' => 'assertIsNotBool',
        'parameters' => 
        array (
          'actual' => 
          array (
            'name' => 'actual',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'mixed',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1971,
            'endLine' => 1971,
            'startColumn' => 50,
            'endColumn' => 62,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 1971,
                'endLine' => 1971,
                'startTokenPos' => 8234,
                'startFilePos' => 58215,
                'endTokenPos' => 8234,
                'endFilePos' => 58216,
              ),
            ),
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
            'startLine' => 1971,
            'endLine' => 1971,
            'startColumn' => 65,
            'endColumn' => 84,
            'parameterIndex' => 1,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that a variable is not of type bool.
 *
 * @throws Exception
 * @throws ExpectationFailedException
 *
 * @phpstan-assert !bool $actual
 */',
        'startLine' => 1971,
        'endLine' => 1978,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertIsNotFloat' => 
      array (
        'name' => 'assertIsNotFloat',
        'parameters' => 
        array (
          'actual' => 
          array (
            'name' => 'actual',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'mixed',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1988,
            'endLine' => 1988,
            'startColumn' => 51,
            'endColumn' => 63,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 1988,
                'endLine' => 1988,
                'startTokenPos' => 8296,
                'startFilePos' => 58648,
                'endTokenPos' => 8296,
                'endFilePos' => 58649,
              ),
            ),
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
            'startLine' => 1988,
            'endLine' => 1988,
            'startColumn' => 66,
            'endColumn' => 85,
            'parameterIndex' => 1,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that a variable is not of type float.
 *
 * @throws Exception
 * @throws ExpectationFailedException
 *
 * @phpstan-assert !float $actual
 */',
        'startLine' => 1988,
        'endLine' => 1995,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertIsNotInt' => 
      array (
        'name' => 'assertIsNotInt',
        'parameters' => 
        array (
          'actual' => 
          array (
            'name' => 'actual',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'mixed',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 2005,
            'endLine' => 2005,
            'startColumn' => 49,
            'endColumn' => 61,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 2005,
                'endLine' => 2005,
                'startTokenPos' => 8358,
                'startFilePos' => 59076,
                'endTokenPos' => 8358,
                'endFilePos' => 59077,
              ),
            ),
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
            'startLine' => 2005,
            'endLine' => 2005,
            'startColumn' => 64,
            'endColumn' => 83,
            'parameterIndex' => 1,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that a variable is not of type int.
 *
 * @throws Exception
 * @throws ExpectationFailedException
 *
 * @phpstan-assert !int $actual
 */',
        'startLine' => 2005,
        'endLine' => 2012,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertIsNotNumeric' => 
      array (
        'name' => 'assertIsNotNumeric',
        'parameters' => 
        array (
          'actual' => 
          array (
            'name' => 'actual',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'mixed',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 2022,
            'endLine' => 2022,
            'startColumn' => 53,
            'endColumn' => 65,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 2022,
                'endLine' => 2022,
                'startTokenPos' => 8420,
                'startFilePos' => 59514,
                'endTokenPos' => 8420,
                'endFilePos' => 59515,
              ),
            ),
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
            'startLine' => 2022,
            'endLine' => 2022,
            'startColumn' => 68,
            'endColumn' => 87,
            'parameterIndex' => 1,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that a variable is not of type numeric.
 *
 * @throws Exception
 * @throws ExpectationFailedException
 *
 * @phpstan-assert !numeric $actual
 */',
        'startLine' => 2022,
        'endLine' => 2029,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertIsNotObject' => 
      array (
        'name' => 'assertIsNotObject',
        'parameters' => 
        array (
          'actual' => 
          array (
            'name' => 'actual',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'mixed',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 2039,
            'endLine' => 2039,
            'startColumn' => 52,
            'endColumn' => 64,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 2039,
                'endLine' => 2039,
                'startTokenPos' => 8482,
                'startFilePos' => 59953,
                'endTokenPos' => 8482,
                'endFilePos' => 59954,
              ),
            ),
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
            'startLine' => 2039,
            'endLine' => 2039,
            'startColumn' => 67,
            'endColumn' => 86,
            'parameterIndex' => 1,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that a variable is not of type object.
 *
 * @throws Exception
 * @throws ExpectationFailedException
 *
 * @phpstan-assert !object $actual
 */',
        'startLine' => 2039,
        'endLine' => 2046,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertIsNotResource' => 
      array (
        'name' => 'assertIsNotResource',
        'parameters' => 
        array (
          'actual' => 
          array (
            'name' => 'actual',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'mixed',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 2056,
            'endLine' => 2056,
            'startColumn' => 54,
            'endColumn' => 66,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 2056,
                'endLine' => 2056,
                'startTokenPos' => 8544,
                'startFilePos' => 60397,
                'endTokenPos' => 8544,
                'endFilePos' => 60398,
              ),
            ),
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
            'startLine' => 2056,
            'endLine' => 2056,
            'startColumn' => 69,
            'endColumn' => 88,
            'parameterIndex' => 1,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that a variable is not of type resource.
 *
 * @throws Exception
 * @throws ExpectationFailedException
 *
 * @phpstan-assert !resource $actual
 */',
        'startLine' => 2056,
        'endLine' => 2063,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertIsNotClosedResource' => 
      array (
        'name' => 'assertIsNotClosedResource',
        'parameters' => 
        array (
          'actual' => 
          array (
            'name' => 'actual',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'mixed',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 2073,
            'endLine' => 2073,
            'startColumn' => 60,
            'endColumn' => 72,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 2073,
                'endLine' => 2073,
                'startTokenPos' => 8606,
                'startFilePos' => 60849,
                'endTokenPos' => 8606,
                'endFilePos' => 60850,
              ),
            ),
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
            'startLine' => 2073,
            'endLine' => 2073,
            'startColumn' => 75,
            'endColumn' => 94,
            'parameterIndex' => 1,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that a variable is not of type resource.
 *
 * @throws Exception
 * @throws ExpectationFailedException
 *
 * @phpstan-assert !resource $actual
 */',
        'startLine' => 2073,
        'endLine' => 2080,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertIsNotString' => 
      array (
        'name' => 'assertIsNotString',
        'parameters' => 
        array (
          'actual' => 
          array (
            'name' => 'actual',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'mixed',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 2090,
            'endLine' => 2090,
            'startColumn' => 52,
            'endColumn' => 64,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 2090,
                'endLine' => 2090,
                'startTokenPos' => 8668,
                'startFilePos' => 61296,
                'endTokenPos' => 8668,
                'endFilePos' => 61297,
              ),
            ),
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
            'startLine' => 2090,
            'endLine' => 2090,
            'startColumn' => 67,
            'endColumn' => 86,
            'parameterIndex' => 1,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that a variable is not of type string.
 *
 * @throws Exception
 * @throws ExpectationFailedException
 *
 * @phpstan-assert !string $actual
 */',
        'startLine' => 2090,
        'endLine' => 2097,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertIsNotScalar' => 
      array (
        'name' => 'assertIsNotScalar',
        'parameters' => 
        array (
          'actual' => 
          array (
            'name' => 'actual',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'mixed',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 2107,
            'endLine' => 2107,
            'startColumn' => 52,
            'endColumn' => 64,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 2107,
                'endLine' => 2107,
                'startTokenPos' => 8730,
                'startFilePos' => 61734,
                'endTokenPos' => 8730,
                'endFilePos' => 61735,
              ),
            ),
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
            'startLine' => 2107,
            'endLine' => 2107,
            'startColumn' => 67,
            'endColumn' => 86,
            'parameterIndex' => 1,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that a variable is not of type scalar.
 *
 * @throws Exception
 * @throws ExpectationFailedException
 *
 * @phpstan-assert !scalar $actual
 */',
        'startLine' => 2107,
        'endLine' => 2114,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertIsNotCallable' => 
      array (
        'name' => 'assertIsNotCallable',
        'parameters' => 
        array (
          'actual' => 
          array (
            'name' => 'actual',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'mixed',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 2124,
            'endLine' => 2124,
            'startColumn' => 54,
            'endColumn' => 66,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 2124,
                'endLine' => 2124,
                'startTokenPos' => 8792,
                'startFilePos' => 62178,
                'endTokenPos' => 8792,
                'endFilePos' => 62179,
              ),
            ),
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
            'startLine' => 2124,
            'endLine' => 2124,
            'startColumn' => 69,
            'endColumn' => 88,
            'parameterIndex' => 1,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that a variable is not of type callable.
 *
 * @throws Exception
 * @throws ExpectationFailedException
 *
 * @phpstan-assert !callable $actual
 */',
        'startLine' => 2124,
        'endLine' => 2131,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertIsNotIterable' => 
      array (
        'name' => 'assertIsNotIterable',
        'parameters' => 
        array (
          'actual' => 
          array (
            'name' => 'actual',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'mixed',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 2141,
            'endLine' => 2141,
            'startColumn' => 54,
            'endColumn' => 66,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 2141,
                'endLine' => 2141,
                'startTokenPos' => 8854,
                'startFilePos' => 62631,
                'endTokenPos' => 8854,
                'endFilePos' => 62632,
              ),
            ),
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
            'startLine' => 2141,
            'endLine' => 2141,
            'startColumn' => 69,
            'endColumn' => 88,
            'parameterIndex' => 1,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that a variable is not of type iterable.
 *
 * @throws Exception
 * @throws ExpectationFailedException
 *
 * @phpstan-assert !iterable<mixed> $actual
 */',
        'startLine' => 2141,
        'endLine' => 2148,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertMatchesRegularExpression' => 
      array (
        'name' => 'assertMatchesRegularExpression',
        'parameters' => 
        array (
          'pattern' => 
          array (
            'name' => 'pattern',
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
            'startLine' => 2155,
            'endLine' => 2155,
            'startColumn' => 65,
            'endColumn' => 79,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'string' => 
          array (
            'name' => 'string',
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
            'startLine' => 2155,
            'endLine' => 2155,
            'startColumn' => 82,
            'endColumn' => 95,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 2155,
                'endLine' => 2155,
                'startTokenPos' => 8921,
                'startFilePos' => 63042,
                'endTokenPos' => 8921,
                'endFilePos' => 63043,
              ),
            ),
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
            'startLine' => 2155,
            'endLine' => 2155,
            'startColumn' => 98,
            'endColumn' => 117,
            'parameterIndex' => 2,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that a string matches a given regular expression.
 *
 * @throws ExpectationFailedException
 */',
        'startLine' => 2155,
        'endLine' => 2158,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertDoesNotMatchRegularExpression' => 
      array (
        'name' => 'assertDoesNotMatchRegularExpression',
        'parameters' => 
        array (
          'pattern' => 
          array (
            'name' => 'pattern',
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
            'startLine' => 2165,
            'endLine' => 2165,
            'startColumn' => 70,
            'endColumn' => 84,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'string' => 
          array (
            'name' => 'string',
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
            'startLine' => 2165,
            'endLine' => 2165,
            'startColumn' => 87,
            'endColumn' => 100,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 2165,
                'endLine' => 2165,
                'startTokenPos' => 8978,
                'startFilePos' => 63400,
                'endTokenPos' => 8978,
                'endFilePos' => 63401,
              ),
            ),
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
            'startLine' => 2165,
            'endLine' => 2165,
            'startColumn' => 103,
            'endColumn' => 122,
            'parameterIndex' => 2,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that a string does not match a given regular expression.
 *
 * @throws ExpectationFailedException
 */',
        'startLine' => 2165,
        'endLine' => 2174,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertSameSize' => 
      array (
        'name' => 'assertSameSize',
        'parameters' => 
        array (
          'expected' => 
          array (
            'name' => 'expected',
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
                      'name' => 'Countable',
                      'isIdentifier' => false,
                    ),
                  ),
                  1 => 
                  array (
                    'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                    'data' => 
                    array (
                      'name' => 'iterable',
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
            'startLine' => 2187,
            'endLine' => 2187,
            'startColumn' => 49,
            'endColumn' => 76,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'actual' => 
          array (
            'name' => 'actual',
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
                      'name' => 'Countable',
                      'isIdentifier' => false,
                    ),
                  ),
                  1 => 
                  array (
                    'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                    'data' => 
                    array (
                      'name' => 'iterable',
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
            'startLine' => 2187,
            'endLine' => 2187,
            'startColumn' => 79,
            'endColumn' => 104,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 2187,
                'endLine' => 2187,
                'startTokenPos' => 9050,
                'startFilePos' => 64064,
                'endTokenPos' => 9050,
                'endFilePos' => 64065,
              ),
            ),
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
            'startLine' => 2187,
            'endLine' => 2187,
            'startColumn' => 107,
            'endColumn' => 126,
            'parameterIndex' => 2,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Assert that the size of two arrays (or `Countable` or `Traversable` objects)
 * is the same.
 *
 * @param Countable|iterable<mixed> $expected
 * @param Countable|iterable<mixed> $actual
 *
 * @throws Exception
 * @throws ExpectationFailedException
 * @throws GeneratorNotSupportedException
 */',
        'startLine' => 2187,
        'endLine' => 2202,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertNotSameSize' => 
      array (
        'name' => 'assertNotSameSize',
        'parameters' => 
        array (
          'expected' => 
          array (
            'name' => 'expected',
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
                      'name' => 'Countable',
                      'isIdentifier' => false,
                    ),
                  ),
                  1 => 
                  array (
                    'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                    'data' => 
                    array (
                      'name' => 'iterable',
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
            'startLine' => 2215,
            'endLine' => 2215,
            'startColumn' => 52,
            'endColumn' => 79,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'actual' => 
          array (
            'name' => 'actual',
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
                      'name' => 'Countable',
                      'isIdentifier' => false,
                    ),
                  ),
                  1 => 
                  array (
                    'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                    'data' => 
                    array (
                      'name' => 'iterable',
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
            'startLine' => 2215,
            'endLine' => 2215,
            'startColumn' => 82,
            'endColumn' => 107,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 2215,
                'endLine' => 2215,
                'startTokenPos' => 9162,
                'startFilePos' => 64954,
                'endTokenPos' => 9162,
                'endFilePos' => 64955,
              ),
            ),
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
            'startLine' => 2215,
            'endLine' => 2215,
            'startColumn' => 110,
            'endColumn' => 129,
            'parameterIndex' => 2,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Assert that the size of two arrays (or `Countable` or `Traversable` objects)
 * is not the same.
 *
 * @param Countable|iterable<mixed> $expected
 * @param Countable|iterable<mixed> $actual
 *
 * @throws Exception
 * @throws ExpectationFailedException
 * @throws GeneratorNotSupportedException
 */',
        'startLine' => 2215,
        'endLine' => 2232,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertStringContainsStringIgnoringLineEndings' => 
      array (
        'name' => 'assertStringContainsStringIgnoringLineEndings',
        'parameters' => 
        array (
          'needle' => 
          array (
            'name' => 'needle',
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
            'startLine' => 2237,
            'endLine' => 2237,
            'startColumn' => 80,
            'endColumn' => 93,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'haystack' => 
          array (
            'name' => 'haystack',
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
            'startLine' => 2237,
            'endLine' => 2237,
            'startColumn' => 96,
            'endColumn' => 111,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 2237,
                'endLine' => 2237,
                'startTokenPos' => 9278,
                'startFilePos' => 65604,
                'endTokenPos' => 9278,
                'endFilePos' => 65605,
              ),
            ),
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
            'startLine' => 2237,
            'endLine' => 2237,
            'startColumn' => 114,
            'endColumn' => 133,
            'parameterIndex' => 2,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * @throws ExpectationFailedException
 */',
        'startLine' => 2237,
        'endLine' => 2240,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertStringEqualsStringIgnoringLineEndings' => 
      array (
        'name' => 'assertStringEqualsStringIgnoringLineEndings',
        'parameters' => 
        array (
          'expected' => 
          array (
            'name' => 'expected',
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
            'startLine' => 2247,
            'endLine' => 2247,
            'startColumn' => 78,
            'endColumn' => 93,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'actual' => 
          array (
            'name' => 'actual',
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
            'startLine' => 2247,
            'endLine' => 2247,
            'startColumn' => 96,
            'endColumn' => 109,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 2247,
                'endLine' => 2247,
                'startTokenPos' => 9341,
                'startFilePos' => 65977,
                'endTokenPos' => 9341,
                'endFilePos' => 65978,
              ),
            ),
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
            'startLine' => 2247,
            'endLine' => 2247,
            'startColumn' => 112,
            'endColumn' => 131,
            'parameterIndex' => 2,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that two strings are equal except for line endings.
 *
 * @throws ExpectationFailedException
 */',
        'startLine' => 2247,
        'endLine' => 2250,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertFileMatchesFormat' => 
      array (
        'name' => 'assertFileMatchesFormat',
        'parameters' => 
        array (
          'format' => 
          array (
            'name' => 'format',
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
            'startLine' => 2257,
            'endLine' => 2257,
            'startColumn' => 58,
            'endColumn' => 71,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'actualFile' => 
          array (
            'name' => 'actualFile',
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
            'startLine' => 2257,
            'endLine' => 2257,
            'startColumn' => 74,
            'endColumn' => 91,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 2257,
                'endLine' => 2257,
                'startTokenPos' => 9398,
                'startFilePos' => 66335,
                'endTokenPos' => 9398,
                'endFilePos' => 66336,
              ),
            ),
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
            'startLine' => 2257,
            'endLine' => 2257,
            'startColumn' => 94,
            'endColumn' => 113,
            'parameterIndex' => 2,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that a string matches a given format string.
 *
 * @throws ExpectationFailedException
 */',
        'startLine' => 2257,
        'endLine' => 2266,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertFileMatchesFormatFile' => 
      array (
        'name' => 'assertFileMatchesFormatFile',
        'parameters' => 
        array (
          'formatFile' => 
          array (
            'name' => 'formatFile',
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
            'startLine' => 2273,
            'endLine' => 2273,
            'startColumn' => 62,
            'endColumn' => 79,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'actualFile' => 
          array (
            'name' => 'actualFile',
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
            'startLine' => 2273,
            'endLine' => 2273,
            'startColumn' => 82,
            'endColumn' => 99,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 2273,
                'endLine' => 2273,
                'startTokenPos' => 9472,
                'startFilePos' => 66818,
                'endTokenPos' => 9472,
                'endFilePos' => 66819,
              ),
            ),
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
            'startLine' => 2273,
            'endLine' => 2273,
            'startColumn' => 102,
            'endColumn' => 121,
            'parameterIndex' => 2,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that a string matches a given format string.
 *
 * @throws ExpectationFailedException
 */',
        'startLine' => 2273,
        'endLine' => 2287,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertStringMatchesFormat' => 
      array (
        'name' => 'assertStringMatchesFormat',
        'parameters' => 
        array (
          'format' => 
          array (
            'name' => 'format',
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
            'startLine' => 2294,
            'endLine' => 2294,
            'startColumn' => 60,
            'endColumn' => 73,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'string' => 
          array (
            'name' => 'string',
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
            'startLine' => 2294,
            'endLine' => 2294,
            'startColumn' => 76,
            'endColumn' => 89,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 2294,
                'endLine' => 2294,
                'startTokenPos' => 9575,
                'startFilePos' => 67470,
                'endTokenPos' => 9575,
                'endFilePos' => 67471,
              ),
            ),
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
            'startLine' => 2294,
            'endLine' => 2294,
            'startColumn' => 92,
            'endColumn' => 111,
            'parameterIndex' => 2,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that a string matches a given format string.
 *
 * @throws ExpectationFailedException
 */',
        'startLine' => 2294,
        'endLine' => 2297,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertStringNotMatchesFormat' => 
      array (
        'name' => 'assertStringNotMatchesFormat',
        'parameters' => 
        array (
          'format' => 
          array (
            'name' => 'format',
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
            'startLine' => 2306,
            'endLine' => 2306,
            'startColumn' => 63,
            'endColumn' => 76,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'string' => 
          array (
            'name' => 'string',
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
            'startLine' => 2306,
            'endLine' => 2306,
            'startColumn' => 79,
            'endColumn' => 92,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 2306,
                'endLine' => 2306,
                'startTokenPos' => 9632,
                'startFilePos' => 67910,
                'endTokenPos' => 9632,
                'endFilePos' => 67911,
              ),
            ),
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
            'startLine' => 2306,
            'endLine' => 2306,
            'startColumn' => 95,
            'endColumn' => 114,
            'parameterIndex' => 2,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that a string does not match a given format string.
 *
 * @throws ExpectationFailedException
 *
 * @deprecated https://github.com/sebastianbergmann/phpunit/issues/5472
 */',
        'startLine' => 2306,
        'endLine' => 2320,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertStringMatchesFormatFile' => 
      array (
        'name' => 'assertStringMatchesFormatFile',
        'parameters' => 
        array (
          'formatFile' => 
          array (
            'name' => 'formatFile',
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
            'startLine' => 2327,
            'endLine' => 2327,
            'startColumn' => 64,
            'endColumn' => 81,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'string' => 
          array (
            'name' => 'string',
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
            'startLine' => 2327,
            'endLine' => 2327,
            'startColumn' => 84,
            'endColumn' => 97,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 2327,
                'endLine' => 2327,
                'startTokenPos' => 9718,
                'startFilePos' => 68568,
                'endTokenPos' => 9718,
                'endFilePos' => 68569,
              ),
            ),
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
            'startLine' => 2327,
            'endLine' => 2327,
            'startColumn' => 100,
            'endColumn' => 119,
            'parameterIndex' => 2,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that a string matches a given format file.
 *
 * @throws ExpectationFailedException
 */',
        'startLine' => 2327,
        'endLine' => 2342,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertStringNotMatchesFormatFile' => 
      array (
        'name' => 'assertStringNotMatchesFormatFile',
        'parameters' => 
        array (
          'formatFile' => 
          array (
            'name' => 'formatFile',
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
            'startLine' => 2351,
            'endLine' => 2351,
            'startColumn' => 67,
            'endColumn' => 84,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'string' => 
          array (
            'name' => 'string',
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
            'startLine' => 2351,
            'endLine' => 2351,
            'startColumn' => 87,
            'endColumn' => 100,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 2351,
                'endLine' => 2351,
                'startTokenPos' => 9810,
                'startFilePos' => 69274,
                'endTokenPos' => 9810,
                'endFilePos' => 69275,
              ),
            ),
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
            'startLine' => 2351,
            'endLine' => 2351,
            'startColumn' => 103,
            'endColumn' => 122,
            'parameterIndex' => 2,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that a string does not match a given format string.
 *
 * @throws ExpectationFailedException
 *
 * @deprecated https://github.com/sebastianbergmann/phpunit/issues/5472
 */',
        'startLine' => 2351,
        'endLine' => 2373,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertStringStartsWith' => 
      array (
        'name' => 'assertStringStartsWith',
        'parameters' => 
        array (
          'prefix' => 
          array (
            'name' => 'prefix',
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
            'startLine' => 2383,
            'endLine' => 2383,
            'startColumn' => 57,
            'endColumn' => 70,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'string' => 
          array (
            'name' => 'string',
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
            'startLine' => 2383,
            'endLine' => 2383,
            'startColumn' => 73,
            'endColumn' => 86,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 2383,
                'endLine' => 2383,
                'startTokenPos' => 9928,
                'startFilePos' => 70229,
                'endTokenPos' => 9928,
                'endFilePos' => 70230,
              ),
            ),
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
            'startLine' => 2383,
            'endLine' => 2383,
            'startColumn' => 89,
            'endColumn' => 108,
            'parameterIndex' => 2,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that a string starts with a given prefix.
 *
 * @param non-empty-string $prefix
 *
 * @throws ExpectationFailedException
 * @throws InvalidArgumentException
 */',
        'startLine' => 2383,
        'endLine' => 2386,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertStringStartsNotWith' => 
      array (
        'name' => 'assertStringStartsNotWith',
        'parameters' => 
        array (
          'prefix' => 
          array (
            'name' => 'prefix',
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
            'startLine' => 2396,
            'endLine' => 2396,
            'startColumn' => 60,
            'endColumn' => 73,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'string' => 
          array (
            'name' => 'string',
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
            'startLine' => 2396,
            'endLine' => 2396,
            'startColumn' => 76,
            'endColumn' => 89,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 2396,
                'endLine' => 2396,
                'startTokenPos' => 9985,
                'startFilePos' => 70649,
                'endTokenPos' => 9985,
                'endFilePos' => 70650,
              ),
            ),
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
            'startLine' => 2396,
            'endLine' => 2396,
            'startColumn' => 92,
            'endColumn' => 111,
            'parameterIndex' => 2,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that a string starts not with a given prefix.
 *
 * @param non-empty-string $prefix
 *
 * @throws ExpectationFailedException
 * @throws InvalidArgumentException
 */',
        'startLine' => 2396,
        'endLine' => 2405,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertStringContainsString' => 
      array (
        'name' => 'assertStringContainsString',
        'parameters' => 
        array (
          'needle' => 
          array (
            'name' => 'needle',
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
            'startLine' => 2410,
            'endLine' => 2410,
            'startColumn' => 61,
            'endColumn' => 74,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'haystack' => 
          array (
            'name' => 'haystack',
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
            'startLine' => 2410,
            'endLine' => 2410,
            'startColumn' => 77,
            'endColumn' => 92,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 2410,
                'endLine' => 2410,
                'startTokenPos' => 10053,
                'startFilePos' => 71012,
                'endTokenPos' => 10053,
                'endFilePos' => 71013,
              ),
            ),
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
            'startLine' => 2410,
            'endLine' => 2410,
            'startColumn' => 95,
            'endColumn' => 114,
            'parameterIndex' => 2,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * @throws ExpectationFailedException
 */',
        'startLine' => 2410,
        'endLine' => 2415,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertStringContainsStringIgnoringCase' => 
      array (
        'name' => 'assertStringContainsStringIgnoringCase',
        'parameters' => 
        array (
          'needle' => 
          array (
            'name' => 'needle',
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
            'startLine' => 2420,
            'endLine' => 2420,
            'startColumn' => 73,
            'endColumn' => 86,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'haystack' => 
          array (
            'name' => 'haystack',
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
            'startLine' => 2420,
            'endLine' => 2420,
            'startColumn' => 89,
            'endColumn' => 104,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 2420,
                'endLine' => 2420,
                'startTokenPos' => 10117,
                'startFilePos' => 71329,
                'endTokenPos' => 10117,
                'endFilePos' => 71330,
              ),
            ),
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
            'startLine' => 2420,
            'endLine' => 2420,
            'startColumn' => 107,
            'endColumn' => 126,
            'parameterIndex' => 2,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * @throws ExpectationFailedException
 */',
        'startLine' => 2420,
        'endLine' => 2425,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertStringNotContainsString' => 
      array (
        'name' => 'assertStringNotContainsString',
        'parameters' => 
        array (
          'needle' => 
          array (
            'name' => 'needle',
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
            'startLine' => 2430,
            'endLine' => 2430,
            'startColumn' => 64,
            'endColumn' => 77,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'haystack' => 
          array (
            'name' => 'haystack',
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
            'startLine' => 2430,
            'endLine' => 2430,
            'startColumn' => 80,
            'endColumn' => 95,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 2430,
                'endLine' => 2430,
                'startTokenPos' => 10184,
                'startFilePos' => 71643,
                'endTokenPos' => 10184,
                'endFilePos' => 71644,
              ),
            ),
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
            'startLine' => 2430,
            'endLine' => 2430,
            'startColumn' => 98,
            'endColumn' => 117,
            'parameterIndex' => 2,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * @throws ExpectationFailedException
 */',
        'startLine' => 2430,
        'endLine' => 2435,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertStringNotContainsStringIgnoringCase' => 
      array (
        'name' => 'assertStringNotContainsStringIgnoringCase',
        'parameters' => 
        array (
          'needle' => 
          array (
            'name' => 'needle',
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
            'startLine' => 2440,
            'endLine' => 2440,
            'startColumn' => 76,
            'endColumn' => 89,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'haystack' => 
          array (
            'name' => 'haystack',
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
            'startLine' => 2440,
            'endLine' => 2440,
            'startColumn' => 92,
            'endColumn' => 107,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 2440,
                'endLine' => 2440,
                'startTokenPos' => 10253,
                'startFilePos' => 71979,
                'endTokenPos' => 10253,
                'endFilePos' => 71980,
              ),
            ),
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
            'startLine' => 2440,
            'endLine' => 2440,
            'startColumn' => 110,
            'endColumn' => 129,
            'parameterIndex' => 2,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * @throws ExpectationFailedException
 */',
        'startLine' => 2440,
        'endLine' => 2445,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertStringEndsWith' => 
      array (
        'name' => 'assertStringEndsWith',
        'parameters' => 
        array (
          'suffix' => 
          array (
            'name' => 'suffix',
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
            'startLine' => 2455,
            'endLine' => 2455,
            'startColumn' => 55,
            'endColumn' => 68,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'string' => 
          array (
            'name' => 'string',
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
            'startLine' => 2455,
            'endLine' => 2455,
            'startColumn' => 71,
            'endColumn' => 84,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 2455,
                'endLine' => 2455,
                'startTokenPos' => 10325,
                'startFilePos' => 72446,
                'endTokenPos' => 10325,
                'endFilePos' => 72447,
              ),
            ),
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
            'startLine' => 2455,
            'endLine' => 2455,
            'startColumn' => 87,
            'endColumn' => 106,
            'parameterIndex' => 2,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that a string ends with a given suffix.
 *
 * @param non-empty-string $suffix
 *
 * @throws ExpectationFailedException
 * @throws InvalidArgumentException
 */',
        'startLine' => 2455,
        'endLine' => 2458,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertStringEndsNotWith' => 
      array (
        'name' => 'assertStringEndsNotWith',
        'parameters' => 
        array (
          'suffix' => 
          array (
            'name' => 'suffix',
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
            'startLine' => 2468,
            'endLine' => 2468,
            'startColumn' => 58,
            'endColumn' => 71,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'string' => 
          array (
            'name' => 'string',
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
            'startLine' => 2468,
            'endLine' => 2468,
            'startColumn' => 74,
            'endColumn' => 87,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 2468,
                'endLine' => 2468,
                'startTokenPos' => 10382,
                'startFilePos' => 72860,
                'endTokenPos' => 10382,
                'endFilePos' => 72861,
              ),
            ),
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
            'startLine' => 2468,
            'endLine' => 2468,
            'startColumn' => 90,
            'endColumn' => 109,
            'parameterIndex' => 2,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that a string ends not with a given suffix.
 *
 * @param non-empty-string $suffix
 *
 * @throws ExpectationFailedException
 * @throws InvalidArgumentException
 */',
        'startLine' => 2468,
        'endLine' => 2477,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertXmlFileEqualsXmlFile' => 
      array (
        'name' => 'assertXmlFileEqualsXmlFile',
        'parameters' => 
        array (
          'expectedFile' => 
          array (
            'name' => 'expectedFile',
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
            'startLine' => 2486,
            'endLine' => 2486,
            'startColumn' => 61,
            'endColumn' => 80,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'actualFile' => 
          array (
            'name' => 'actualFile',
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
            'startLine' => 2486,
            'endLine' => 2486,
            'startColumn' => 83,
            'endColumn' => 100,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 2486,
                'endLine' => 2486,
                'startTokenPos' => 10450,
                'startFilePos' => 73334,
                'endTokenPos' => 10450,
                'endFilePos' => 73335,
              ),
            ),
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
            'startLine' => 2486,
            'endLine' => 2486,
            'startColumn' => 103,
            'endColumn' => 122,
            'parameterIndex' => 2,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that two XML files are equal.
 *
 * @throws Exception
 * @throws ExpectationFailedException
 * @throws XmlException
 */',
        'startLine' => 2486,
        'endLine' => 2492,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertXmlFileNotEqualsXmlFile' => 
      array (
        'name' => 'assertXmlFileNotEqualsXmlFile',
        'parameters' => 
        array (
          'expectedFile' => 
          array (
            'name' => 'expectedFile',
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
            'startLine' => 2500,
            'endLine' => 2500,
            'startColumn' => 64,
            'endColumn' => 83,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'actualFile' => 
          array (
            'name' => 'actualFile',
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
            'startLine' => 2500,
            'endLine' => 2500,
            'startColumn' => 86,
            'endColumn' => 103,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 2500,
                'endLine' => 2500,
                'startTokenPos' => 10534,
                'startFilePos' => 73814,
                'endTokenPos' => 10534,
                'endFilePos' => 73815,
              ),
            ),
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
            'startLine' => 2500,
            'endLine' => 2500,
            'startColumn' => 106,
            'endColumn' => 125,
            'parameterIndex' => 2,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that two XML files are not equal.
 *
 * @throws \\PHPUnit\\Util\\Exception
 * @throws ExpectationFailedException
 */',
        'startLine' => 2500,
        'endLine' => 2506,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertXmlStringEqualsXmlFile' => 
      array (
        'name' => 'assertXmlStringEqualsXmlFile',
        'parameters' => 
        array (
          'expectedFile' => 
          array (
            'name' => 'expectedFile',
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
            'startLine' => 2514,
            'endLine' => 2514,
            'startColumn' => 63,
            'endColumn' => 82,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'actualXml' => 
          array (
            'name' => 'actualXml',
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
            'startLine' => 2514,
            'endLine' => 2514,
            'startColumn' => 85,
            'endColumn' => 101,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 2514,
                'endLine' => 2514,
                'startTokenPos' => 10618,
                'startFilePos' => 74284,
                'endTokenPos' => 10618,
                'endFilePos' => 74285,
              ),
            ),
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
            'startLine' => 2514,
            'endLine' => 2514,
            'startColumn' => 104,
            'endColumn' => 123,
            'parameterIndex' => 2,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that two XML documents are equal.
 *
 * @throws ExpectationFailedException
 * @throws XmlException
 */',
        'startLine' => 2514,
        'endLine' => 2520,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertXmlStringNotEqualsXmlFile' => 
      array (
        'name' => 'assertXmlStringNotEqualsXmlFile',
        'parameters' => 
        array (
          'expectedFile' => 
          array (
            'name' => 'expectedFile',
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
            'startLine' => 2528,
            'endLine' => 2528,
            'startColumn' => 66,
            'endColumn' => 85,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'actualXml' => 
          array (
            'name' => 'actualXml',
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
            'startLine' => 2528,
            'endLine' => 2528,
            'startColumn' => 88,
            'endColumn' => 104,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 2528,
                'endLine' => 2528,
                'startTokenPos' => 10702,
                'startFilePos' => 74753,
                'endTokenPos' => 10702,
                'endFilePos' => 74754,
              ),
            ),
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
            'startLine' => 2528,
            'endLine' => 2528,
            'startColumn' => 107,
            'endColumn' => 126,
            'parameterIndex' => 2,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that two XML documents are not equal.
 *
 * @throws ExpectationFailedException
 * @throws XmlException
 */',
        'startLine' => 2528,
        'endLine' => 2534,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertXmlStringEqualsXmlString' => 
      array (
        'name' => 'assertXmlStringEqualsXmlString',
        'parameters' => 
        array (
          'expectedXml' => 
          array (
            'name' => 'expectedXml',
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
            'startLine' => 2542,
            'endLine' => 2542,
            'startColumn' => 65,
            'endColumn' => 83,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'actualXml' => 
          array (
            'name' => 'actualXml',
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
            'startLine' => 2542,
            'endLine' => 2542,
            'startColumn' => 86,
            'endColumn' => 102,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 2542,
                'endLine' => 2542,
                'startTokenPos' => 10786,
                'startFilePos' => 75219,
                'endTokenPos' => 10786,
                'endFilePos' => 75220,
              ),
            ),
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
            'startLine' => 2542,
            'endLine' => 2542,
            'startColumn' => 105,
            'endColumn' => 124,
            'parameterIndex' => 2,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that two XML documents are equal.
 *
 * @throws ExpectationFailedException
 * @throws XmlException
 */',
        'startLine' => 2542,
        'endLine' => 2548,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertXmlStringNotEqualsXmlString' => 
      array (
        'name' => 'assertXmlStringNotEqualsXmlString',
        'parameters' => 
        array (
          'expectedXml' => 
          array (
            'name' => 'expectedXml',
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
            'startLine' => 2556,
            'endLine' => 2556,
            'startColumn' => 68,
            'endColumn' => 86,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'actualXml' => 
          array (
            'name' => 'actualXml',
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
            'startLine' => 2556,
            'endLine' => 2556,
            'startColumn' => 89,
            'endColumn' => 105,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 2556,
                'endLine' => 2556,
                'startTokenPos' => 10870,
                'startFilePos' => 75684,
                'endTokenPos' => 10870,
                'endFilePos' => 75685,
              ),
            ),
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
            'startLine' => 2556,
            'endLine' => 2556,
            'startColumn' => 108,
            'endColumn' => 127,
            'parameterIndex' => 2,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that two XML documents are not equal.
 *
 * @throws ExpectationFailedException
 * @throws XmlException
 */',
        'startLine' => 2556,
        'endLine' => 2562,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertThat' => 
      array (
        'name' => 'assertThat',
        'parameters' => 
        array (
          'value' => 
          array (
            'name' => 'value',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'mixed',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 2569,
            'endLine' => 2569,
            'startColumn' => 45,
            'endColumn' => 56,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'constraint' => 
          array (
            'name' => 'constraint',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPUnit\\Framework\\Constraint\\Constraint',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 2569,
            'endLine' => 2569,
            'startColumn' => 59,
            'endColumn' => 80,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 2569,
                'endLine' => 2569,
                'startTokenPos' => 10954,
                'startFilePos' => 76110,
                'endTokenPos' => 10954,
                'endFilePos' => 76111,
              ),
            ),
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
            'startLine' => 2569,
            'endLine' => 2569,
            'startColumn' => 83,
            'endColumn' => 102,
            'parameterIndex' => 2,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Evaluates a PHPUnit\\Framework\\Constraint matcher object.
 *
 * @throws ExpectationFailedException
 */',
        'startLine' => 2569,
        'endLine' => 2574,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertJson' => 
      array (
        'name' => 'assertJson',
        'parameters' => 
        array (
          'actual' => 
          array (
            'name' => 'actual',
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
            'startLine' => 2581,
            'endLine' => 2581,
            'startColumn' => 45,
            'endColumn' => 58,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 2581,
                'endLine' => 2581,
                'startTokenPos' => 11010,
                'startFilePos' => 76423,
                'endTokenPos' => 11010,
                'endFilePos' => 76424,
              ),
            ),
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
            'startLine' => 2581,
            'endLine' => 2581,
            'startColumn' => 61,
            'endColumn' => 80,
            'parameterIndex' => 1,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that a string is a valid JSON string.
 *
 * @throws ExpectationFailedException
 */',
        'startLine' => 2581,
        'endLine' => 2584,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertJsonStringEqualsJsonString' => 
      array (
        'name' => 'assertJsonStringEqualsJsonString',
        'parameters' => 
        array (
          'expectedJson' => 
          array (
            'name' => 'expectedJson',
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
            'startLine' => 2591,
            'endLine' => 2591,
            'startColumn' => 67,
            'endColumn' => 86,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'actualJson' => 
          array (
            'name' => 'actualJson',
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
            'startLine' => 2591,
            'endLine' => 2591,
            'startColumn' => 89,
            'endColumn' => 106,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 2591,
                'endLine' => 2591,
                'startTokenPos' => 11066,
                'startFilePos' => 76770,
                'endTokenPos' => 11066,
                'endFilePos' => 76771,
              ),
            ),
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
            'startLine' => 2591,
            'endLine' => 2591,
            'startColumn' => 109,
            'endColumn' => 128,
            'parameterIndex' => 2,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that two given JSON encoded objects or arrays are equal.
 *
 * @throws ExpectationFailedException
 */',
        'startLine' => 2591,
        'endLine' => 2597,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertJsonStringNotEqualsJsonString' => 
      array (
        'name' => 'assertJsonStringNotEqualsJsonString',
        'parameters' => 
        array (
          'expectedJson' => 
          array (
            'name' => 'expectedJson',
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
            'startLine' => 2604,
            'endLine' => 2604,
            'startColumn' => 70,
            'endColumn' => 89,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'actualJson' => 
          array (
            'name' => 'actualJson',
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
            'startLine' => 2604,
            'endLine' => 2604,
            'startColumn' => 92,
            'endColumn' => 109,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 2604,
                'endLine' => 2604,
                'startTokenPos' => 11145,
                'startFilePos' => 77245,
                'endTokenPos' => 11145,
                'endFilePos' => 77246,
              ),
            ),
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
            'startLine' => 2604,
            'endLine' => 2604,
            'startColumn' => 112,
            'endColumn' => 131,
            'parameterIndex' => 2,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that two given JSON encoded objects or arrays are not equal.
 *
 * @throws ExpectationFailedException
 */',
        'startLine' => 2604,
        'endLine' => 2616,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertJsonStringEqualsJsonFile' => 
      array (
        'name' => 'assertJsonStringEqualsJsonFile',
        'parameters' => 
        array (
          'expectedFile' => 
          array (
            'name' => 'expectedFile',
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
            'startLine' => 2623,
            'endLine' => 2623,
            'startColumn' => 65,
            'endColumn' => 84,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'actualJson' => 
          array (
            'name' => 'actualJson',
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
            'startLine' => 2623,
            'endLine' => 2623,
            'startColumn' => 87,
            'endColumn' => 104,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 2623,
                'endLine' => 2623,
                'startTokenPos' => 11235,
                'startFilePos' => 77832,
                'endTokenPos' => 11235,
                'endFilePos' => 77833,
              ),
            ),
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
            'startLine' => 2623,
            'endLine' => 2623,
            'startColumn' => 107,
            'endColumn' => 126,
            'parameterIndex' => 2,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that the generated JSON encoded object and the content of the given file are equal.
 *
 * @throws ExpectationFailedException
 */',
        'startLine' => 2623,
        'endLine' => 2634,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertJsonStringNotEqualsJsonFile' => 
      array (
        'name' => 'assertJsonStringNotEqualsJsonFile',
        'parameters' => 
        array (
          'expectedFile' => 
          array (
            'name' => 'expectedFile',
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
            'startLine' => 2641,
            'endLine' => 2641,
            'startColumn' => 68,
            'endColumn' => 87,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'actualJson' => 
          array (
            'name' => 'actualJson',
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
            'startLine' => 2641,
            'endLine' => 2641,
            'startColumn' => 90,
            'endColumn' => 107,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 2641,
                'endLine' => 2641,
                'startTokenPos' => 11343,
                'startFilePos' => 78494,
                'endTokenPos' => 11343,
                'endFilePos' => 78495,
              ),
            ),
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
            'startLine' => 2641,
            'endLine' => 2641,
            'startColumn' => 110,
            'endColumn' => 129,
            'parameterIndex' => 2,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that the generated JSON encoded object and the content of the given file are not equal.
 *
 * @throws ExpectationFailedException
 */',
        'startLine' => 2641,
        'endLine' => 2658,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertJsonFileEqualsJsonFile' => 
      array (
        'name' => 'assertJsonFileEqualsJsonFile',
        'parameters' => 
        array (
          'expectedFile' => 
          array (
            'name' => 'expectedFile',
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
            'startLine' => 2665,
            'endLine' => 2665,
            'startColumn' => 63,
            'endColumn' => 82,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'actualFile' => 
          array (
            'name' => 'actualFile',
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
            'startLine' => 2665,
            'endLine' => 2665,
            'startColumn' => 85,
            'endColumn' => 102,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 2665,
                'endLine' => 2665,
                'startTokenPos' => 11462,
                'startFilePos' => 79188,
                'endTokenPos' => 11462,
                'endFilePos' => 79189,
              ),
            ),
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
            'startLine' => 2665,
            'endLine' => 2665,
            'startColumn' => 105,
            'endColumn' => 124,
            'parameterIndex' => 2,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that two JSON files are equal.
 *
 * @throws ExpectationFailedException
 */',
        'startLine' => 2665,
        'endLine' => 2682,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'assertJsonFileNotEqualsJsonFile' => 
      array (
        'name' => 'assertJsonFileNotEqualsJsonFile',
        'parameters' => 
        array (
          'expectedFile' => 
          array (
            'name' => 'expectedFile',
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
            'startLine' => 2689,
            'endLine' => 2689,
            'startColumn' => 66,
            'endColumn' => 85,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'actualFile' => 
          array (
            'name' => 'actualFile',
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
            'startLine' => 2689,
            'endLine' => 2689,
            'startColumn' => 88,
            'endColumn' => 105,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 2689,
                'endLine' => 2689,
                'startTokenPos' => 11599,
                'startFilePos' => 79950,
                'endTokenPos' => 11599,
                'endFilePos' => 79951,
              ),
            ),
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
            'startLine' => 2689,
            'endLine' => 2689,
            'startColumn' => 108,
            'endColumn' => 127,
            'parameterIndex' => 2,
            'isOptional' => true,
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
        ),
        'docComment' => '/**
 * Asserts that two JSON files are not equal.
 *
 * @throws ExpectationFailedException
 */',
        'startLine' => 2689,
        'endLine' => 2706,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'logicalAnd' => 
      array (
        'name' => 'logicalAnd',
        'parameters' => 
        array (
          'constraints' => 
          array (
            'name' => 'constraints',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'mixed',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => true,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 2711,
            'endLine' => 2711,
            'startColumn' => 45,
            'endColumn' => 65,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPUnit\\Framework\\Constraint\\LogicalAnd',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @throws Exception
 */',
        'startLine' => 2711,
        'endLine' => 2714,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => true,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'logicalOr' => 
      array (
        'name' => 'logicalOr',
        'parameters' => 
        array (
          'constraints' => 
          array (
            'name' => 'constraints',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'mixed',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => true,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 2716,
            'endLine' => 2716,
            'startColumn' => 44,
            'endColumn' => 64,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPUnit\\Framework\\Constraint\\LogicalOr',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 2716,
        'endLine' => 2719,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => true,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'logicalNot' => 
      array (
        'name' => 'logicalNot',
        'parameters' => 
        array (
          'constraint' => 
          array (
            'name' => 'constraint',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPUnit\\Framework\\Constraint\\Constraint',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 2721,
            'endLine' => 2721,
            'startColumn' => 45,
            'endColumn' => 66,
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
            'name' => 'PHPUnit\\Framework\\Constraint\\LogicalNot',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 2721,
        'endLine' => 2724,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'logicalXor' => 
      array (
        'name' => 'logicalXor',
        'parameters' => 
        array (
          'constraints' => 
          array (
            'name' => 'constraints',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'mixed',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => true,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 2726,
            'endLine' => 2726,
            'startColumn' => 45,
            'endColumn' => 65,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPUnit\\Framework\\Constraint\\LogicalXor',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 2726,
        'endLine' => 2729,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => true,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'anything' => 
      array (
        'name' => 'anything',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPUnit\\Framework\\Constraint\\IsAnything',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 2731,
        'endLine' => 2734,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'isTrue' => 
      array (
        'name' => 'isTrue',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPUnit\\Framework\\Constraint\\IsTrue',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 2736,
        'endLine' => 2739,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'callback' => 
      array (
        'name' => 'callback',
        'parameters' => 
        array (
          'callback' => 
          array (
            'name' => 'callback',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'callable',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 2748,
            'endLine' => 2748,
            'startColumn' => 43,
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
            'name' => 'PHPUnit\\Framework\\Constraint\\Callback',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @template CallbackInput of mixed
 *
 * @param callable(CallbackInput $callback): bool $callback
 *
 * @return Callback<CallbackInput>
 */',
        'startLine' => 2748,
        'endLine' => 2751,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'isFalse' => 
      array (
        'name' => 'isFalse',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPUnit\\Framework\\Constraint\\IsFalse',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 2753,
        'endLine' => 2756,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'isJson' => 
      array (
        'name' => 'isJson',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPUnit\\Framework\\Constraint\\IsJson',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 2758,
        'endLine' => 2761,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'isNull' => 
      array (
        'name' => 'isNull',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPUnit\\Framework\\Constraint\\IsNull',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 2763,
        'endLine' => 2766,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'isFinite' => 
      array (
        'name' => 'isFinite',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPUnit\\Framework\\Constraint\\IsFinite',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 2768,
        'endLine' => 2771,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'isInfinite' => 
      array (
        'name' => 'isInfinite',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPUnit\\Framework\\Constraint\\IsInfinite',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 2773,
        'endLine' => 2776,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'isNan' => 
      array (
        'name' => 'isNan',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPUnit\\Framework\\Constraint\\IsNan',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 2778,
        'endLine' => 2781,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'containsEqual' => 
      array (
        'name' => 'containsEqual',
        'parameters' => 
        array (
          'value' => 
          array (
            'name' => 'value',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'mixed',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 2783,
            'endLine' => 2783,
            'startColumn' => 48,
            'endColumn' => 59,
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
            'name' => 'PHPUnit\\Framework\\Constraint\\TraversableContainsEqual',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 2783,
        'endLine' => 2786,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'containsIdentical' => 
      array (
        'name' => 'containsIdentical',
        'parameters' => 
        array (
          'value' => 
          array (
            'name' => 'value',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'mixed',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 2788,
            'endLine' => 2788,
            'startColumn' => 52,
            'endColumn' => 63,
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
            'name' => 'PHPUnit\\Framework\\Constraint\\TraversableContainsIdentical',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 2788,
        'endLine' => 2791,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'containsOnly' => 
      array (
        'name' => 'containsOnly',
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
            'startLine' => 2800,
            'endLine' => 2800,
            'startColumn' => 47,
            'endColumn' => 58,
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
            'name' => 'PHPUnit\\Framework\\Constraint\\TraversableContainsOnly',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @param \'array\'|\'bool\'|\'boolean\'|\'callable\'|\'double\'|\'float\'|\'int\'|\'integer\'|\'iterable\'|\'null\'|\'numeric\'|\'object\'|\'real\'|\'resource (closed)\'|\'resource\'|\'scalar\'|\'string\' $type
 *
 * @throws Exception
 *
 * @deprecated https://github.com/sebastianbergmann/phpunit/issues/6055
 */',
        'startLine' => 2800,
        'endLine' => 2803,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'containsOnlyArray' => 
      array (
        'name' => 'containsOnlyArray',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPUnit\\Framework\\Constraint\\TraversableContainsOnly',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 2805,
        'endLine' => 2808,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'containsOnlyBool' => 
      array (
        'name' => 'containsOnlyBool',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPUnit\\Framework\\Constraint\\TraversableContainsOnly',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 2810,
        'endLine' => 2813,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'containsOnlyCallable' => 
      array (
        'name' => 'containsOnlyCallable',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPUnit\\Framework\\Constraint\\TraversableContainsOnly',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 2815,
        'endLine' => 2818,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'containsOnlyFloat' => 
      array (
        'name' => 'containsOnlyFloat',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPUnit\\Framework\\Constraint\\TraversableContainsOnly',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 2820,
        'endLine' => 2823,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'containsOnlyInt' => 
      array (
        'name' => 'containsOnlyInt',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPUnit\\Framework\\Constraint\\TraversableContainsOnly',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 2825,
        'endLine' => 2828,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'containsOnlyIterable' => 
      array (
        'name' => 'containsOnlyIterable',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPUnit\\Framework\\Constraint\\TraversableContainsOnly',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 2830,
        'endLine' => 2833,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'containsOnlyNull' => 
      array (
        'name' => 'containsOnlyNull',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPUnit\\Framework\\Constraint\\TraversableContainsOnly',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 2835,
        'endLine' => 2838,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'containsOnlyNumeric' => 
      array (
        'name' => 'containsOnlyNumeric',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPUnit\\Framework\\Constraint\\TraversableContainsOnly',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 2840,
        'endLine' => 2843,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'containsOnlyObject' => 
      array (
        'name' => 'containsOnlyObject',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPUnit\\Framework\\Constraint\\TraversableContainsOnly',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 2845,
        'endLine' => 2848,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'containsOnlyResource' => 
      array (
        'name' => 'containsOnlyResource',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPUnit\\Framework\\Constraint\\TraversableContainsOnly',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 2850,
        'endLine' => 2853,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'containsOnlyClosedResource' => 
      array (
        'name' => 'containsOnlyClosedResource',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPUnit\\Framework\\Constraint\\TraversableContainsOnly',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 2855,
        'endLine' => 2858,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'containsOnlyScalar' => 
      array (
        'name' => 'containsOnlyScalar',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPUnit\\Framework\\Constraint\\TraversableContainsOnly',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 2860,
        'endLine' => 2863,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'containsOnlyString' => 
      array (
        'name' => 'containsOnlyString',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPUnit\\Framework\\Constraint\\TraversableContainsOnly',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 2865,
        'endLine' => 2868,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'containsOnlyInstancesOf' => 
      array (
        'name' => 'containsOnlyInstancesOf',
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
            'startLine' => 2875,
            'endLine' => 2875,
            'startColumn' => 58,
            'endColumn' => 74,
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
            'name' => 'PHPUnit\\Framework\\Constraint\\TraversableContainsOnly',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @param class-string $className
 *
 * @throws Exception
 */',
        'startLine' => 2875,
        'endLine' => 2878,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'arrayHasKey' => 
      array (
        'name' => 'arrayHasKey',
        'parameters' => 
        array (
          'key' => 
          array (
            'name' => 'key',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'mixed',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 2880,
            'endLine' => 2880,
            'startColumn' => 46,
            'endColumn' => 55,
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
            'name' => 'PHPUnit\\Framework\\Constraint\\ArrayHasKey',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 2880,
        'endLine' => 2883,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'isList' => 
      array (
        'name' => 'isList',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPUnit\\Framework\\Constraint\\IsList',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 2885,
        'endLine' => 2888,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'equalTo' => 
      array (
        'name' => 'equalTo',
        'parameters' => 
        array (
          'value' => 
          array (
            'name' => 'value',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'mixed',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 2890,
            'endLine' => 2890,
            'startColumn' => 42,
            'endColumn' => 53,
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
            'name' => 'PHPUnit\\Framework\\Constraint\\IsEqual',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 2890,
        'endLine' => 2893,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'equalToCanonicalizing' => 
      array (
        'name' => 'equalToCanonicalizing',
        'parameters' => 
        array (
          'value' => 
          array (
            'name' => 'value',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'mixed',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 2895,
            'endLine' => 2895,
            'startColumn' => 56,
            'endColumn' => 67,
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
            'name' => 'PHPUnit\\Framework\\Constraint\\IsEqualCanonicalizing',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 2895,
        'endLine' => 2898,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'equalToIgnoringCase' => 
      array (
        'name' => 'equalToIgnoringCase',
        'parameters' => 
        array (
          'value' => 
          array (
            'name' => 'value',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'mixed',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 2900,
            'endLine' => 2900,
            'startColumn' => 54,
            'endColumn' => 65,
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
            'name' => 'PHPUnit\\Framework\\Constraint\\IsEqualIgnoringCase',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 2900,
        'endLine' => 2903,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'equalToWithDelta' => 
      array (
        'name' => 'equalToWithDelta',
        'parameters' => 
        array (
          'value' => 
          array (
            'name' => 'value',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'mixed',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 2905,
            'endLine' => 2905,
            'startColumn' => 51,
            'endColumn' => 62,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'delta' => 
          array (
            'name' => 'delta',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'float',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 2905,
            'endLine' => 2905,
            'startColumn' => 65,
            'endColumn' => 76,
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
            'name' => 'PHPUnit\\Framework\\Constraint\\IsEqualWithDelta',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 2905,
        'endLine' => 2908,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'isEmpty' => 
      array (
        'name' => 'isEmpty',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPUnit\\Framework\\Constraint\\IsEmpty',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 2910,
        'endLine' => 2913,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'isWritable' => 
      array (
        'name' => 'isWritable',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPUnit\\Framework\\Constraint\\IsWritable',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 2915,
        'endLine' => 2918,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'isReadable' => 
      array (
        'name' => 'isReadable',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPUnit\\Framework\\Constraint\\IsReadable',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 2920,
        'endLine' => 2923,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'directoryExists' => 
      array (
        'name' => 'directoryExists',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPUnit\\Framework\\Constraint\\DirectoryExists',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 2925,
        'endLine' => 2928,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'fileExists' => 
      array (
        'name' => 'fileExists',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPUnit\\Framework\\Constraint\\FileExists',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 2930,
        'endLine' => 2933,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'greaterThan' => 
      array (
        'name' => 'greaterThan',
        'parameters' => 
        array (
          'value' => 
          array (
            'name' => 'value',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'mixed',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 2935,
            'endLine' => 2935,
            'startColumn' => 46,
            'endColumn' => 57,
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
            'name' => 'PHPUnit\\Framework\\Constraint\\GreaterThan',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 2935,
        'endLine' => 2938,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'greaterThanOrEqual' => 
      array (
        'name' => 'greaterThanOrEqual',
        'parameters' => 
        array (
          'value' => 
          array (
            'name' => 'value',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'mixed',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 2940,
            'endLine' => 2940,
            'startColumn' => 53,
            'endColumn' => 64,
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
            'name' => 'PHPUnit\\Framework\\Constraint\\LogicalOr',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 2940,
        'endLine' => 2946,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'identicalTo' => 
      array (
        'name' => 'identicalTo',
        'parameters' => 
        array (
          'value' => 
          array (
            'name' => 'value',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'mixed',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 2948,
            'endLine' => 2948,
            'startColumn' => 46,
            'endColumn' => 57,
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
            'name' => 'PHPUnit\\Framework\\Constraint\\IsIdentical',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 2948,
        'endLine' => 2951,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'isInstanceOf' => 
      array (
        'name' => 'isInstanceOf',
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
            'startLine' => 2956,
            'endLine' => 2956,
            'startColumn' => 47,
            'endColumn' => 63,
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
            'name' => 'PHPUnit\\Framework\\Constraint\\IsInstanceOf',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @throws UnknownClassOrInterfaceException
 */',
        'startLine' => 2956,
        'endLine' => 2959,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'isArray' => 
      array (
        'name' => 'isArray',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPUnit\\Framework\\Constraint\\IsType',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 2961,
        'endLine' => 2964,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'isBool' => 
      array (
        'name' => 'isBool',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPUnit\\Framework\\Constraint\\IsType',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 2966,
        'endLine' => 2969,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'isCallable' => 
      array (
        'name' => 'isCallable',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPUnit\\Framework\\Constraint\\IsType',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 2971,
        'endLine' => 2974,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'isFloat' => 
      array (
        'name' => 'isFloat',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPUnit\\Framework\\Constraint\\IsType',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 2976,
        'endLine' => 2979,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'isInt' => 
      array (
        'name' => 'isInt',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPUnit\\Framework\\Constraint\\IsType',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 2981,
        'endLine' => 2984,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'isIterable' => 
      array (
        'name' => 'isIterable',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPUnit\\Framework\\Constraint\\IsType',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 2986,
        'endLine' => 2989,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'isNumeric' => 
      array (
        'name' => 'isNumeric',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPUnit\\Framework\\Constraint\\IsType',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 2991,
        'endLine' => 2994,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'isObject' => 
      array (
        'name' => 'isObject',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPUnit\\Framework\\Constraint\\IsType',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 2996,
        'endLine' => 2999,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'isResource' => 
      array (
        'name' => 'isResource',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPUnit\\Framework\\Constraint\\IsType',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 3001,
        'endLine' => 3004,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'isClosedResource' => 
      array (
        'name' => 'isClosedResource',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPUnit\\Framework\\Constraint\\IsType',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 3006,
        'endLine' => 3009,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'isScalar' => 
      array (
        'name' => 'isScalar',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPUnit\\Framework\\Constraint\\IsType',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 3011,
        'endLine' => 3014,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'isString' => 
      array (
        'name' => 'isString',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPUnit\\Framework\\Constraint\\IsType',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 3016,
        'endLine' => 3019,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'isType' => 
      array (
        'name' => 'isType',
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
            'startLine' => 3028,
            'endLine' => 3028,
            'startColumn' => 41,
            'endColumn' => 52,
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
            'name' => 'PHPUnit\\Framework\\Constraint\\IsType',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @param \'array\'|\'bool\'|\'boolean\'|\'callable\'|\'double\'|\'float\'|\'int\'|\'integer\'|\'iterable\'|\'null\'|\'numeric\'|\'object\'|\'real\'|\'resource (closed)\'|\'resource\'|\'scalar\'|\'string\' $type
 *
 * @throws Exception
 *
 * @deprecated https://github.com/sebastianbergmann/phpunit/issues/6052
 */',
        'startLine' => 3028,
        'endLine' => 3031,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'lessThan' => 
      array (
        'name' => 'lessThan',
        'parameters' => 
        array (
          'value' => 
          array (
            'name' => 'value',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'mixed',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 3033,
            'endLine' => 3033,
            'startColumn' => 43,
            'endColumn' => 54,
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
            'name' => 'PHPUnit\\Framework\\Constraint\\LessThan',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 3033,
        'endLine' => 3036,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'lessThanOrEqual' => 
      array (
        'name' => 'lessThanOrEqual',
        'parameters' => 
        array (
          'value' => 
          array (
            'name' => 'value',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'mixed',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 3038,
            'endLine' => 3038,
            'startColumn' => 50,
            'endColumn' => 61,
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
            'name' => 'PHPUnit\\Framework\\Constraint\\LogicalOr',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 3038,
        'endLine' => 3044,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'matchesRegularExpression' => 
      array (
        'name' => 'matchesRegularExpression',
        'parameters' => 
        array (
          'pattern' => 
          array (
            'name' => 'pattern',
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
            'startLine' => 3046,
            'endLine' => 3046,
            'startColumn' => 59,
            'endColumn' => 73,
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
            'name' => 'PHPUnit\\Framework\\Constraint\\RegularExpression',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 3046,
        'endLine' => 3049,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'matches' => 
      array (
        'name' => 'matches',
        'parameters' => 
        array (
          'string' => 
          array (
            'name' => 'string',
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
            'startLine' => 3051,
            'endLine' => 3051,
            'startColumn' => 42,
            'endColumn' => 55,
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
            'name' => 'PHPUnit\\Framework\\Constraint\\StringMatchesFormatDescription',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 3051,
        'endLine' => 3054,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'stringStartsWith' => 
      array (
        'name' => 'stringStartsWith',
        'parameters' => 
        array (
          'prefix' => 
          array (
            'name' => 'prefix',
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
            'startLine' => 3061,
            'endLine' => 3061,
            'startColumn' => 51,
            'endColumn' => 64,
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
            'name' => 'PHPUnit\\Framework\\Constraint\\StringStartsWith',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @param non-empty-string $prefix
 *
 * @throws InvalidArgumentException
 */',
        'startLine' => 3061,
        'endLine' => 3064,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'stringContains' => 
      array (
        'name' => 'stringContains',
        'parameters' => 
        array (
          'string' => 
          array (
            'name' => 'string',
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
            'startLine' => 3066,
            'endLine' => 3066,
            'startColumn' => 49,
            'endColumn' => 62,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'case' => 
          array (
            'name' => 'case',
            'default' => 
            array (
              'code' => 'true',
              'attributes' => 
              array (
                'startLine' => 3066,
                'endLine' => 3066,
                'startTokenPos' => 13754,
                'startFilePos' => 90122,
                'endTokenPos' => 13754,
                'endFilePos' => 90125,
              ),
            ),
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
            'startLine' => 3066,
            'endLine' => 3066,
            'startColumn' => 65,
            'endColumn' => 81,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPUnit\\Framework\\Constraint\\StringContains',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 3066,
        'endLine' => 3069,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'stringEndsWith' => 
      array (
        'name' => 'stringEndsWith',
        'parameters' => 
        array (
          'suffix' => 
          array (
            'name' => 'suffix',
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
            'startLine' => 3076,
            'endLine' => 3076,
            'startColumn' => 49,
            'endColumn' => 62,
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
            'name' => 'PHPUnit\\Framework\\Constraint\\StringEndsWith',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @param non-empty-string $suffix
 *
 * @throws InvalidArgumentException
 */',
        'startLine' => 3076,
        'endLine' => 3079,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'stringEqualsStringIgnoringLineEndings' => 
      array (
        'name' => 'stringEqualsStringIgnoringLineEndings',
        'parameters' => 
        array (
          'string' => 
          array (
            'name' => 'string',
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
            'startLine' => 3081,
            'endLine' => 3081,
            'startColumn' => 72,
            'endColumn' => 85,
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
            'name' => 'PHPUnit\\Framework\\Constraint\\StringEqualsStringIgnoringLineEndings',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 3081,
        'endLine' => 3084,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'countOf' => 
      array (
        'name' => 'countOf',
        'parameters' => 
        array (
          'count' => 
          array (
            'name' => 'count',
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
            'startLine' => 3086,
            'endLine' => 3086,
            'startColumn' => 42,
            'endColumn' => 51,
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
            'name' => 'PHPUnit\\Framework\\Constraint\\Count',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 3086,
        'endLine' => 3089,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'objectEquals' => 
      array (
        'name' => 'objectEquals',
        'parameters' => 
        array (
          'object' => 
          array (
            'name' => 'object',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'object',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 3091,
            'endLine' => 3091,
            'startColumn' => 47,
            'endColumn' => 60,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'method' => 
          array (
            'name' => 'method',
            'default' => 
            array (
              'code' => '\'equals\'',
              'attributes' => 
              array (
                'startLine' => 3091,
                'endLine' => 3091,
                'startTokenPos' => 13896,
                'startFilePos' => 90839,
                'endTokenPos' => 13896,
                'endFilePos' => 90846,
              ),
            ),
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
            'startLine' => 3091,
            'endLine' => 3091,
            'startColumn' => 63,
            'endColumn' => 87,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPUnit\\Framework\\Constraint\\ObjectEquals',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 3091,
        'endLine' => 3094,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'fail' => 
      array (
        'name' => 'fail',
        'parameters' => 
        array (
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 3101,
                'endLine' => 3101,
                'startTokenPos' => 13937,
                'startFilePos' => 91086,
                'endTokenPos' => 13937,
                'endFilePos' => 91087,
              ),
            ),
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
            'startLine' => 3101,
            'endLine' => 3101,
            'startColumn' => 39,
            'endColumn' => 58,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'never',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Fails a test with the given message.
 *
 * @throws AssertionFailedError
 */',
        'startLine' => 3101,
        'endLine' => 3106,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'markTestIncomplete' => 
      array (
        'name' => 'markTestIncomplete',
        'parameters' => 
        array (
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 3113,
                'endLine' => 3113,
                'startTokenPos' => 13981,
                'startFilePos' => 91349,
                'endTokenPos' => 13981,
                'endFilePos' => 91350,
              ),
            ),
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
            'startLine' => 3113,
            'endLine' => 3113,
            'startColumn' => 53,
            'endColumn' => 72,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'never',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Mark the test as incomplete.
 *
 * @throws IncompleteTestError
 */',
        'startLine' => 3113,
        'endLine' => 3116,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'markTestSkipped' => 
      array (
        'name' => 'markTestSkipped',
        'parameters' => 
        array (
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 3123,
                'endLine' => 3123,
                'startTokenPos' => 14019,
                'startFilePos' => 91588,
                'endTokenPos' => 14019,
                'endFilePos' => 91589,
              ),
            ),
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
            'startLine' => 3123,
            'endLine' => 3123,
            'startColumn' => 50,
            'endColumn' => 69,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'never',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Mark the test as skipped.
 *
 * @throws SkippedWithMessageException
 */',
        'startLine' => 3123,
        'endLine' => 3126,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'getCount' => 
      array (
        'name' => 'getCount',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'int',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Return the current assertion count.
 */',
        'startLine' => 3131,
        'endLine' => 3134,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'resetCount' => 
      array (
        'name' => 'resetCount',
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
        'docComment' => '/**
 * Reset the assertion counter.
 */',
        'startLine' => 3139,
        'endLine' => 3142,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
        'aliasName' => NULL,
      ),
      'isNativeType' => 
      array (
        'name' => 'isNativeType',
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
            'startLine' => 3144,
            'endLine' => 3144,
            'startColumn' => 42,
            'endColumn' => 53,
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
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 3144,
        'endLine' => 3150,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 20,
        'namespace' => 'PHPUnit\\Framework',
        'declaringClassName' => 'PHPUnit\\Framework\\Assert',
        'implementingClassName' => 'PHPUnit\\Framework\\Assert',
        'currentClassName' => 'PHPUnit\\Framework\\Assert',
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