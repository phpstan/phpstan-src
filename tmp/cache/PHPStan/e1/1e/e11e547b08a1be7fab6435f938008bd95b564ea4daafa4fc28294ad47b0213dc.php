<?php declare(strict_types = 1);

// osfsl-/home/runner/work/phpstan-src/phpstan-src/vendor/composer/../ondrejmirtes/better-reflection/src/Reflector/Reflector.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\BetterReflection\Reflector\Reflector
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-b136933419c9396672fc043b99245cb6ba195f79c74d76e7916230f39594f93c-8.4.21-6.70.0.1',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\BetterReflection\\Reflector\\Reflector',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/vendor/composer/../ondrejmirtes/better-reflection/src/Reflector/Reflector.php',
      ),
    ),
    'namespace' => 'PHPStan\\BetterReflection\\Reflector',
    'name' => 'PHPStan\\BetterReflection\\Reflector\\Reflector',
    'shortName' => 'Reflector',
    'isInterface' => true,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => NULL,
    'attributes' => 
    array (
    ),
    'startLine' => 12,
    'endLine' => 55,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
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
      'reflectClass' => 
      array (
        'name' => 'reflectClass',
        'parameters' => 
        array (
          'identifierName' => 
          array (
            'name' => 'identifierName',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'string',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 19,
            'endLine' => 19,
            'startColumn' => 34,
            'endColumn' => 55,
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
            'name' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionClass',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Create a ReflectionClass for the specified $className.
 *
 * @throws IdentifierNotFound
 */',
        'startLine' => 19,
        'endLine' => 19,
        'startColumn' => 5,
        'endColumn' => 74,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\BetterReflection\\Reflector',
        'declaringClassName' => 'PHPStan\\BetterReflection\\Reflector\\Reflector',
        'implementingClassName' => 'PHPStan\\BetterReflection\\Reflector\\Reflector',
        'currentClassName' => 'PHPStan\\BetterReflection\\Reflector\\Reflector',
        'aliasName' => NULL,
      ),
      'reflectAllClasses' => 
      array (
        'name' => 'reflectAllClasses',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'iterable',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get all the classes available in the scope specified by the SourceLocator.
 *
 * @return list<ReflectionClass>
 */',
        'startLine' => 26,
        'endLine' => 26,
        'startColumn' => 5,
        'endColumn' => 50,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\BetterReflection\\Reflector',
        'declaringClassName' => 'PHPStan\\BetterReflection\\Reflector\\Reflector',
        'implementingClassName' => 'PHPStan\\BetterReflection\\Reflector\\Reflector',
        'currentClassName' => 'PHPStan\\BetterReflection\\Reflector\\Reflector',
        'aliasName' => NULL,
      ),
      'reflectFunction' => 
      array (
        'name' => 'reflectFunction',
        'parameters' => 
        array (
          'identifierName' => 
          array (
            'name' => 'identifierName',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'string',
                'isIdentifier' => true,
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
            'startColumn' => 37,
            'endColumn' => 58,
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
            'name' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionFunction',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Create a ReflectionFunction for the specified $functionName.
 *
 * @throws IdentifierNotFound
 */',
        'startLine' => 33,
        'endLine' => 33,
        'startColumn' => 5,
        'endColumn' => 80,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\BetterReflection\\Reflector',
        'declaringClassName' => 'PHPStan\\BetterReflection\\Reflector\\Reflector',
        'implementingClassName' => 'PHPStan\\BetterReflection\\Reflector\\Reflector',
        'currentClassName' => 'PHPStan\\BetterReflection\\Reflector\\Reflector',
        'aliasName' => NULL,
      ),
      'reflectAllFunctions' => 
      array (
        'name' => 'reflectAllFunctions',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'iterable',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get all the functions available in the scope specified by the SourceLocator.
 *
 * @return list<ReflectionFunction>
 */',
        'startLine' => 40,
        'endLine' => 40,
        'startColumn' => 5,
        'endColumn' => 52,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\BetterReflection\\Reflector',
        'declaringClassName' => 'PHPStan\\BetterReflection\\Reflector\\Reflector',
        'implementingClassName' => 'PHPStan\\BetterReflection\\Reflector\\Reflector',
        'currentClassName' => 'PHPStan\\BetterReflection\\Reflector\\Reflector',
        'aliasName' => NULL,
      ),
      'reflectConstant' => 
      array (
        'name' => 'reflectConstant',
        'parameters' => 
        array (
          'identifierName' => 
          array (
            'name' => 'identifierName',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'string',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 47,
            'endLine' => 47,
            'startColumn' => 37,
            'endColumn' => 58,
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
            'name' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionConstant',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Create a ReflectionConstant for the specified $constantName.
 *
 * @throws IdentifierNotFound
 */',
        'startLine' => 47,
        'endLine' => 47,
        'startColumn' => 5,
        'endColumn' => 80,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\BetterReflection\\Reflector',
        'declaringClassName' => 'PHPStan\\BetterReflection\\Reflector\\Reflector',
        'implementingClassName' => 'PHPStan\\BetterReflection\\Reflector\\Reflector',
        'currentClassName' => 'PHPStan\\BetterReflection\\Reflector\\Reflector',
        'aliasName' => NULL,
      ),
      'reflectAllConstants' => 
      array (
        'name' => 'reflectAllConstants',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'iterable',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get all the constants available in the scope specified by the SourceLocator.
 *
 * @return list<ReflectionConstant>
 */',
        'startLine' => 54,
        'endLine' => 54,
        'startColumn' => 5,
        'endColumn' => 52,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\BetterReflection\\Reflector',
        'declaringClassName' => 'PHPStan\\BetterReflection\\Reflector\\Reflector',
        'implementingClassName' => 'PHPStan\\BetterReflection\\Reflector\\Reflector',
        'currentClassName' => 'PHPStan\\BetterReflection\\Reflector\\Reflector',
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