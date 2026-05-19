<?php declare(strict_types = 1);

// osfsl-/home/runner/work/phpstan-src/phpstan-src/vendor/composer/../nikic/php-parser/lib/PhpParser/Node/Expr/AssignRef.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PhpParser\Node\Expr\AssignRef
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-efb318cc50eca7d859113fa9424ff760e09bd055ffcf730dd713a339d1e6f94d-8.4.21-6.70.0.1',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PhpParser\\Node\\Expr\\AssignRef',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/vendor/composer/../nikic/php-parser/lib/PhpParser/Node/Expr/AssignRef.php',
      ),
    ),
    'namespace' => 'PhpParser\\Node\\Expr',
    'name' => 'PhpParser\\Node\\Expr\\AssignRef',
    'shortName' => 'AssignRef',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => NULL,
    'attributes' => 
    array (
    ),
    'startLine' => 7,
    'endLine' => 33,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'PhpParser\\Node\\Expr',
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
      'var' => 
      array (
        'declaringClassName' => 'PhpParser\\Node\\Expr\\AssignRef',
        'implementingClassName' => 'PhpParser\\Node\\Expr\\AssignRef',
        'name' => 'var',
        'modifiers' => 1,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PhpParser\\Node\\Expr',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => '/** @var Expr Variable reference is assigned to */',
        'attributes' => 
        array (
        ),
        'startLine' => 9,
        'endLine' => 9,
        'startColumn' => 5,
        'endColumn' => 21,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'expr' => 
      array (
        'declaringClassName' => 'PhpParser\\Node\\Expr\\AssignRef',
        'implementingClassName' => 'PhpParser\\Node\\Expr\\AssignRef',
        'name' => 'expr',
        'modifiers' => 1,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PhpParser\\Node\\Expr',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => '/** @var Expr Variable which is referenced */',
        'attributes' => 
        array (
        ),
        'startLine' => 11,
        'endLine' => 11,
        'startColumn' => 5,
        'endColumn' => 22,
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
      '__construct' => 
      array (
        'name' => '__construct',
        'parameters' => 
        array (
          'var' => 
          array (
            'name' => 'var',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PhpParser\\Node\\Expr',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 20,
            'endLine' => 20,
            'startColumn' => 33,
            'endColumn' => 41,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'expr' => 
          array (
            'name' => 'expr',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PhpParser\\Node\\Expr',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 20,
            'endLine' => 20,
            'startColumn' => 44,
            'endColumn' => 53,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'attributes' => 
          array (
            'name' => 'attributes',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 20,
                'endLine' => 20,
                'startTokenPos' => 71,
                'startFilePos' => 546,
                'endTokenPos' => 72,
                'endFilePos' => 547,
              ),
            ),
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
            'startLine' => 20,
            'endLine' => 20,
            'startColumn' => 56,
            'endColumn' => 77,
            'parameterIndex' => 2,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Constructs an assignment node.
 *
 * @param Expr $var Variable
 * @param Expr $expr Expression
 * @param array<string, mixed> $attributes Additional attributes
 */',
        'startLine' => 20,
        'endLine' => 24,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpParser\\Node\\Expr',
        'declaringClassName' => 'PhpParser\\Node\\Expr\\AssignRef',
        'implementingClassName' => 'PhpParser\\Node\\Expr\\AssignRef',
        'currentClassName' => 'PhpParser\\Node\\Expr\\AssignRef',
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
        ),
        'docComment' => NULL,
        'startLine' => 26,
        'endLine' => 28,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpParser\\Node\\Expr',
        'declaringClassName' => 'PhpParser\\Node\\Expr\\AssignRef',
        'implementingClassName' => 'PhpParser\\Node\\Expr\\AssignRef',
        'currentClassName' => 'PhpParser\\Node\\Expr\\AssignRef',
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
        ),
        'docComment' => NULL,
        'startLine' => 30,
        'endLine' => 32,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpParser\\Node\\Expr',
        'declaringClassName' => 'PhpParser\\Node\\Expr\\AssignRef',
        'implementingClassName' => 'PhpParser\\Node\\Expr\\AssignRef',
        'currentClassName' => 'PhpParser\\Node\\Expr\\AssignRef',
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