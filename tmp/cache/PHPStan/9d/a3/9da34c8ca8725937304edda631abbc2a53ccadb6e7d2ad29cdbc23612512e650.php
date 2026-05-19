<?php declare(strict_types = 1);

// osfsl-/home/runner/work/phpstan-src/phpstan-src/vendor/composer/../ondrejmirtes/better-reflection/src/Reflection/ReflectionType.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\BetterReflection\Reflection\ReflectionType
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-d1b4769cc928985bc66a4e53320552c691ba47225baaf755cea108d1135fcf59-8.4.21-6.70.0.1',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionType',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/vendor/composer/../ondrejmirtes/better-reflection/src/Reflection/ReflectionType.php',
      ),
    ),
    'namespace' => 'PHPStan\\BetterReflection\\Reflection',
    'name' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionType',
    'shortName' => 'ReflectionType',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 64,
    'docComment' => '/** @psalm-immutable */',
    'attributes' => 
    array (
    ),
    'startLine' => 15,
    'endLine' => 86,
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
      'createFromNode' => 
      array (
        'name' => 'createFromNode',
        'parameters' => 
        array (
          'reflector' => 
          array (
            'name' => 'reflector',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\BetterReflection\\Reflector\\Reflector',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 26,
            'endLine' => 26,
            'startColumn' => 9,
            'endColumn' => 28,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'owner' => 
          array (
            'name' => 'owner',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 27,
            'endLine' => 27,
            'startColumn' => 9,
            'endColumn' => 14,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'type' => 
          array (
            'name' => 'type',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 28,
            'endLine' => 28,
            'startColumn' => 9,
            'endColumn' => 13,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'allowsNull' => 
          array (
            'name' => 'allowsNull',
            'default' => 
            array (
              'code' => 'false',
              'attributes' => 
              array (
                'startLine' => 29,
                'endLine' => 29,
                'startTokenPos' => 83,
                'startFilePos' => 1240,
                'endTokenPos' => 83,
                'endFilePos' => 1244,
              ),
            ),
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
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 29,
            'endLine' => 29,
            'startColumn' => 9,
            'endColumn' => 32,
            'parameterIndex' => 3,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @internal
 *
 * @psalm-pure
 * @param \\PHPStan\\BetterReflection\\Reflection\\ReflectionParameter|\\PHPStan\\BetterReflection\\Reflection\\ReflectionMethod|\\PHPStan\\BetterReflection\\Reflection\\ReflectionFunction|\\PHPStan\\BetterReflection\\Reflection\\ReflectionEnum|\\PHPStan\\BetterReflection\\Reflection\\ReflectionProperty|\\PHPStan\\BetterReflection\\Reflection\\ReflectionClassConstant $owner
 * @param \\PhpParser\\Node\\Identifier|\\PhpParser\\Node\\Name|\\PhpParser\\Node\\NullableType|\\PhpParser\\Node\\UnionType|\\PhpParser\\Node\\IntersectionType $type
 * @return \\PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType|\\PHPStan\\BetterReflection\\Reflection\\ReflectionUnionType|\\PHPStan\\BetterReflection\\Reflection\\ReflectionIntersectionType
 */',
        'startLine' => 25,
        'endLine' => 73,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'PHPStan\\BetterReflection\\Reflection',
        'declaringClassName' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionType',
        'implementingClassName' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionType',
        'currentClassName' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionType',
        'aliasName' => NULL,
      ),
      'allowsNull' => 
      array (
        'name' => 'allowsNull',
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
 * Does the type allow null?
 */',
        'startLine' => 78,
        'endLine' => 78,
        'startColumn' => 5,
        'endColumn' => 48,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 65,
        'namespace' => 'PHPStan\\BetterReflection\\Reflection',
        'declaringClassName' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionType',
        'implementingClassName' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionType',
        'currentClassName' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionType',
        'aliasName' => NULL,
      ),
      '__toString' => 
      array (
        'name' => '__toString',
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
        'docComment' => '/**
 * Convert this string type to a string
 *
 * @return non-empty-string
 */',
        'startLine' => 85,
        'endLine' => 85,
        'startColumn' => 5,
        'endColumn' => 50,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 65,
        'namespace' => 'PHPStan\\BetterReflection\\Reflection',
        'declaringClassName' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionType',
        'implementingClassName' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionType',
        'currentClassName' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionType',
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