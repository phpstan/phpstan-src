<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/src/Rules/PhpDoc/IncompatibleParamImmediatelyInvokedCallableRule.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\Rules\PhpDoc\IncompatibleParamImmediatelyInvokedCallableRule
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-5cad96079d898996cbfdd37500d72150a7991b367ba64aaae626cef212ba2b69',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\Rules\\PhpDoc\\IncompatibleParamImmediatelyInvokedCallableRule',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/src/Rules/PhpDoc/IncompatibleParamImmediatelyInvokedCallableRule.php',
      ),
    ),
    'namespace' => 'PHPStan\\Rules\\PhpDoc',
    'name' => 'PHPStan\\Rules\\PhpDoc\\IncompatibleParamImmediatelyInvokedCallableRule',
    'shortName' => 'IncompatibleParamImmediatelyInvokedCallableRule',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * @implements Rule<FunctionLike>
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
            'code' => '2',
            'attributes' => 
            array (
              'startLine' => 23,
              'endLine' => 23,
              'startTokenPos' => 100,
              'startFilePos' => 578,
              'endTokenPos' => 100,
              'endFilePos' => 578,
            ),
          ),
        ),
      ),
      1 => 
      array (
        'name' => 'PHPStan\\DependencyInjection\\ValidatesStubFiles',
        'isRepeated' => false,
        'arguments' => 
        array (
        ),
      ),
    ),
    'startLine' => 23,
    'endLine' => 98,
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
      'fileTypeMapper' => 
      array (
        'declaringClassName' => 'PHPStan\\Rules\\PhpDoc\\IncompatibleParamImmediatelyInvokedCallableRule',
        'implementingClassName' => 'PHPStan\\Rules\\PhpDoc\\IncompatibleParamImmediatelyInvokedCallableRule',
        'name' => 'fileTypeMapper',
        'modifiers' => 4,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPStan\\Type\\FileTypeMapper',
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
        'endColumn' => 40,
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
          'fileTypeMapper' => 
          array (
            'name' => 'fileTypeMapper',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\Type\\FileTypeMapper',
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
            'endColumn' => 40,
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
        'startLine' => 28,
        'endLine' => 32,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Rules\\PhpDoc',
        'declaringClassName' => 'PHPStan\\Rules\\PhpDoc\\IncompatibleParamImmediatelyInvokedCallableRule',
        'implementingClassName' => 'PHPStan\\Rules\\PhpDoc\\IncompatibleParamImmediatelyInvokedCallableRule',
        'currentClassName' => 'PHPStan\\Rules\\PhpDoc\\IncompatibleParamImmediatelyInvokedCallableRule',
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
        'namespace' => 'PHPStan\\Rules\\PhpDoc',
        'declaringClassName' => 'PHPStan\\Rules\\PhpDoc\\IncompatibleParamImmediatelyInvokedCallableRule',
        'implementingClassName' => 'PHPStan\\Rules\\PhpDoc\\IncompatibleParamImmediatelyInvokedCallableRule',
        'currentClassName' => 'PHPStan\\Rules\\PhpDoc\\IncompatibleParamImmediatelyInvokedCallableRule',
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
        'endLine' => 96,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Rules\\PhpDoc',
        'declaringClassName' => 'PHPStan\\Rules\\PhpDoc\\IncompatibleParamImmediatelyInvokedCallableRule',
        'implementingClassName' => 'PHPStan\\Rules\\PhpDoc\\IncompatibleParamImmediatelyInvokedCallableRule',
        'currentClassName' => 'PHPStan\\Rules\\PhpDoc\\IncompatibleParamImmediatelyInvokedCallableRule',
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