<?php declare(strict_types = 1);

// osfsl-/home/runner/work/phpstan-src/phpstan-src/vendor/composer/../nikic/php-parser/lib/PhpParser/Node/Name/FullyQualified.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PhpParser\Node\Name\FullyQualified
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-8c9cf335d24d4586a327f48f9b8d778c17eaa83d206e51dbe13e7563e4478375-8.4.21-6.70.0.1',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PhpParser\\Node\\Name\\FullyQualified',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/vendor/composer/../nikic/php-parser/lib/PhpParser/Node/Name/FullyQualified.php',
      ),
    ),
    'namespace' => 'PhpParser\\Node\\Name',
    'name' => 'PhpParser\\Node\\Name\\FullyQualified',
    'shortName' => 'FullyQualified',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => NULL,
    'attributes' => 
    array (
    ),
    'startLine' => 5,
    'endLine' => 49,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'PhpParser\\Node\\Name',
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
    ),
    'immediateMethods' => 
    array (
      'isUnqualified' => 
      array (
        'name' => 'isUnqualified',
        'parameters' => 
        array (
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
        'docComment' => '/**
 * Checks whether the name is unqualified. (E.g. Name)
 *
 * @return bool Whether the name is unqualified
 */',
        'startLine' => 11,
        'endLine' => 13,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpParser\\Node\\Name',
        'declaringClassName' => 'PhpParser\\Node\\Name\\FullyQualified',
        'implementingClassName' => 'PhpParser\\Node\\Name\\FullyQualified',
        'currentClassName' => 'PhpParser\\Node\\Name\\FullyQualified',
        'aliasName' => NULL,
      ),
      'isQualified' => 
      array (
        'name' => 'isQualified',
        'parameters' => 
        array (
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
        'docComment' => '/**
 * Checks whether the name is qualified. (E.g. Name\\Name)
 *
 * @return bool Whether the name is qualified
 */',
        'startLine' => 20,
        'endLine' => 22,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpParser\\Node\\Name',
        'declaringClassName' => 'PhpParser\\Node\\Name\\FullyQualified',
        'implementingClassName' => 'PhpParser\\Node\\Name\\FullyQualified',
        'currentClassName' => 'PhpParser\\Node\\Name\\FullyQualified',
        'aliasName' => NULL,
      ),
      'isFullyQualified' => 
      array (
        'name' => 'isFullyQualified',
        'parameters' => 
        array (
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
        'docComment' => '/**
 * Checks whether the name is fully qualified. (E.g. \\Name)
 *
 * @return bool Whether the name is fully qualified
 */',
        'startLine' => 29,
        'endLine' => 31,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpParser\\Node\\Name',
        'declaringClassName' => 'PhpParser\\Node\\Name\\FullyQualified',
        'implementingClassName' => 'PhpParser\\Node\\Name\\FullyQualified',
        'currentClassName' => 'PhpParser\\Node\\Name\\FullyQualified',
        'aliasName' => NULL,
      ),
      'isRelative' => 
      array (
        'name' => 'isRelative',
        'parameters' => 
        array (
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
        'docComment' => '/**
 * Checks whether the name is explicitly relative to the current namespace. (E.g. namespace\\Name)
 *
 * @return bool Whether the name is relative
 */',
        'startLine' => 38,
        'endLine' => 40,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpParser\\Node\\Name',
        'declaringClassName' => 'PhpParser\\Node\\Name\\FullyQualified',
        'implementingClassName' => 'PhpParser\\Node\\Name\\FullyQualified',
        'currentClassName' => 'PhpParser\\Node\\Name\\FullyQualified',
        'aliasName' => NULL,
      ),
      'toCodeString' => 
      array (
        'name' => 'toCodeString',
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
        'startLine' => 42,
        'endLine' => 44,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpParser\\Node\\Name',
        'declaringClassName' => 'PhpParser\\Node\\Name\\FullyQualified',
        'implementingClassName' => 'PhpParser\\Node\\Name\\FullyQualified',
        'currentClassName' => 'PhpParser\\Node\\Name\\FullyQualified',
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
        'startLine' => 46,
        'endLine' => 48,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpParser\\Node\\Name',
        'declaringClassName' => 'PhpParser\\Node\\Name\\FullyQualified',
        'implementingClassName' => 'PhpParser\\Node\\Name\\FullyQualified',
        'currentClassName' => 'PhpParser\\Node\\Name\\FullyQualified',
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