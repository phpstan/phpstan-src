<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/src/Type/Php/HashFunctionsReturnTypeExtension.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\Type\Php\HashFunctionsReturnTypeExtension
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-27efbf8496227a62085f0c7c87d96cda73ca94302c07b547ca9e33c99af2d9b6',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\Type\\Php\\HashFunctionsReturnTypeExtension',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Php/HashFunctionsReturnTypeExtension.php',
      ),
    ),
    'namespace' => 'PHPStan\\Type\\Php',
    'name' => 'PHPStan\\Type\\Php\\HashFunctionsReturnTypeExtension',
    'shortName' => 'HashFunctionsReturnTypeExtension',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => NULL,
    'attributes' => 
    array (
      0 => 
      array (
        'name' => 'PHPStan\\DependencyInjection\\AutowiredService',
        'isRepeated' => false,
        'arguments' => 
        array (
        ),
      ),
    ),
    'startLine' => 30,
    'endLine' => 166,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
      0 => 'PHPStan\\Type\\DynamicFunctionReturnTypeExtension',
    ),
    'traitClassNames' => 
    array (
    ),
    'immediateConstants' => 
    array (
      'SUPPORTED_FUNCTIONS' => 
      array (
        'declaringClassName' => 'PHPStan\\Type\\Php\\HashFunctionsReturnTypeExtension',
        'implementingClassName' => 'PHPStan\\Type\\Php\\HashFunctionsReturnTypeExtension',
        'name' => 'SUPPORTED_FUNCTIONS',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'hash\' => [\'cryptographic\' => false, \'possiblyFalse\' => false, \'binary\' => 2], \'hash_file\' => [\'cryptographic\' => false, \'possiblyFalse\' => true, \'binary\' => 2], \'hash_hkdf\' => [\'cryptographic\' => true, \'possiblyFalse\' => false, \'binary\' => true], \'hash_hmac\' => [\'cryptographic\' => true, \'possiblyFalse\' => false, \'binary\' => 3], \'hash_hmac_file\' => [\'cryptographic\' => true, \'possiblyFalse\' => true, \'binary\' => 3], \'hash_pbkdf2\' => [\'cryptographic\' => true, \'possiblyFalse\' => false, \'binary\' => 5]]',
          'attributes' => 
          array (
            'startLine' => 34,
            'endLine' => 65,
            'startTokenPos' => 174,
            'startFilePos' => 1051,
            'endTokenPos' => 356,
            'endFilePos' => 1653,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 34,
        'endLine' => 65,
        'startColumn' => 2,
        'endColumn' => 3,
      ),
      'NON_CRYPTOGRAPHIC_ALGORITHMS' => 
      array (
        'declaringClassName' => 'PHPStan\\Type\\Php\\HashFunctionsReturnTypeExtension',
        'implementingClassName' => 'PHPStan\\Type\\Php\\HashFunctionsReturnTypeExtension',
        'name' => 'NON_CRYPTOGRAPHIC_ALGORITHMS',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'adler32\', \'crc32\', \'crc32b\', \'crc32c\', \'fnv132\', \'fnv1a32\', \'fnv164\', \'fnv1a64\', \'joaat\', \'murmur3a\', \'murmur3c\', \'murmur3f\', \'xxh32\', \'xxh64\', \'xxh3\', \'xxh128\']',
          'attributes' => 
          array (
            'startLine' => 67,
            'endLine' => 84,
            'startTokenPos' => 367,
            'startFilePos' => 1703,
            'endTokenPos' => 417,
            'endFilePos' => 1901,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 67,
        'endLine' => 84,
        'startColumn' => 2,
        'endColumn' => 3,
      ),
    ),
    'immediateProperties' => 
    array (
      'hashAlgorithms' => 
      array (
        'declaringClassName' => 'PHPStan\\Type\\Php\\HashFunctionsReturnTypeExtension',
        'implementingClassName' => 'PHPStan\\Type\\Php\\HashFunctionsReturnTypeExtension',
        'name' => 'hashAlgorithms',
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
        'docComment' => '/** @var array<int, non-empty-string> */',
        'attributes' => 
        array (
        ),
        'startLine' => 87,
        'endLine' => 87,
        'startColumn' => 2,
        'endColumn' => 31,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'phpVersion' => 
      array (
        'declaringClassName' => 'PHPStan\\Type\\Php\\HashFunctionsReturnTypeExtension',
        'implementingClassName' => 'PHPStan\\Type\\Php\\HashFunctionsReturnTypeExtension',
        'name' => 'phpVersion',
        'modifiers' => 4,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPStan\\Php\\PhpVersion',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 89,
        'endLine' => 89,
        'startColumn' => 30,
        'endColumn' => 59,
        'isPromoted' => true,
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
          'phpVersion' => 
          array (
            'name' => 'phpVersion',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\Php\\PhpVersion',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 89,
            'endLine' => 89,
            'startColumn' => 30,
            'endColumn' => 59,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 89,
        'endLine' => 92,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type\\Php',
        'declaringClassName' => 'PHPStan\\Type\\Php\\HashFunctionsReturnTypeExtension',
        'implementingClassName' => 'PHPStan\\Type\\Php\\HashFunctionsReturnTypeExtension',
        'currentClassName' => 'PHPStan\\Type\\Php\\HashFunctionsReturnTypeExtension',
        'aliasName' => NULL,
      ),
      'isFunctionSupported' => 
      array (
        'name' => 'isFunctionSupported',
        'parameters' => 
        array (
          'functionReflection' => 
          array (
            'name' => 'functionReflection',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\Reflection\\FunctionReflection',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 94,
            'endLine' => 94,
            'startColumn' => 38,
            'endColumn' => 75,
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
        'startLine' => 94,
        'endLine' => 98,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type\\Php',
        'declaringClassName' => 'PHPStan\\Type\\Php\\HashFunctionsReturnTypeExtension',
        'implementingClassName' => 'PHPStan\\Type\\Php\\HashFunctionsReturnTypeExtension',
        'currentClassName' => 'PHPStan\\Type\\Php\\HashFunctionsReturnTypeExtension',
        'aliasName' => NULL,
      ),
      'getTypeFromFunctionCall' => 
      array (
        'name' => 'getTypeFromFunctionCall',
        'parameters' => 
        array (
          'functionReflection' => 
          array (
            'name' => 'functionReflection',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\Reflection\\FunctionReflection',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 100,
            'endLine' => 100,
            'startColumn' => 42,
            'endColumn' => 79,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'functionCall' => 
          array (
            'name' => 'functionCall',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PhpParser\\Node\\Expr\\FuncCall',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 100,
            'endLine' => 100,
            'startColumn' => 82,
            'endColumn' => 103,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'scope' => 
          array (
            'name' => 'scope',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\Analyser\\Scope',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 100,
            'endLine' => 100,
            'startColumn' => 106,
            'endColumn' => 117,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
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
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 100,
        'endLine' => 164,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type\\Php',
        'declaringClassName' => 'PHPStan\\Type\\Php\\HashFunctionsReturnTypeExtension',
        'implementingClassName' => 'PHPStan\\Type\\Php\\HashFunctionsReturnTypeExtension',
        'currentClassName' => 'PHPStan\\Type\\Php\\HashFunctionsReturnTypeExtension',
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