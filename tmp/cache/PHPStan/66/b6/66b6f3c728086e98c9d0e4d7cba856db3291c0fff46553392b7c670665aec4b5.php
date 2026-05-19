<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/src/Rules/Exceptions/CatchWithUnthrownExceptionRule.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\Rules\Exceptions\CatchWithUnthrownExceptionRule
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-1c20d83f877560537cde5f347acb86984c40294ee7c93caca05f5023f1269447',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\Rules\\Exceptions\\CatchWithUnthrownExceptionRule',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/src/Rules/Exceptions/CatchWithUnthrownExceptionRule.php',
      ),
    ),
    'namespace' => 'PHPStan\\Rules\\Exceptions',
    'name' => 'PHPStan\\Rules\\Exceptions\\CatchWithUnthrownExceptionRule',
    'shortName' => 'CatchWithUnthrownExceptionRule',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * @implements Rule<CatchWithUnthrownExceptionNode>
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
              'startLine' => 19,
              'endLine' => 19,
              'startTokenPos' => 76,
              'startFilePos' => 496,
              'endTokenPos' => 76,
              'endFilePos' => 496,
            ),
          ),
        ),
      ),
    ),
    'startLine' => 19,
    'endLine' => 74,
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
        'declaringClassName' => 'PHPStan\\Rules\\Exceptions\\CatchWithUnthrownExceptionRule',
        'implementingClassName' => 'PHPStan\\Rules\\Exceptions\\CatchWithUnthrownExceptionRule',
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
                  'startLine' => 24,
                  'endLine' => 24,
                  'startTokenPos' => 105,
                  'startFilePos' => 620,
                  'endTokenPos' => 105,
                  'endFilePos' => 643,
                ),
              ),
            ),
          ),
        ),
        'startLine' => 24,
        'endLine' => 25,
        'startColumn' => 3,
        'endColumn' => 54,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'reportUncheckedExceptionDeadCatch' => 
      array (
        'declaringClassName' => 'PHPStan\\Rules\\Exceptions\\CatchWithUnthrownExceptionRule',
        'implementingClassName' => 'PHPStan\\Rules\\Exceptions\\CatchWithUnthrownExceptionRule',
        'name' => 'reportUncheckedExceptionDeadCatch',
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
                'code' => '\'%exceptions.reportUncheckedExceptionDeadCatch%\'',
                'attributes' => 
                array (
                  'startLine' => 26,
                  'endLine' => 26,
                  'startTokenPos' => 122,
                  'startFilePos' => 731,
                  'endTokenPos' => 122,
                  'endFilePos' => 778,
                ),
              ),
            ),
          ),
        ),
        'startLine' => 26,
        'endLine' => 27,
        'startColumn' => 3,
        'endColumn' => 49,
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
                      'startLine' => 24,
                      'endLine' => 24,
                      'startTokenPos' => 105,
                      'startFilePos' => 620,
                      'endTokenPos' => 105,
                      'endFilePos' => 643,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 24,
            'endLine' => 25,
            'startColumn' => 3,
            'endColumn' => 54,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'reportUncheckedExceptionDeadCatch' => 
          array (
            'name' => 'reportUncheckedExceptionDeadCatch',
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
                    'code' => '\'%exceptions.reportUncheckedExceptionDeadCatch%\'',
                    'attributes' => 
                    array (
                      'startLine' => 26,
                      'endLine' => 26,
                      'startTokenPos' => 122,
                      'startFilePos' => 731,
                      'endTokenPos' => 122,
                      'endFilePos' => 778,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 26,
            'endLine' => 27,
            'startColumn' => 3,
            'endColumn' => 49,
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
        'startLine' => 23,
        'endLine' => 30,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Rules\\Exceptions',
        'declaringClassName' => 'PHPStan\\Rules\\Exceptions\\CatchWithUnthrownExceptionRule',
        'implementingClassName' => 'PHPStan\\Rules\\Exceptions\\CatchWithUnthrownExceptionRule',
        'currentClassName' => 'PHPStan\\Rules\\Exceptions\\CatchWithUnthrownExceptionRule',
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
        'startLine' => 32,
        'endLine' => 35,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Rules\\Exceptions',
        'declaringClassName' => 'PHPStan\\Rules\\Exceptions\\CatchWithUnthrownExceptionRule',
        'implementingClassName' => 'PHPStan\\Rules\\Exceptions\\CatchWithUnthrownExceptionRule',
        'currentClassName' => 'PHPStan\\Rules\\Exceptions\\CatchWithUnthrownExceptionRule',
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
            'startLine' => 37,
            'endLine' => 37,
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
            'startLine' => 37,
            'endLine' => 37,
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
        'startLine' => 37,
        'endLine' => 72,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Rules\\Exceptions',
        'declaringClassName' => 'PHPStan\\Rules\\Exceptions\\CatchWithUnthrownExceptionRule',
        'implementingClassName' => 'PHPStan\\Rules\\Exceptions\\CatchWithUnthrownExceptionRule',
        'currentClassName' => 'PHPStan\\Rules\\Exceptions\\CatchWithUnthrownExceptionRule',
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