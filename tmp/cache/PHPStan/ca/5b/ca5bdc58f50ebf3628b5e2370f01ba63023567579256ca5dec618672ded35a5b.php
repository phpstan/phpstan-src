<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/src/Rules/TooWideTypehints/TooWideMethodParameterOutTypeRule.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\Rules\TooWideTypehints\TooWideMethodParameterOutTypeRule
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-a8118cfc39f144832e9a7845069a85018ef592fa09fd5b8d3104ed6849fd2ea7',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\Rules\\TooWideTypehints\\TooWideMethodParameterOutTypeRule',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/src/Rules/TooWideTypehints/TooWideMethodParameterOutTypeRule.php',
      ),
    ),
    'namespace' => 'PHPStan\\Rules\\TooWideTypehints',
    'name' => 'PHPStan\\Rules\\TooWideTypehints\\TooWideMethodParameterOutTypeRule',
    'shortName' => 'TooWideMethodParameterOutTypeRule',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * @implements Rule<MethodReturnStatementsNode>
 */',
    'attributes' => 
    array (
      0 => 
      array (
        'name' => 'PHPStan\\DependencyInjection\\RegisteredRule',
        'isRepeated' => false,
        'arguments' => 
        array (
          'level' => 
          array (
            'code' => '4',
            'attributes' => 
            array (
              'startLine' => 16,
              'endLine' => 16,
              'startTokenPos' => 61,
              'startFilePos' => 397,
              'endTokenPos' => 61,
              'endFilePos' => 397,
            ),
          ),
        ),
      ),
    ),
    'startLine' => 16,
    'endLine' => 54,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
      0 => 'PHPStan\\Rules\\Rule',
    ),
    'traitClassNames' => 
    array (
    ),
    'immediateConstants' => 
    array (
    ),
    'immediateProperties' => 
    array (
      'check' => 
      array (
        'declaringClassName' => 'PHPStan\\Rules\\TooWideTypehints\\TooWideMethodParameterOutTypeRule',
        'implementingClassName' => 'PHPStan\\Rules\\TooWideTypehints\\TooWideMethodParameterOutTypeRule',
        'name' => 'check',
        'modifiers' => 4,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPStan\\Rules\\TooWideTypehints\\TooWideParameterOutTypeCheck',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 21,
        'endLine' => 21,
        'startColumn' => 3,
        'endColumn' => 45,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'checkProtectedAndPublicMethods' => 
      array (
        'declaringClassName' => 'PHPStan\\Rules\\TooWideTypehints\\TooWideMethodParameterOutTypeRule',
        'implementingClassName' => 'PHPStan\\Rules\\TooWideTypehints\\TooWideMethodParameterOutTypeRule',
        'name' => 'checkProtectedAndPublicMethods',
        'modifiers' => 4,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
          0 => 
          array (
            'name' => 'PHPStan\\DependencyInjection\\AutowiredParameter',
            'isRepeated' => false,
            'arguments' => 
            array (
              'ref' => 
              array (
                'code' => '\'%checkTooWideParameterOutInProtectedAndPublicMethods%\'',
                'attributes' => 
                array (
                  'startLine' => 22,
                  'endLine' => 22,
                  'startTokenPos' => 97,
                  'startFilePos' => 571,
                  'endTokenPos' => 97,
                  'endFilePos' => 625,
                ),
              ),
            ),
          ),
        ),
        'startLine' => 22,
        'endLine' => 23,
        'startColumn' => 3,
        'endColumn' => 46,
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
          'check' => 
          array (
            'name' => 'check',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\Rules\\TooWideTypehints\\TooWideParameterOutTypeCheck',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 21,
            'endLine' => 21,
            'startColumn' => 3,
            'endColumn' => 45,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'checkProtectedAndPublicMethods' => 
          array (
            'name' => 'checkProtectedAndPublicMethods',
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
            'isPromoted' => true,
            'attributes' => 
            array (
              0 => 
              array (
                'name' => 'PHPStan\\DependencyInjection\\AutowiredParameter',
                'isRepeated' => false,
                'arguments' => 
                array (
                  'ref' => 
                  array (
                    'code' => '\'%checkTooWideParameterOutInProtectedAndPublicMethods%\'',
                    'attributes' => 
                    array (
                      'startLine' => 22,
                      'endLine' => 22,
                      'startTokenPos' => 97,
                      'startFilePos' => 571,
                      'endTokenPos' => 97,
                      'endFilePos' => 625,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 22,
            'endLine' => 23,
            'startColumn' => 3,
            'endColumn' => 46,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 20,
        'endLine' => 26,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Rules\\TooWideTypehints',
        'declaringClassName' => 'PHPStan\\Rules\\TooWideTypehints\\TooWideMethodParameterOutTypeRule',
        'implementingClassName' => 'PHPStan\\Rules\\TooWideTypehints\\TooWideMethodParameterOutTypeRule',
        'currentClassName' => 'PHPStan\\Rules\\TooWideTypehints\\TooWideMethodParameterOutTypeRule',
        'aliasName' => NULL,
      ),
      'getNodeType' => 
      array (
        'name' => 'getNodeType',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 28,
        'endLine' => 31,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Rules\\TooWideTypehints',
        'declaringClassName' => 'PHPStan\\Rules\\TooWideTypehints\\TooWideMethodParameterOutTypeRule',
        'implementingClassName' => 'PHPStan\\Rules\\TooWideTypehints\\TooWideMethodParameterOutTypeRule',
        'currentClassName' => 'PHPStan\\Rules\\TooWideTypehints\\TooWideMethodParameterOutTypeRule',
        'aliasName' => NULL,
      ),
      'processNode' => 
      array (
        'name' => 'processNode',
        'parameters' => 
        array (
          'node' => 
          array (
            'name' => 'node',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PhpParser\\Node',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 33,
            'endLine' => 33,
            'startColumn' => 30,
            'endColumn' => 39,
            'parameterIndex' => 0,
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
            'startLine' => 33,
            'endLine' => 33,
            'startColumn' => 42,
            'endColumn' => 53,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 33,
        'endLine' => 52,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Rules\\TooWideTypehints',
        'declaringClassName' => 'PHPStan\\Rules\\TooWideTypehints\\TooWideMethodParameterOutTypeRule',
        'implementingClassName' => 'PHPStan\\Rules\\TooWideTypehints\\TooWideMethodParameterOutTypeRule',
        'currentClassName' => 'PHPStan\\Rules\\TooWideTypehints\\TooWideMethodParameterOutTypeRule',
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