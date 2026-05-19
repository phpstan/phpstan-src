<?php declare(strict_types = 1);

// osfsl-/home/runner/work/phpstan-src/phpstan-src/vendor/composer/../nikic/php-parser/lib/PhpParser/ErrorHandler/Collecting.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PhpParser\ErrorHandler\Collecting
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-f111088d9f324ae70ff85af766e03f8d91e13f6c98ec5f1aa524f7efd131276d-8.4.21-6.70.0.1',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PhpParser\\ErrorHandler\\Collecting',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/vendor/composer/../nikic/php-parser/lib/PhpParser/ErrorHandler/Collecting.php',
      ),
    ),
    'namespace' => 'PhpParser\\ErrorHandler',
    'name' => 'PhpParser\\ErrorHandler\\Collecting',
    'shortName' => 'Collecting',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Error handler that collects all errors into an array.
 *
 * This allows graceful handling of errors.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 13,
    'endLine' => 43,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
      0 => 'PhpParser\\ErrorHandler',
    ),
    'traitClassNames' => 
    array (
    ),
    'immediateConstants' => 
    array (
    ),
    'immediateProperties' => 
    array (
      'errors' => 
      array (
        'declaringClassName' => 'PhpParser\\ErrorHandler\\Collecting',
        'implementingClassName' => 'PhpParser\\ErrorHandler\\Collecting',
        'name' => 'errors',
        'modifiers' => 4,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'default' => 
        array (
          'code' => '[]',
          'attributes' => 
          array (
            'startLine' => 15,
            'endLine' => 15,
            'startTokenPos' => 46,
            'startFilePos' => 341,
            'endTokenPos' => 47,
            'endFilePos' => 342,
          ),
        ),
        'docComment' => '/** @var Error[] Collected errors */',
        'attributes' => 
        array (
        ),
        'startLine' => 15,
        'endLine' => 15,
        'startColumn' => 5,
        'endColumn' => 31,
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
      'handleError' => 
      array (
        'name' => 'handleError',
        'parameters' => 
        array (
          'error' => 
          array (
            'name' => 'error',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PhpParser\\Error',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 17,
            'endLine' => 17,
            'startColumn' => 33,
            'endColumn' => 44,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 17,
        'endLine' => 19,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpParser\\ErrorHandler',
        'declaringClassName' => 'PhpParser\\ErrorHandler\\Collecting',
        'implementingClassName' => 'PhpParser\\ErrorHandler\\Collecting',
        'currentClassName' => 'PhpParser\\ErrorHandler\\Collecting',
        'aliasName' => NULL,
      ),
      'getErrors' => 
      array (
        'name' => 'getErrors',
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
        'docComment' => '/**
 * Get collected errors.
 *
 * @return Error[]
 */',
        'startLine' => 26,
        'endLine' => 28,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpParser\\ErrorHandler',
        'declaringClassName' => 'PhpParser\\ErrorHandler\\Collecting',
        'implementingClassName' => 'PhpParser\\ErrorHandler\\Collecting',
        'currentClassName' => 'PhpParser\\ErrorHandler\\Collecting',
        'aliasName' => NULL,
      ),
      'hasErrors' => 
      array (
        'name' => 'hasErrors',
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
 * Check whether there are any errors.
 */',
        'startLine' => 33,
        'endLine' => 35,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpParser\\ErrorHandler',
        'declaringClassName' => 'PhpParser\\ErrorHandler\\Collecting',
        'implementingClassName' => 'PhpParser\\ErrorHandler\\Collecting',
        'currentClassName' => 'PhpParser\\ErrorHandler\\Collecting',
        'aliasName' => NULL,
      ),
      'clearErrors' => 
      array (
        'name' => 'clearErrors',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Reset/clear collected errors.
 */',
        'startLine' => 40,
        'endLine' => 42,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpParser\\ErrorHandler',
        'declaringClassName' => 'PhpParser\\ErrorHandler\\Collecting',
        'implementingClassName' => 'PhpParser\\ErrorHandler\\Collecting',
        'currentClassName' => 'PhpParser\\ErrorHandler\\Collecting',
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