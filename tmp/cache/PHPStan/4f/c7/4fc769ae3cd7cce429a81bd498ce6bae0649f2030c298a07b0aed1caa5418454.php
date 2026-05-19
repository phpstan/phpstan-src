<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/src/Diagnose/DiagnoseExtension.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\Diagnose\DiagnoseExtension
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-e247d89ebf8d466773fa64f5a00f51f621acbd26ce4ee3c0de4ebda288ba6ad4',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\Diagnose\\DiagnoseExtension',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/src/Diagnose/DiagnoseExtension.php',
      ),
    ),
    'namespace' => 'PHPStan\\Diagnose',
    'name' => 'PHPStan\\Diagnose\\DiagnoseExtension',
    'shortName' => 'DiagnoseExtension',
    'isInterface' => true,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * DiagnoseExtension can output any diagnostic information to stderr after analysis.
 *
 * PHPStan displays this information when running the "analyse" command with "-vvv" CLI option.
 *
 * To register it in the configuration file use the `phpstan.diagnoseExtension` service tag:
 *
 * ```
 * services:
 * 	-
 *		class: App\\PHPStan\\MyExtension
 *		tags:
 *			- phpstan.diagnoseExtension
 * ```
 *
 * @api
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 24,
    'endLine' => 31,
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
        'declaringClassName' => 'PHPStan\\Diagnose\\DiagnoseExtension',
        'implementingClassName' => 'PHPStan\\Diagnose\\DiagnoseExtension',
        'name' => 'EXTENSION_TAG',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'phpstan.diagnoseExtension\'',
          'attributes' => 
          array (
            'startLine' => 27,
            'endLine' => 27,
            'startTokenPos' => 37,
            'startFilePos' => 566,
            'endTokenPos' => 37,
            'endFilePos' => 592,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 27,
        'endLine' => 27,
        'startColumn' => 2,
        'endColumn' => 58,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'print' => 
      array (
        'name' => 'print',
        'parameters' => 
        array (
          'output' => 
          array (
            'name' => 'output',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\Command\\Output',
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
            'startColumn' => 24,
            'endColumn' => 37,
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
        'startLine' => 29,
        'endLine' => 29,
        'startColumn' => 2,
        'endColumn' => 45,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Diagnose',
        'declaringClassName' => 'PHPStan\\Diagnose\\DiagnoseExtension',
        'implementingClassName' => 'PHPStan\\Diagnose\\DiagnoseExtension',
        'currentClassName' => 'PHPStan\\Diagnose\\DiagnoseExtension',
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