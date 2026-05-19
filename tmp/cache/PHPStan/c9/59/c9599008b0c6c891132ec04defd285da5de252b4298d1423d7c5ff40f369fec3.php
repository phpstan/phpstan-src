<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/src/Reflection/Deprecation/EnumCaseDeprecationExtension.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\Reflection\Deprecation\EnumCaseDeprecationExtension
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-ad2caa2fa54c0e630cade6bd35b06010d40f8ee1f831bbf4ce9c5271722151e4',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\Reflection\\Deprecation\\EnumCaseDeprecationExtension',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/src/Reflection/Deprecation/EnumCaseDeprecationExtension.php',
      ),
    ),
    'namespace' => 'PHPStan\\Reflection\\Deprecation',
    'name' => 'PHPStan\\Reflection\\Deprecation\\EnumCaseDeprecationExtension',
    'shortName' => 'EnumCaseDeprecationExtension',
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
 *			- phpstan.enumCaseDeprecationExtension
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
      'ENUM_CASE_EXTENSION_TAG' => 
      array (
        'declaringClassName' => 'PHPStan\\Reflection\\Deprecation\\EnumCaseDeprecationExtension',
        'implementingClassName' => 'PHPStan\\Reflection\\Deprecation\\EnumCaseDeprecationExtension',
        'name' => 'ENUM_CASE_EXTENSION_TAG',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'phpstan.enumCaseDeprecationExtension\'',
          'attributes' => 
          array (
            'startLine' => 26,
            'endLine' => 26,
            'startTokenPos' => 42,
            'startFilePos' => 590,
            'endTokenPos' => 42,
            'endFilePos' => 627,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 26,
        'endLine' => 26,
        'startColumn' => 2,
        'endColumn' => 79,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'getEnumCaseDeprecation' => 
      array (
        'name' => 'getEnumCaseDeprecation',
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
                      'name' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionEnumUnitCase',
                      'isIdentifier' => false,
                    ),
                  ),
                  1 => 
                  array (
                    'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                    'data' => 
                    array (
                      'name' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionEnumBackedCase',
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
            'startColumn' => 41,
            'endColumn' => 99,
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
        'endColumn' => 115,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection\\Deprecation',
        'declaringClassName' => 'PHPStan\\Reflection\\Deprecation\\EnumCaseDeprecationExtension',
        'implementingClassName' => 'PHPStan\\Reflection\\Deprecation\\EnumCaseDeprecationExtension',
        'currentClassName' => 'PHPStan\\Reflection\\Deprecation\\EnumCaseDeprecationExtension',
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