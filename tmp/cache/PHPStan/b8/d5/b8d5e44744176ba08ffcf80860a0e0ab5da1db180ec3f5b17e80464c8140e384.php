<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/src/Reflection/Deprecation/PropertyDeprecationExtension.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\Reflection\Deprecation\PropertyDeprecationExtension
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-6ebe54c5bd9203e71ba03d290a90e2aa7e6efac1e1006b6313a2854e37a76ea1',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\Reflection\\Deprecation\\PropertyDeprecationExtension',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/src/Reflection/Deprecation/PropertyDeprecationExtension.php',
      ),
    ),
    'namespace' => 'PHPStan\\Reflection\\Deprecation',
    'name' => 'PHPStan\\Reflection\\Deprecation\\PropertyDeprecationExtension',
    'shortName' => 'PropertyDeprecationExtension',
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
 *			- phpstan.propertyDeprecationExtension
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
      'PROPERTY_EXTENSION_TAG' => 
      array (
        'declaringClassName' => 'PHPStan\\Reflection\\Deprecation\\PropertyDeprecationExtension',
        'implementingClassName' => 'PHPStan\\Reflection\\Deprecation\\PropertyDeprecationExtension',
        'name' => 'PROPERTY_EXTENSION_TAG',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'phpstan.propertyDeprecationExtension\'',
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
      'getPropertyDeprecation' => 
      array (
        'name' => 'getPropertyDeprecation',
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
                'name' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionProperty',
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
        'declaringClassName' => 'PHPStan\\Reflection\\Deprecation\\PropertyDeprecationExtension',
        'implementingClassName' => 'PHPStan\\Reflection\\Deprecation\\PropertyDeprecationExtension',
        'currentClassName' => 'PHPStan\\Reflection\\Deprecation\\PropertyDeprecationExtension',
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