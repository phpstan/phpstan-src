<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/src/Rules/TooWideTypehints/TooWideMethodReturnTypehintRule.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\Rules\TooWideTypehints\TooWideMethodReturnTypehintRule
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-1b61d3e253263edff0d3394ece17684194dccf181d366778b68bbaf6416adb12',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\Rules\\TooWideTypehints\\TooWideMethodReturnTypehintRule',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/src/Rules/TooWideTypehints/TooWideMethodReturnTypehintRule.php',
      ),
    ),
    'namespace' => 'PHPStan\\Rules\\TooWideTypehints',
    'name' => 'PHPStan\\Rules\\TooWideTypehints\\TooWideMethodReturnTypehintRule',
    'shortName' => 'TooWideMethodReturnTypehintRule',
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
    'endLine' => 66,
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
      'checkProtectedAndPublicMethods' => 
      array (
        'declaringClassName' => 'PHPStan\\Rules\\TooWideTypehints\\TooWideMethodReturnTypehintRule',
        'implementingClassName' => 'PHPStan\\Rules\\TooWideTypehints\\TooWideMethodReturnTypehintRule',
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
                'code' => '\'%checkTooWideReturnTypesInProtectedAndPublicMethods%\'',
                'attributes' => 
                array (
                  'startLine' => 21,
                  'endLine' => 21,
                  'startTokenPos' => 90,
                  'startFilePos' => 522,
                  'endTokenPos' => 90,
                  'endFilePos' => 575,
                ),
              ),
            ),
          ),
        ),
        'startLine' => 21,
        'endLine' => 22,
        'startColumn' => 3,
        'endColumn' => 46,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'check' => 
      array (
        'declaringClassName' => 'PHPStan\\Rules\\TooWideTypehints\\TooWideMethodReturnTypehintRule',
        'implementingClassName' => 'PHPStan\\Rules\\TooWideTypehints\\TooWideMethodReturnTypehintRule',
        'name' => 'check',
        'modifiers' => 4,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPStan\\Rules\\TooWideTypehints\\TooWideTypeCheck',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 23,
        'endLine' => 23,
        'startColumn' => 3,
        'endColumn' => 33,
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
                    'code' => '\'%checkTooWideReturnTypesInProtectedAndPublicMethods%\'',
                    'attributes' => 
                    array (
                      'startLine' => 21,
                      'endLine' => 21,
                      'startTokenPos' => 90,
                      'startFilePos' => 522,
                      'endTokenPos' => 90,
                      'endFilePos' => 575,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 21,
            'endLine' => 22,
            'startColumn' => 3,
            'endColumn' => 46,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'check' => 
          array (
            'name' => 'check',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\Rules\\TooWideTypehints\\TooWideTypeCheck',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 23,
            'endLine' => 23,
            'startColumn' => 3,
            'endColumn' => 33,
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
        'declaringClassName' => 'PHPStan\\Rules\\TooWideTypehints\\TooWideMethodReturnTypehintRule',
        'implementingClassName' => 'PHPStan\\Rules\\TooWideTypehints\\TooWideMethodReturnTypehintRule',
        'currentClassName' => 'PHPStan\\Rules\\TooWideTypehints\\TooWideMethodReturnTypehintRule',
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
        'declaringClassName' => 'PHPStan\\Rules\\TooWideTypehints\\TooWideMethodReturnTypehintRule',
        'implementingClassName' => 'PHPStan\\Rules\\TooWideTypehints\\TooWideMethodReturnTypehintRule',
        'currentClassName' => 'PHPStan\\Rules\\TooWideTypehints\\TooWideMethodReturnTypehintRule',
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
        'endLine' => 64,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Rules\\TooWideTypehints',
        'declaringClassName' => 'PHPStan\\Rules\\TooWideTypehints\\TooWideMethodReturnTypehintRule',
        'implementingClassName' => 'PHPStan\\Rules\\TooWideTypehints\\TooWideMethodReturnTypehintRule',
        'currentClassName' => 'PHPStan\\Rules\\TooWideTypehints\\TooWideMethodReturnTypehintRule',
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