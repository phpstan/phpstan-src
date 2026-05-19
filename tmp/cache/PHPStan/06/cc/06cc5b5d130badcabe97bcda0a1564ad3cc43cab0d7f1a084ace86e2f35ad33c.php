<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/src/Rules/RestrictedUsage/RestrictedMethodUsageExtension.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\Rules\RestrictedUsage\RestrictedMethodUsageExtension
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-fc80c6eecb681494844e98dfa0f61211ac2a52a9d5f16b5e1b00dc9edaaa8378',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\Rules\\RestrictedUsage\\RestrictedMethodUsageExtension',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/src/Rules/RestrictedUsage/RestrictedMethodUsageExtension.php',
      ),
    ),
    'namespace' => 'PHPStan\\Rules\\RestrictedUsage',
    'name' => 'PHPStan\\Rules\\RestrictedUsage\\RestrictedMethodUsageExtension',
    'shortName' => 'RestrictedMethodUsageExtension',
    'isInterface' => true,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Extensions implementing this interface are called for each analysed method call.
 *
 * Extension can decide to create RestrictedUsage object
 * with error message & error identifier to be reported for this method call.
 *
 * Typical usage is to report errors for methods marked as @-deprecated or @-internal.
 *
 * To register it in the configuration file use the following tag:
 *
 * ```
 * services:
 * 	-
 *		class: App\\PHPStan\\MyExtension
 *		tags:
 *			- phpstan.restrictedMethodUsageExtension
 * ```
 *
 * @api
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 28,
    'endLine' => 38,
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
      'METHOD_EXTENSION_TAG' => 
      array (
        'declaringClassName' => 'PHPStan\\Rules\\RestrictedUsage\\RestrictedMethodUsageExtension',
        'implementingClassName' => 'PHPStan\\Rules\\RestrictedUsage\\RestrictedMethodUsageExtension',
        'name' => 'METHOD_EXTENSION_TAG',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'phpstan.restrictedMethodUsageExtension\'',
          'attributes' => 
          array (
            'startLine' => 31,
            'endLine' => 31,
            'startTokenPos' => 42,
            'startFilePos' => 763,
            'endTokenPos' => 42,
            'endFilePos' => 802,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 31,
        'endLine' => 31,
        'startColumn' => 2,
        'endColumn' => 78,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'isRestrictedMethodUsage' => 
      array (
        'name' => 'isRestrictedMethodUsage',
        'parameters' => 
        array (
          'methodReflection' => 
          array (
            'name' => 'methodReflection',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\Reflection\\ExtendedMethodReflection',
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
            'startColumn' => 3,
            'endColumn' => 44,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'scope' => 
          array (
            'name' => 'scope',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\Analyser\\Scope',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 35,
            'endLine' => 35,
            'startColumn' => 3,
            'endColumn' => 14,
            'parameterIndex' => 1,
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
                  'name' => 'PHPStan\\Rules\\RestrictedUsage\\RestrictedUsage',
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
        'startLine' => 33,
        'endLine' => 36,
        'startColumn' => 2,
        'endColumn' => 21,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Rules\\RestrictedUsage',
        'declaringClassName' => 'PHPStan\\Rules\\RestrictedUsage\\RestrictedMethodUsageExtension',
        'implementingClassName' => 'PHPStan\\Rules\\RestrictedUsage\\RestrictedMethodUsageExtension',
        'currentClassName' => 'PHPStan\\Rules\\RestrictedUsage\\RestrictedMethodUsageExtension',
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