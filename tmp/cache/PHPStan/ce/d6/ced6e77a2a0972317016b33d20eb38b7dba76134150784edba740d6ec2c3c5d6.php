<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/src/Rules/RestrictedUsage/RestrictedClassConstantUsageExtension.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\Rules\RestrictedUsage\RestrictedClassConstantUsageExtension
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-ff78cada8e36781e9c3ae6af98560c4f59d32531eb5f19acdc9e74c5b6729995',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\Rules\\RestrictedUsage\\RestrictedClassConstantUsageExtension',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/src/Rules/RestrictedUsage/RestrictedClassConstantUsageExtension.php',
      ),
    ),
    'namespace' => 'PHPStan\\Rules\\RestrictedUsage',
    'name' => 'PHPStan\\Rules\\RestrictedUsage\\RestrictedClassConstantUsageExtension',
    'shortName' => 'RestrictedClassConstantUsageExtension',
    'isInterface' => true,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Extensions implementing this interface are called for each analysed class constant access.
 *
 * Extension can decide to create RestrictedUsage object
 * with error message & error identifier to be reported for this class constant access.
 *
 * Typical usage is to report errors for constants marked as @-deprecated or @-internal.
 *
 * To register it in the configuration file use the following tag:
 *
 * ```
 * services:
 * 	-
 *		class: App\\PHPStan\\MyExtension
 *		tags:
 *			- phpstan.restrictedClassConstantUsageExtension
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
      'CLASS_CONSTANT_EXTENSION_TAG' => 
      array (
        'declaringClassName' => 'PHPStan\\Rules\\RestrictedUsage\\RestrictedClassConstantUsageExtension',
        'implementingClassName' => 'PHPStan\\Rules\\RestrictedUsage\\RestrictedClassConstantUsageExtension',
        'name' => 'CLASS_CONSTANT_EXTENSION_TAG',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'phpstan.restrictedClassConstantUsageExtension\'',
          'attributes' => 
          array (
            'startLine' => 31,
            'endLine' => 31,
            'startTokenPos' => 42,
            'startFilePos' => 806,
            'endTokenPos' => 42,
            'endFilePos' => 852,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 31,
        'endLine' => 31,
        'startColumn' => 2,
        'endColumn' => 93,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'isRestrictedClassConstantUsage' => 
      array (
        'name' => 'isRestrictedClassConstantUsage',
        'parameters' => 
        array (
          'constantReflection' => 
          array (
            'name' => 'constantReflection',
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
            'startLine' => 34,
            'endLine' => 34,
            'startColumn' => 3,
            'endColumn' => 45,
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
        'declaringClassName' => 'PHPStan\\Rules\\RestrictedUsage\\RestrictedClassConstantUsageExtension',
        'implementingClassName' => 'PHPStan\\Rules\\RestrictedUsage\\RestrictedClassConstantUsageExtension',
        'currentClassName' => 'PHPStan\\Rules\\RestrictedUsage\\RestrictedClassConstantUsageExtension',
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