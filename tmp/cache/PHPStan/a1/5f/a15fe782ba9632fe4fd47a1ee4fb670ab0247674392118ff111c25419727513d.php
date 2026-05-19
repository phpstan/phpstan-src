<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/src/Reflection/Deprecation/ClassConstantDeprecationExtension.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\Reflection\Deprecation\ClassConstantDeprecationExtension
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-b391e88601ca07aaa73436eee3a395d4c29c94e916531b0b1235ed8759d9a8a9',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\Reflection\\Deprecation\\ClassConstantDeprecationExtension',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/src/Reflection/Deprecation/ClassConstantDeprecationExtension.php',
      ),
    ),
    'namespace' => 'PHPStan\\Reflection\\Deprecation',
    'name' => 'PHPStan\\Reflection\\Deprecation\\ClassConstantDeprecationExtension',
    'shortName' => 'ClassConstantDeprecationExtension',
    'isInterface' => true,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * This interface allows you to provide custom deprecation information
 *
 * To register it in the configuration file use the following tag:
 *
 * ```
 * services:
 * 	-
 *		class: App\\PHPStan\\MyProvider
 *		tags:
 *			- phpstan.classConstantDeprecationExtension
 * ```
 *
 * @api
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 22,
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
      'CLASS_CONSTANT_EXTENSION_TAG' => 
      array (
        'declaringClassName' => 'PHPStan\\Reflection\\Deprecation\\ClassConstantDeprecationExtension',
        'implementingClassName' => 'PHPStan\\Reflection\\Deprecation\\ClassConstantDeprecationExtension',
        'name' => 'CLASS_CONSTANT_EXTENSION_TAG',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'phpstan.classConstantDeprecationExtension\'',
          'attributes' => 
          array (
            'startLine' => 25,
            'endLine' => 25,
            'startTokenPos' => 37,
            'startFilePos' => 532,
            'endTokenPos' => 37,
            'endFilePos' => 574,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 25,
        'endLine' => 25,
        'startColumn' => 2,
        'endColumn' => 89,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'getClassConstantDeprecation' => 
      array (
        'name' => 'getClassConstantDeprecation',
        'parameters' => 
        array (
          'reflection' => 
          array (
            'name' => 'reflection',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClassConstant',
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
            'startColumn' => 46,
            'endColumn' => 80,
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
                  'name' => 'PHPStan\\Reflection\\Deprecation\\Deprecation',
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
        'startLine' => 27,
        'endLine' => 27,
        'startColumn' => 2,
        'endColumn' => 96,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection\\Deprecation',
        'declaringClassName' => 'PHPStan\\Reflection\\Deprecation\\ClassConstantDeprecationExtension',
        'implementingClassName' => 'PHPStan\\Reflection\\Deprecation\\ClassConstantDeprecationExtension',
        'currentClassName' => 'PHPStan\\Reflection\\Deprecation\\ClassConstantDeprecationExtension',
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