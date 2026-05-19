<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/src/Type/TypeWithClassName.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\Type\TypeWithClassName
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-8c58dd164d168e53cdf52e5e1619931445688741175eef221b394bf60fbaadca',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\Type\\TypeWithClassName',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/TypeWithClassName.php',
      ),
    ),
    'namespace' => 'PHPStan\\Type',
    'name' => 'PHPStan\\Type\\TypeWithClassName',
    'shortName' => 'TypeWithClassName',
    'isInterface' => true,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * A Type that represents an object with a known class name.
 *
 * Implemented by ObjectType, StaticType, ThisType, EnumCaseObjectType, ClosureType,
 * and GenericObjectType. Provides access to the class name and its ClassReflection.
 *
 * This interface is used when code needs to work with any object type that has a
 * specific class — for example, Scope::resolveTypeByName() returns TypeWithClassName
 * because the resolved type always has a known class.
 *
 * Note: Do not use `instanceof TypeWithClassName` to check if a type is an object.
 * Use `$type->getObjectClassNames()` or `$type->isObject()` instead, which correctly
 * handles union types and intersection types.
 *
 * @api
 * @api-do-not-implement
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 24,
    'endLine' => 36,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
      0 => 'PHPStan\\Type\\Type',
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
      'getClassName' => 
      array (
        'name' => 'getClassName',
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
        'startLine' => 27,
        'endLine' => 27,
        'startColumn' => 2,
        'endColumn' => 40,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type',
        'declaringClassName' => 'PHPStan\\Type\\TypeWithClassName',
        'implementingClassName' => 'PHPStan\\Type\\TypeWithClassName',
        'currentClassName' => 'PHPStan\\Type\\TypeWithClassName',
        'aliasName' => NULL,
      ),
      'getAncestorWithClassName' => 
      array (
        'name' => 'getAncestorWithClassName',
        'parameters' => 
        array (
          'className' => 
          array (
            'name' => 'className',
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
            'startLine' => 32,
            'endLine' => 32,
            'startColumn' => 43,
            'endColumn' => 59,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionUnionType',
          'data' => 
          array (
            'types' => 
            array (
              0 => 
              array (
                'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                'data' => 
                array (
                  'name' => 'self',
                  'isIdentifier' => false,
                ),
              ),
              1 => 
              array (
                'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                'data' => 
                array (
                  'name' => 'null',
                  'isIdentifier' => true,
                ),
              ),
            ),
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Returns this type projected onto an ancestor class, preserving generic type arguments.
 */',
        'startLine' => 32,
        'endLine' => 32,
        'startColumn' => 2,
        'endColumn' => 68,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type',
        'declaringClassName' => 'PHPStan\\Type\\TypeWithClassName',
        'implementingClassName' => 'PHPStan\\Type\\TypeWithClassName',
        'currentClassName' => 'PHPStan\\Type\\TypeWithClassName',
        'aliasName' => NULL,
      ),
      'getClassReflection' => 
      array (
        'name' => 'getClassReflection',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionUnionType',
          'data' => 
          array (
            'types' => 
            array (
              0 => 
              array (
                'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                'data' => 
                array (
                  'name' => 'PHPStan\\Reflection\\ClassReflection',
                  'isIdentifier' => false,
                ),
              ),
              1 => 
              array (
                'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                'data' => 
                array (
                  'name' => 'null',
                  'isIdentifier' => true,
                ),
              ),
            ),
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 34,
        'endLine' => 34,
        'startColumn' => 2,
        'endColumn' => 56,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type',
        'declaringClassName' => 'PHPStan\\Type\\TypeWithClassName',
        'implementingClassName' => 'PHPStan\\Type\\TypeWithClassName',
        'currentClassName' => 'PHPStan\\Type\\TypeWithClassName',
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