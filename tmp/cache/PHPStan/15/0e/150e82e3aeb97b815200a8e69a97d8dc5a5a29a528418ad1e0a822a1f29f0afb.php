<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/src/Rules/Constants/AlwaysUsedClassConstantsExtension.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\Rules\Constants\AlwaysUsedClassConstantsExtension
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-ea7805428bb5fbecdc6743b37debf671f31b6baf5fc03fbf6a7c12b9d573900f',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\Rules\\Constants\\AlwaysUsedClassConstantsExtension',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/src/Rules/Constants/AlwaysUsedClassConstantsExtension.php',
      ),
    ),
    'namespace' => 'PHPStan\\Rules\\Constants',
    'name' => 'PHPStan\\Rules\\Constants\\AlwaysUsedClassConstantsExtension',
    'shortName' => 'AlwaysUsedClassConstantsExtension',
    'isInterface' => true,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * This is the extension interface to implement if you want to describe
 * always-used class constant.
 *
 * To register it in the configuration file use the `phpstan.constants.alwaysUsedClassConstantsExtension` service tag:
 *
 * ```
 * services:
 * 	-
 *		class: App\\PHPStan\\MyExtension
 *		tags:
 *			- phpstan.constants.alwaysUsedClassConstantsExtension
 * ```
 *
 * Learn more: https://phpstan.org/developing-extensions/always-used-class-constants
 *
 * @api
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 25,
    'endLine' => 30,
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
      'isAlwaysUsed' => 
      array (
        'name' => 'isAlwaysUsed',
        'parameters' => 
        array (
          'constant' => 
          array (
            'name' => 'constant',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\Reflection\\ClassConstantReflection',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 28,
            'endLine' => 28,
            'startColumn' => 31,
            'endColumn' => 63,
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
        'startLine' => 28,
        'endLine' => 28,
        'startColumn' => 2,
        'endColumn' => 71,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Rules\\Constants',
        'declaringClassName' => 'PHPStan\\Rules\\Constants\\AlwaysUsedClassConstantsExtension',
        'implementingClassName' => 'PHPStan\\Rules\\Constants\\AlwaysUsedClassConstantsExtension',
        'currentClassName' => 'PHPStan\\Rules\\Constants\\AlwaysUsedClassConstantsExtension',
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