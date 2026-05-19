<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/src/Rules/Functions/ImplodeParameterCastableToStringRule.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\Rules\Functions\ImplodeParameterCastableToStringRule
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-1b753c917d79cf6b565c8a9f8b5af0ebbd1c7a0139cef2bfccf4d36331b957f2',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\Rules\\Functions\\ImplodeParameterCastableToStringRule',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/src/Rules/Functions/ImplodeParameterCastableToStringRule.php',
      ),
    ),
    'namespace' => 'PHPStan\\Rules\\Functions',
    'name' => 'PHPStan\\Rules\\Functions\\ImplodeParameterCastableToStringRule',
    'shortName' => 'ImplodeParameterCastableToStringRule',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * @implements Rule<Node\\Expr\\FuncCall>
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
            'code' => '5',
            'attributes' => 
            array (
              'startLine' => 23,
              'endLine' => 23,
              'startTokenPos' => 102,
              'startFilePos' => 602,
              'endTokenPos' => 102,
              'endFilePos' => 602,
            ),
          ),
        ),
      ),
    ),
    'startLine' => 23,
    'endLine' => 119,
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
      'reflectionProvider' => 
      array (
        'declaringClassName' => 'PHPStan\\Rules\\Functions\\ImplodeParameterCastableToStringRule',
        'implementingClassName' => 'PHPStan\\Rules\\Functions\\ImplodeParameterCastableToStringRule',
        'name' => 'reflectionProvider',
        'modifiers' => 4,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPStan\\Reflection\\ReflectionProvider',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 28,
        'endLine' => 28,
        'startColumn' => 3,
        'endColumn' => 48,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'parameterCastableToStringCheck' => 
      array (
        'declaringClassName' => 'PHPStan\\Rules\\Functions\\ImplodeParameterCastableToStringRule',
        'implementingClassName' => 'PHPStan\\Rules\\Functions\\ImplodeParameterCastableToStringRule',
        'name' => 'parameterCastableToStringCheck',
        'modifiers' => 4,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPStan\\Rules\\ParameterCastableToStringCheck',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 29,
        'endLine' => 29,
        'startColumn' => 3,
        'endColumn' => 72,
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
          'reflectionProvider' => 
          array (
            'name' => 'reflectionProvider',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\Reflection\\ReflectionProvider',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 28,
            'endLine' => 28,
            'startColumn' => 3,
            'endColumn' => 48,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'parameterCastableToStringCheck' => 
          array (
            'name' => 'parameterCastableToStringCheck',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\Rules\\ParameterCastableToStringCheck',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 29,
            'endLine' => 29,
            'startColumn' => 3,
            'endColumn' => 72,
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
        'startLine' => 27,
        'endLine' => 32,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Rules\\Functions',
        'declaringClassName' => 'PHPStan\\Rules\\Functions\\ImplodeParameterCastableToStringRule',
        'implementingClassName' => 'PHPStan\\Rules\\Functions\\ImplodeParameterCastableToStringRule',
        'currentClassName' => 'PHPStan\\Rules\\Functions\\ImplodeParameterCastableToStringRule',
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
        'startLine' => 34,
        'endLine' => 37,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Rules\\Functions',
        'declaringClassName' => 'PHPStan\\Rules\\Functions\\ImplodeParameterCastableToStringRule',
        'implementingClassName' => 'PHPStan\\Rules\\Functions\\ImplodeParameterCastableToStringRule',
        'currentClassName' => 'PHPStan\\Rules\\Functions\\ImplodeParameterCastableToStringRule',
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
            'startLine' => 39,
            'endLine' => 39,
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
            'startLine' => 39,
            'endLine' => 39,
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
        'startLine' => 39,
        'endLine' => 117,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Rules\\Functions',
        'declaringClassName' => 'PHPStan\\Rules\\Functions\\ImplodeParameterCastableToStringRule',
        'implementingClassName' => 'PHPStan\\Rules\\Functions\\ImplodeParameterCastableToStringRule',
        'currentClassName' => 'PHPStan\\Rules\\Functions\\ImplodeParameterCastableToStringRule',
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