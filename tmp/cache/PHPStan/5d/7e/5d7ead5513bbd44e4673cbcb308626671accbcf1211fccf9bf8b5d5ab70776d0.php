<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/src/Reflection/PropertyReflection.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\Reflection\PropertyReflection
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-5906a9a830b444ee0e302edf7c34650aeeacfd0f3a4691a00bbeb2bcc362d688',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\Reflection\\PropertyReflection',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/src/Reflection/PropertyReflection.php',
      ),
    ),
    'namespace' => 'PHPStan\\Reflection',
    'name' => 'PHPStan\\Reflection\\PropertyReflection',
    'shortName' => 'PropertyReflection',
    'isInterface' => true,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Reflection for a class property.
 *
 * This is the interface extension developers should implement when creating
 * custom PropertiesClassReflectionExtension implementations for magic properties.
 *
 * Properties have separate readable and writable types to support:
 * - Asymmetric types (PHP 8.4+ property hooks with different get/set types)
 * - Read-only properties (readable but not writable)
 * - Write-only properties (writable but not readable, rare)
 *
 * For additional property metadata (native types, PHPDoc types, hooks, attributes),
 * see ExtendedPropertyReflection which extends this interface.
 *
 * @api
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 24,
    'endLine' => 53,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
      0 => 'PHPStan\\Reflection\\ClassMemberReflection',
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
      'getReadableType' => 
      array (
        'name' => 'getReadableType',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPStan\\Type\\Type',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 27,
        'endLine' => 27,
        'startColumn' => 2,
        'endColumn' => 41,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection',
        'declaringClassName' => 'PHPStan\\Reflection\\PropertyReflection',
        'implementingClassName' => 'PHPStan\\Reflection\\PropertyReflection',
        'currentClassName' => 'PHPStan\\Reflection\\PropertyReflection',
        'aliasName' => NULL,
      ),
      'getWritableType' => 
      array (
        'name' => 'getWritableType',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPStan\\Type\\Type',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * May differ from the readable type for properties with asymmetric visibility
 * or property hooks with different get/set types.
 */',
        'startLine' => 33,
        'endLine' => 33,
        'startColumn' => 2,
        'endColumn' => 41,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection',
        'declaringClassName' => 'PHPStan\\Reflection\\PropertyReflection',
        'implementingClassName' => 'PHPStan\\Reflection\\PropertyReflection',
        'currentClassName' => 'PHPStan\\Reflection\\PropertyReflection',
        'aliasName' => NULL,
      ),
      'canChangeTypeAfterAssignment' => 
      array (
        'name' => 'canChangeTypeAfterAssignment',
        'parameters' => 
        array (
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
        'docComment' => '/**
 * Returns true when the readable and writable types are the same and no property hooks
 * transform the value — PHPStan can then narrow the property\'s type based on assignments.
 * Returns false when read and write types differ (e.g. `@property` with asymmetric types,
 * property hooks, virtual properties).
 */',
        'startLine' => 41,
        'endLine' => 41,
        'startColumn' => 2,
        'endColumn' => 54,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection',
        'declaringClassName' => 'PHPStan\\Reflection\\PropertyReflection',
        'implementingClassName' => 'PHPStan\\Reflection\\PropertyReflection',
        'currentClassName' => 'PHPStan\\Reflection\\PropertyReflection',
        'aliasName' => NULL,
      ),
      'isReadable' => 
      array (
        'name' => 'isReadable',
        'parameters' => 
        array (
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
        'startLine' => 43,
        'endLine' => 43,
        'startColumn' => 2,
        'endColumn' => 36,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection',
        'declaringClassName' => 'PHPStan\\Reflection\\PropertyReflection',
        'implementingClassName' => 'PHPStan\\Reflection\\PropertyReflection',
        'currentClassName' => 'PHPStan\\Reflection\\PropertyReflection',
        'aliasName' => NULL,
      ),
      'isWritable' => 
      array (
        'name' => 'isWritable',
        'parameters' => 
        array (
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
        'startLine' => 45,
        'endLine' => 45,
        'startColumn' => 2,
        'endColumn' => 36,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection',
        'declaringClassName' => 'PHPStan\\Reflection\\PropertyReflection',
        'implementingClassName' => 'PHPStan\\Reflection\\PropertyReflection',
        'currentClassName' => 'PHPStan\\Reflection\\PropertyReflection',
        'aliasName' => NULL,
      ),
      'isDeprecated' => 
      array (
        'name' => 'isDeprecated',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPStan\\TrinaryLogic',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 47,
        'endLine' => 47,
        'startColumn' => 2,
        'endColumn' => 46,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection',
        'declaringClassName' => 'PHPStan\\Reflection\\PropertyReflection',
        'implementingClassName' => 'PHPStan\\Reflection\\PropertyReflection',
        'currentClassName' => 'PHPStan\\Reflection\\PropertyReflection',
        'aliasName' => NULL,
      ),
      'getDeprecatedDescription' => 
      array (
        'name' => 'getDeprecatedDescription',
        'parameters' => 
        array (
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
                  'name' => 'string',
                  'isIdentifier' => true,
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
        'startLine' => 49,
        'endLine' => 49,
        'startColumn' => 2,
        'endColumn' => 53,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection',
        'declaringClassName' => 'PHPStan\\Reflection\\PropertyReflection',
        'implementingClassName' => 'PHPStan\\Reflection\\PropertyReflection',
        'currentClassName' => 'PHPStan\\Reflection\\PropertyReflection',
        'aliasName' => NULL,
      ),
      'isInternal' => 
      array (
        'name' => 'isInternal',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPStan\\TrinaryLogic',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 51,
        'endLine' => 51,
        'startColumn' => 2,
        'endColumn' => 44,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection',
        'declaringClassName' => 'PHPStan\\Reflection\\PropertyReflection',
        'implementingClassName' => 'PHPStan\\Reflection\\PropertyReflection',
        'currentClassName' => 'PHPStan\\Reflection\\PropertyReflection',
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