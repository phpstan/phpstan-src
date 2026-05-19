<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/src/Node/PropertyHookStatementNode.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\Node\PropertyHookStatementNode
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-5768f9e0d4e184493a944bf2d0503f268eef16a67573c1d7947d14d1367b2472',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\Node\\PropertyHookStatementNode',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/src/Node/PropertyHookStatementNode.php',
      ),
    ),
    'namespace' => 'PHPStan\\Node',
    'name' => 'PHPStan\\Node\\PropertyHookStatementNode',
    'shortName' => 'PropertyHookStatementNode',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * This class exists because PhpParser\\Node\\PropertyHook
 * is not a Stmt, but we need to pass it to
 * a few places that expect a Stmt.
 *
 * This is because PhpParser\\Node\\PropertyHook
 * is likely the one of two PhpParser nodes which contains Stmt[]
 * but itself is not a Stmt.
 *
 * The other one is Expr\\Closure, but that one can
 * at least be wrapped in Stmt\\Expression.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 21,
    'endLine' => 49,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'PhpParser\\Node\\Stmt',
    'implementsClassNames' => 
    array (
      0 => 'PHPStan\\Node\\VirtualNode',
    ),
    'traitClassNames' => 
    array (
    ),
    'immediateConstants' => 
    array (
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      '__construct' => 
      array (
        'name' => '__construct',
        'parameters' => 
        array (
          'propertyHook' => 
          array (
            'name' => 'propertyHook',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PhpParser\\Node\\PropertyHook',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 24,
            'endLine' => 24,
            'startColumn' => 30,
            'endColumn' => 55,
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
        'startLine' => 24,
        'endLine' => 27,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Node',
        'declaringClassName' => 'PHPStan\\Node\\PropertyHookStatementNode',
        'implementingClassName' => 'PHPStan\\Node\\PropertyHookStatementNode',
        'currentClassName' => 'PHPStan\\Node\\PropertyHookStatementNode',
        'aliasName' => NULL,
      ),
      'getReturnType' => 
      array (
        'name' => 'getReturnType',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @return null
 */',
        'startLine' => 32,
        'endLine' => 35,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Node',
        'declaringClassName' => 'PHPStan\\Node\\PropertyHookStatementNode',
        'implementingClassName' => 'PHPStan\\Node\\PropertyHookStatementNode',
        'currentClassName' => 'PHPStan\\Node\\PropertyHookStatementNode',
        'aliasName' => NULL,
      ),
      'getType' => 
      array (
        'name' => 'getType',
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
          0 => 
          array (
            'name' => 'Override',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => NULL,
        'startLine' => 37,
        'endLine' => 41,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Node',
        'declaringClassName' => 'PHPStan\\Node\\PropertyHookStatementNode',
        'implementingClassName' => 'PHPStan\\Node\\PropertyHookStatementNode',
        'currentClassName' => 'PHPStan\\Node\\PropertyHookStatementNode',
        'aliasName' => NULL,
      ),
      'getSubNodeNames' => 
      array (
        'name' => 'getSubNodeNames',
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
          0 => 
          array (
            'name' => 'Override',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => NULL,
        'startLine' => 43,
        'endLine' => 47,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Node',
        'declaringClassName' => 'PHPStan\\Node\\PropertyHookStatementNode',
        'implementingClassName' => 'PHPStan\\Node\\PropertyHookStatementNode',
        'currentClassName' => 'PHPStan\\Node\\PropertyHookStatementNode',
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