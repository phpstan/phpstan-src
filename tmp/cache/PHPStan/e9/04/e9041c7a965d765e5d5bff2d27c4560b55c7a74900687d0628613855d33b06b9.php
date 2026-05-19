<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/src/Reflection/Deprecation/FunctionDeprecationExtension.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\Reflection\Deprecation\FunctionDeprecationExtension
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-63405d85caf055160c9776e13c650f198571136ab0a99e17f4fda77fcf8b7838',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\Reflection\\Deprecation\\FunctionDeprecationExtension',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/src/Reflection/Deprecation/FunctionDeprecationExtension.php',
      ),
    ),
    'namespace' => 'PHPStan\\Reflection\\Deprecation',
    'name' => 'PHPStan\\Reflection\\Deprecation\\FunctionDeprecationExtension',
    'shortName' => 'FunctionDeprecationExtension',
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
 *			- phpstan.functionDeprecationExtension
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
      'FUNCTION_EXTENSION_TAG' => 
      array (
        'declaringClassName' => 'PHPStan\\Reflection\\Deprecation\\FunctionDeprecationExtension',
        'implementingClassName' => 'PHPStan\\Reflection\\Deprecation\\FunctionDeprecationExtension',
        'name' => 'FUNCTION_EXTENSION_TAG',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'phpstan.functionDeprecationExtension\'',
          'attributes' => 
          array (
            'startLine' => 25,
            'endLine' => 25,
            'startTokenPos' => 37,
            'startFilePos' => 511,
            'endTokenPos' => 37,
            'endFilePos' => 548,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 25,
        'endLine' => 25,
        'startColumn' => 2,
        'endColumn' => 78,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'getFunctionDeprecation' => 
      array (
        'name' => 'getFunctionDeprecation',
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
                'name' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionFunction',
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
            'startColumn' => 41,
            'endColumn' => 70,
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
        'endColumn' => 86,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection\\Deprecation',
        'declaringClassName' => 'PHPStan\\Reflection\\Deprecation\\FunctionDeprecationExtension',
        'implementingClassName' => 'PHPStan\\Reflection\\Deprecation\\FunctionDeprecationExtension',
        'currentClassName' => 'PHPStan\\Reflection\\Deprecation\\FunctionDeprecationExtension',
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