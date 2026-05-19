<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/src/Type/Php/StrvalFamilyFunctionReturnTypeExtension.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\Type\Php\StrvalFamilyFunctionReturnTypeExtension
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-7c02eb4c31a4e147293e12d9d4abe5e03f80d38b8aeaf47f096a5c69cba4cbe9',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\Type\\Php\\StrvalFamilyFunctionReturnTypeExtension',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Php/StrvalFamilyFunctionReturnTypeExtension.php',
      ),
    ),
    'namespace' => 'PHPStan\\Type\\Php',
    'name' => 'PHPStan\\Type\\Php\\StrvalFamilyFunctionReturnTypeExtension',
    'shortName' => 'StrvalFamilyFunctionReturnTypeExtension',
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
    'startLine' => 19,
    'endLine' => 65,
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
      'FUNCTIONS' => 
      array (
        'declaringClassName' => 'PHPStan\\Type\\Php\\StrvalFamilyFunctionReturnTypeExtension',
        'implementingClassName' => 'PHPStan\\Type\\Php\\StrvalFamilyFunctionReturnTypeExtension',
        'name' => 'FUNCTIONS',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'strval\', \'intval\', \'boolval\', \'floatval\', \'doubleval\']',
          'attributes' => 
          array (
            'startLine' => 23,
            'endLine' => 29,
            'startTokenPos' => 109,
            'startFilePos' => 637,
            'endTokenPos' => 126,
            'endFilePos' => 706,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 23,
        'endLine' => 29,
        'startColumn' => 2,
        'endColumn' => 3,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
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
            'startLine' => 31,
            'endLine' => 31,
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
        'startLine' => 31,
        'endLine' => 34,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type\\Php',
        'declaringClassName' => 'PHPStan\\Type\\Php\\StrvalFamilyFunctionReturnTypeExtension',
        'implementingClassName' => 'PHPStan\\Type\\Php\\StrvalFamilyFunctionReturnTypeExtension',
        'currentClassName' => 'PHPStan\\Type\\Php\\StrvalFamilyFunctionReturnTypeExtension',
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
            'startLine' => 37,
            'endLine' => 37,
            'startColumn' => 3,
            'endColumn' => 40,
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
            'startLine' => 38,
            'endLine' => 38,
            'startColumn' => 3,
            'endColumn' => 24,
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
            'startLine' => 39,
            'endLine' => 39,
            'startColumn' => 3,
            'endColumn' => 14,
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
            'name' => 'PHPStan\\Type\\Type',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 36,
        'endLine' => 63,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type\\Php',
        'declaringClassName' => 'PHPStan\\Type\\Php\\StrvalFamilyFunctionReturnTypeExtension',
        'implementingClassName' => 'PHPStan\\Type\\Php\\StrvalFamilyFunctionReturnTypeExtension',
        'currentClassName' => 'PHPStan\\Type\\Php\\StrvalFamilyFunctionReturnTypeExtension',
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