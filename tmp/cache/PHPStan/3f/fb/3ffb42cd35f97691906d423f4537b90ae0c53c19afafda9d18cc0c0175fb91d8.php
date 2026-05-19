<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/src/Classes/ForbiddenClassNameExtension.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\Classes\ForbiddenClassNameExtension
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-dda6c87692e2e7cf2067fd6bf939b6d803d50457fee5d146e3e666a8e3e49a0f',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\Classes\\ForbiddenClassNameExtension',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/src/Classes/ForbiddenClassNameExtension.php',
      ),
    ),
    'namespace' => 'PHPStan\\Classes',
    'name' => 'PHPStan\\Classes\\ForbiddenClassNameExtension',
    'shortName' => 'ForbiddenClassNameExtension',
    'isInterface' => true,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * This is the extension interface to implement if you want to dynamically
 * add forbidden class prefixes to the ClassForbiddenNameCheck rule.
 *
 * The idea is that you want to report usages of classes that you\'re not supposed to use in application.
 * For example: Generated Doctrine proxies from their configured namespace.
 *
 * To register it in the configuration file use the `phpstan.forbiddenClassNamesExtension` service tag:
 *
 * ```
 * services:
 * 	-
 *		class: App\\PHPStan\\MyExtension
 *		tags:
 *			- phpstan.forbiddenClassNamesExtension
 * ```
 *
 * @api
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 24,
    'endLine' => 32,
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
        'declaringClassName' => 'PHPStan\\Classes\\ForbiddenClassNameExtension',
        'implementingClassName' => 'PHPStan\\Classes\\ForbiddenClassNameExtension',
        'name' => 'EXTENSION_TAG',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'phpstan.forbiddenClassNamesExtension\'',
          'attributes' => 
          array (
            'startLine' => 27,
            'endLine' => 27,
            'startTokenPos' => 32,
            'startFilePos' => 712,
            'endTokenPos' => 32,
            'endFilePos' => 749,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 27,
        'endLine' => 27,
        'startColumn' => 2,
        'endColumn' => 69,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'getClassPrefixes' => 
      array (
        'name' => 'getClassPrefixes',
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
        'docComment' => '/** @return array<string, string> */',
        'startLine' => 30,
        'endLine' => 30,
        'startColumn' => 2,
        'endColumn' => 43,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Classes',
        'declaringClassName' => 'PHPStan\\Classes\\ForbiddenClassNameExtension',
        'implementingClassName' => 'PHPStan\\Classes\\ForbiddenClassNameExtension',
        'currentClassName' => 'PHPStan\\Classes\\ForbiddenClassNameExtension',
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