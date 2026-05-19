<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/src/Reflection/AdditionalConstructorsExtension.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\Reflection\AdditionalConstructorsExtension
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-6d2c376e91fd878a87757a4c01eb63465fff2d191659605cd84491e85c0fb913',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\Reflection\\AdditionalConstructorsExtension',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/src/Reflection/AdditionalConstructorsExtension.php',
      ),
    ),
    'namespace' => 'PHPStan\\Reflection',
    'name' => 'PHPStan\\Reflection\\AdditionalConstructorsExtension',
    'shortName' => 'AdditionalConstructorsExtension',
    'isInterface' => true,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * This is the extension interface to implement if you want to dynamically
 * mark methods as constructor. As opposed to simply list them in the configuration file.
 *
 * To register it in the configuration file use the `phpstan.additionalConstructorsExtension` service tag:
 *
 * ```
 * services:
 * 	-
 *		class: App\\PHPStan\\MyExtension
 *		tags:
 *			- phpstan.additionalConstructorsExtension
 * ```
 *
 * @api
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 21,
    'endLine' => 29,
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
      'EXTENSION_TAG' => 
      array (
        'declaringClassName' => 'PHPStan\\Reflection\\AdditionalConstructorsExtension',
        'implementingClassName' => 'PHPStan\\Reflection\\AdditionalConstructorsExtension',
        'name' => 'EXTENSION_TAG',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'phpstan.additionalConstructorsExtension\'',
          'attributes' => 
          array (
            'startLine' => 24,
            'endLine' => 24,
            'startTokenPos' => 32,
            'startFilePos' => 562,
            'endTokenPos' => 32,
            'endFilePos' => 602,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 24,
        'endLine' => 24,
        'startColumn' => 2,
        'endColumn' => 72,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'getAdditionalConstructors' => 
      array (
        'name' => 'getAdditionalConstructors',
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
            'startLine' => 27,
            'endLine' => 27,
            'startColumn' => 44,
            'endColumn' => 75,
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
        'docComment' => '/** @return string[] */',
        'startLine' => 27,
        'endLine' => 27,
        'startColumn' => 2,
        'endColumn' => 84,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection',
        'declaringClassName' => 'PHPStan\\Reflection\\AdditionalConstructorsExtension',
        'implementingClassName' => 'PHPStan\\Reflection\\AdditionalConstructorsExtension',
        'currentClassName' => 'PHPStan\\Reflection\\AdditionalConstructorsExtension',
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