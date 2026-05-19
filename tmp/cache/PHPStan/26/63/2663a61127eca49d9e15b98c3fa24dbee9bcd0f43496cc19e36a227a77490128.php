<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/src/Rules/RestrictedUsage/RestrictedClassNameUsageExtension.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\Rules\RestrictedUsage\RestrictedClassNameUsageExtension
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-968b57441f5b293b490e2a8c36541ad4e00168b174aeb66a85cd92127d42ccbf',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\Rules\\RestrictedUsage\\RestrictedClassNameUsageExtension',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/src/Rules/RestrictedUsage/RestrictedClassNameUsageExtension.php',
      ),
    ),
    'namespace' => 'PHPStan\\Rules\\RestrictedUsage',
    'name' => 'PHPStan\\Rules\\RestrictedUsage\\RestrictedClassNameUsageExtension',
    'shortName' => 'RestrictedClassNameUsageExtension',
    'isInterface' => true,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Extensions implementing this interface are called for each analysed class name usage.
 *
 * Extension can decide to create RestrictedUsage object
 * with error message & error identifier to be reported for this method call.
 *
 * Typical usage is to report errors for class names marked as @-deprecated or @-internal.
 *
 * Extension can take advantage of the usage location information in the ClassNameUsageLocation object.
 *
 * To register the extension in the configuration file use the following tag:
 *
 * ```
 * services:
 * 	-
 *		class: App\\PHPStan\\MyExtension
 *		tags:
 *			- phpstan.restrictedClassNameUsageExtension
 * ```
 *
 * @api
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 31,
    'endLine' => 42,
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
      'CLASS_NAME_EXTENSION_TAG' => 
      array (
        'declaringClassName' => 'PHPStan\\Rules\\RestrictedUsage\\RestrictedClassNameUsageExtension',
        'implementingClassName' => 'PHPStan\\Rules\\RestrictedUsage\\RestrictedClassNameUsageExtension',
        'name' => 'CLASS_NAME_EXTENSION_TAG',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'phpstan.restrictedClassNameUsageExtension\'',
          'attributes' => 
          array (
            'startLine' => 34,
            'endLine' => 34,
            'startTokenPos' => 47,
            'startFilePos' => 933,
            'endTokenPos' => 47,
            'endFilePos' => 975,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 34,
        'endLine' => 34,
        'startColumn' => 2,
        'endColumn' => 85,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'isRestrictedClassNameUsage' => 
      array (
        'name' => 'isRestrictedClassNameUsage',
        'parameters' => 
        array (
          'classReflection' => 
          array (
            'name' => 'classReflection',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\Reflection\\ClassReflection',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 37,
            'endLine' => 37,
            'startColumn' => 3,
            'endColumn' => 34,
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
            'startLine' => 38,
            'endLine' => 38,
            'startColumn' => 3,
            'endColumn' => 14,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'location' => 
          array (
            'name' => 'location',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\Rules\\ClassNameUsageLocation',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 39,
            'endLine' => 39,
            'startColumn' => 3,
            'endColumn' => 34,
            'parameterIndex' => 2,
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
        'startLine' => 36,
        'endLine' => 40,
        'startColumn' => 2,
        'endColumn' => 21,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Rules\\RestrictedUsage',
        'declaringClassName' => 'PHPStan\\Rules\\RestrictedUsage\\RestrictedClassNameUsageExtension',
        'implementingClassName' => 'PHPStan\\Rules\\RestrictedUsage\\RestrictedClassNameUsageExtension',
        'currentClassName' => 'PHPStan\\Rules\\RestrictedUsage\\RestrictedClassNameUsageExtension',
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