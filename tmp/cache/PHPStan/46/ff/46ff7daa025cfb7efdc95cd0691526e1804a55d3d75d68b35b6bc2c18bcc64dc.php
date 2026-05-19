<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/src/Rules/Exceptions/ThrowsVoidPropertyHookWithExplicitThrowPointRule.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\Rules\Exceptions\ThrowsVoidPropertyHookWithExplicitThrowPointRule
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-65079ba7ca2baae5ecb236ce8ffeb879f4b95726916d466f8cb5e91e9d1a3870',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\Rules\\Exceptions\\ThrowsVoidPropertyHookWithExplicitThrowPointRule',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/src/Rules/Exceptions/ThrowsVoidPropertyHookWithExplicitThrowPointRule.php',
      ),
    ),
    'namespace' => 'PHPStan\\Rules\\Exceptions',
    'name' => 'PHPStan\\Rules\\Exceptions\\ThrowsVoidPropertyHookWithExplicitThrowPointRule',
    'shortName' => 'ThrowsVoidPropertyHookWithExplicitThrowPointRule',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * @implements Rule<PropertyHookReturnStatementsNode>
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
            'code' => '3',
            'attributes' => 
            array (
              'startLine' => 22,
              'endLine' => 22,
              'startTokenPos' => 93,
              'startFilePos' => 586,
              'endTokenPos' => 93,
              'endFilePos' => 586,
            ),
          ),
        ),
      ),
    ),
    'startLine' => 22,
    'endLine' => 84,
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
      'exceptionTypeResolver' => 
      array (
        'declaringClassName' => 'PHPStan\\Rules\\Exceptions\\ThrowsVoidPropertyHookWithExplicitThrowPointRule',
        'implementingClassName' => 'PHPStan\\Rules\\Exceptions\\ThrowsVoidPropertyHookWithExplicitThrowPointRule',
        'name' => 'exceptionTypeResolver',
        'modifiers' => 4,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPStan\\Rules\\Exceptions\\ExceptionTypeResolver',
            'isIdentifier' => false,
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
                'code' => '\'@exceptionTypeResolver\'',
                'attributes' => 
                array (
                  'startLine' => 27,
                  'endLine' => 27,
                  'startTokenPos' => 122,
                  'startFilePos' => 728,
                  'endTokenPos' => 122,
                  'endFilePos' => 751,
                ),
              ),
            ),
          ),
        ),
        'startLine' => 27,
        'endLine' => 28,
        'startColumn' => 3,
        'endColumn' => 54,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'missingCheckedExceptionInThrows' => 
      array (
        'declaringClassName' => 'PHPStan\\Rules\\Exceptions\\ThrowsVoidPropertyHookWithExplicitThrowPointRule',
        'implementingClassName' => 'PHPStan\\Rules\\Exceptions\\ThrowsVoidPropertyHookWithExplicitThrowPointRule',
        'name' => 'missingCheckedExceptionInThrows',
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
                'code' => '\'%exceptions.check.missingCheckedExceptionInThrows%\'',
                'attributes' => 
                array (
                  'startLine' => 29,
                  'endLine' => 29,
                  'startTokenPos' => 139,
                  'startFilePos' => 839,
                  'endTokenPos' => 139,
                  'endFilePos' => 890,
                ),
              ),
            ),
          ),
        ),
        'startLine' => 29,
        'endLine' => 30,
        'startColumn' => 3,
        'endColumn' => 47,
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
          'exceptionTypeResolver' => 
          array (
            'name' => 'exceptionTypeResolver',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\Rules\\Exceptions\\ExceptionTypeResolver',
                'isIdentifier' => false,
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
                    'code' => '\'@exceptionTypeResolver\'',
                    'attributes' => 
                    array (
                      'startLine' => 27,
                      'endLine' => 27,
                      'startTokenPos' => 122,
                      'startFilePos' => 728,
                      'endTokenPos' => 122,
                      'endFilePos' => 751,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 27,
            'endLine' => 28,
            'startColumn' => 3,
            'endColumn' => 54,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'missingCheckedExceptionInThrows' => 
          array (
            'name' => 'missingCheckedExceptionInThrows',
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
                    'code' => '\'%exceptions.check.missingCheckedExceptionInThrows%\'',
                    'attributes' => 
                    array (
                      'startLine' => 29,
                      'endLine' => 29,
                      'startTokenPos' => 139,
                      'startFilePos' => 839,
                      'endTokenPos' => 139,
                      'endFilePos' => 890,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 29,
            'endLine' => 30,
            'startColumn' => 3,
            'endColumn' => 47,
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
        'startLine' => 26,
        'endLine' => 33,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Rules\\Exceptions',
        'declaringClassName' => 'PHPStan\\Rules\\Exceptions\\ThrowsVoidPropertyHookWithExplicitThrowPointRule',
        'implementingClassName' => 'PHPStan\\Rules\\Exceptions\\ThrowsVoidPropertyHookWithExplicitThrowPointRule',
        'currentClassName' => 'PHPStan\\Rules\\Exceptions\\ThrowsVoidPropertyHookWithExplicitThrowPointRule',
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
        'startLine' => 35,
        'endLine' => 38,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Rules\\Exceptions',
        'declaringClassName' => 'PHPStan\\Rules\\Exceptions\\ThrowsVoidPropertyHookWithExplicitThrowPointRule',
        'implementingClassName' => 'PHPStan\\Rules\\Exceptions\\ThrowsVoidPropertyHookWithExplicitThrowPointRule',
        'currentClassName' => 'PHPStan\\Rules\\Exceptions\\ThrowsVoidPropertyHookWithExplicitThrowPointRule',
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
            'startLine' => 40,
            'endLine' => 40,
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
            'startLine' => 40,
            'endLine' => 40,
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
        'startLine' => 40,
        'endLine' => 82,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Rules\\Exceptions',
        'declaringClassName' => 'PHPStan\\Rules\\Exceptions\\ThrowsVoidPropertyHookWithExplicitThrowPointRule',
        'implementingClassName' => 'PHPStan\\Rules\\Exceptions\\ThrowsVoidPropertyHookWithExplicitThrowPointRule',
        'currentClassName' => 'PHPStan\\Rules\\Exceptions\\ThrowsVoidPropertyHookWithExplicitThrowPointRule',
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