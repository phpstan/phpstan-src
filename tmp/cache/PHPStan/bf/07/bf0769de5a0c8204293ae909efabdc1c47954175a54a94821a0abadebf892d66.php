<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/src/Reflection/AllowedSubTypesClassReflectionExtension.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\Reflection\AllowedSubTypesClassReflectionExtension
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-54d9a7dea5a269941e8c2906a888a2251af2acf62ec32223639e2ef88890a744',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\Reflection\\AllowedSubTypesClassReflectionExtension',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/src/Reflection/AllowedSubTypesClassReflectionExtension.php',
      ),
    ),
    'namespace' => 'PHPStan\\Reflection',
    'name' => 'PHPStan\\Reflection\\AllowedSubTypesClassReflectionExtension',
    'shortName' => 'AllowedSubTypesClassReflectionExtension',
    'isInterface' => true,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * This is the extension interface to implement if you want to described
 * allowed subtypes - to limit which classes can implement a certain interface
 * or extend a certain parent class.
 *
 * To register it in the configuration file use the `phpstan.broker.allowedSubTypesClassReflectionExtension` service tag:
 *
 * ```
 * services:
 * 	-
 *		class: App\\PHPStan\\MyExtension
 *		tags:
 *			- phpstan.broker.allowedSubTypesClassReflectionExtension
 * ```
 *
 * Learn more: https://phpstan.org/developing-extensions/allowed-subtypes
 *
 * @api
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 26,
    'endLine' => 36,
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
      'supports' => 
      array (
        'name' => 'supports',
        'parameters' => 
        array (
          'classReflection' => 
          array (
            'name' => 'classReflection',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\Reflection\\ClassReflection',
                'isIdentifier' => false,
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
            'startColumn' => 27,
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
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 29,
        'endLine' => 29,
        'startColumn' => 2,
        'endColumn' => 66,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection',
        'declaringClassName' => 'PHPStan\\Reflection\\AllowedSubTypesClassReflectionExtension',
        'implementingClassName' => 'PHPStan\\Reflection\\AllowedSubTypesClassReflectionExtension',
        'currentClassName' => 'PHPStan\\Reflection\\AllowedSubTypesClassReflectionExtension',
        'aliasName' => NULL,
      ),
      'getAllowedSubTypes' => 
      array (
        'name' => 'getAllowedSubTypes',
        'parameters' => 
        array (
          'classReflection' => 
          array (
            'name' => 'classReflection',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\Reflection\\ClassReflection',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 34,
            'endLine' => 34,
            'startColumn' => 37,
            'endColumn' => 68,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @return array<Type>
 */',
        'startLine' => 34,
        'endLine' => 34,
        'startColumn' => 2,
        'endColumn' => 77,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection',
        'declaringClassName' => 'PHPStan\\Reflection\\AllowedSubTypesClassReflectionExtension',
        'implementingClassName' => 'PHPStan\\Reflection\\AllowedSubTypesClassReflectionExtension',
        'currentClassName' => 'PHPStan\\Reflection\\AllowedSubTypesClassReflectionExtension',
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