<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/src/Reflection/Deprecation/ClassDeprecationExtension.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\Reflection\Deprecation\ClassDeprecationExtension
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-98422b4c8273913ee3303f8dd76c8b96f3e0c70ad9bd12ce638e637bebfb7dac',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\Reflection\\Deprecation\\ClassDeprecationExtension',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/src/Reflection/Deprecation/ClassDeprecationExtension.php',
      ),
    ),
    'namespace' => 'PHPStan\\Reflection\\Deprecation',
    'name' => 'PHPStan\\Reflection\\Deprecation\\ClassDeprecationExtension',
    'shortName' => 'ClassDeprecationExtension',
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
 *			- phpstan.classDeprecationExtension
 * ```
 *
 * @api
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 23,
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
      'CLASS_EXTENSION_TAG' => 
      array (
        'declaringClassName' => 'PHPStan\\Reflection\\Deprecation\\ClassDeprecationExtension',
        'implementingClassName' => 'PHPStan\\Reflection\\Deprecation\\ClassDeprecationExtension',
        'name' => 'CLASS_EXTENSION_TAG',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'phpstan.classDeprecationExtension\'',
          'attributes' => 
          array (
            'startLine' => 26,
            'endLine' => 26,
            'startTokenPos' => 42,
            'startFilePos' => 563,
            'endTokenPos' => 42,
            'endFilePos' => 597,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 26,
        'endLine' => 26,
        'startColumn' => 2,
        'endColumn' => 72,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'getClassDeprecation' => 
      array (
        'name' => 'getClassDeprecation',
        'parameters' => 
        array (
          'reflection' => 
          array (
            'name' => 'reflection',
            'default' => NULL,
            'type' => 
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
                      'name' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
                      'isIdentifier' => false,
                    ),
                  ),
                  1 => 
                  array (
                    'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                    'data' => 
                    array (
                      'name' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionEnum',
                      'isIdentifier' => false,
                    ),
                  ),
                ),
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
            'startColumn' => 38,
            'endColumn' => 79,
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
        'startLine' => 28,
        'endLine' => 28,
        'startColumn' => 2,
        'endColumn' => 95,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection\\Deprecation',
        'declaringClassName' => 'PHPStan\\Reflection\\Deprecation\\ClassDeprecationExtension',
        'implementingClassName' => 'PHPStan\\Reflection\\Deprecation\\ClassDeprecationExtension',
        'currentClassName' => 'PHPStan\\Reflection\\Deprecation\\ClassDeprecationExtension',
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