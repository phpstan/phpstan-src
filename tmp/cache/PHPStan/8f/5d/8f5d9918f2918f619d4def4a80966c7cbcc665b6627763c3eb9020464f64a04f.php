<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/src/Reflection/Type/UnresolvedPropertyPrototypeReflection.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\Reflection\Type\UnresolvedPropertyPrototypeReflection
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-9a37886b8f6d99fba5f89fe26f9096531ff7e5091ca19775afc7f175196ff9d6',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\Reflection\\Type\\UnresolvedPropertyPrototypeReflection',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/src/Reflection/Type/UnresolvedPropertyPrototypeReflection.php',
      ),
    ),
    'namespace' => 'PHPStan\\Reflection\\Type',
    'name' => 'PHPStan\\Reflection\\Type\\UnresolvedPropertyPrototypeReflection',
    'shortName' => 'UnresolvedPropertyPrototypeReflection',
    'isInterface' => true,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Lazy property reflection that defers template type and static type resolution.
 *
 * When accessing a property on a generic type, the property\'s types need to be
 * transformed by substituting template type parameters with their concrete arguments.
 * This interface allows that resolution to be deferred and configured:
 *
 * - getNakedProperty() returns the property as declared (before template substitution)
 * - getTransformedProperty() returns the property with templates resolved
 * - doNotResolveTemplateTypeMapToBounds() prevents falling back to template bounds
 *   when concrete types are unknown (used during type inference)
 * - withFechedOnType() sets the type the property is being accessed on
 *
 * This exists primarily because of StaticType. ObjectType uses
 * CalledOnTypeUnresolvedPropertyPrototypeReflection which has hardcoded logic
 * to transform static types. StaticType uses CallbackUnresolvedPropertyPrototypeReflection
 * which accepts a custom callback for context-aware static type transformation.
 *
 * This is the return type of Type::getUnresolvedPropertyPrototype(),
 * Type::getUnresolvedInstancePropertyPrototype(), and
 * Type::getUnresolvedStaticPropertyPrototype().
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 30,
    'endLine' => 45,
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
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'doNotResolveTemplateTypeMapToBounds' => 
      array (
        'name' => 'doNotResolveTemplateTypeMapToBounds',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'self',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 33,
        'endLine' => 33,
        'startColumn' => 2,
        'endColumn' => 61,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection\\Type',
        'declaringClassName' => 'PHPStan\\Reflection\\Type\\UnresolvedPropertyPrototypeReflection',
        'implementingClassName' => 'PHPStan\\Reflection\\Type\\UnresolvedPropertyPrototypeReflection',
        'currentClassName' => 'PHPStan\\Reflection\\Type\\UnresolvedPropertyPrototypeReflection',
        'aliasName' => NULL,
      ),
      'getNakedProperty' => 
      array (
        'name' => 'getNakedProperty',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPStan\\Reflection\\ExtendedPropertyReflection',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 35,
        'endLine' => 35,
        'startColumn' => 2,
        'endColumn' => 64,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection\\Type',
        'declaringClassName' => 'PHPStan\\Reflection\\Type\\UnresolvedPropertyPrototypeReflection',
        'implementingClassName' => 'PHPStan\\Reflection\\Type\\UnresolvedPropertyPrototypeReflection',
        'currentClassName' => 'PHPStan\\Reflection\\Type\\UnresolvedPropertyPrototypeReflection',
        'aliasName' => NULL,
      ),
      'getTransformedProperty' => 
      array (
        'name' => 'getTransformedProperty',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPStan\\Reflection\\ExtendedPropertyReflection',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Returns the property reflection with template types substituted from the
 * fetched-on type\'s generic arguments.
 */',
        'startLine' => 41,
        'endLine' => 41,
        'startColumn' => 2,
        'endColumn' => 70,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection\\Type',
        'declaringClassName' => 'PHPStan\\Reflection\\Type\\UnresolvedPropertyPrototypeReflection',
        'implementingClassName' => 'PHPStan\\Reflection\\Type\\UnresolvedPropertyPrototypeReflection',
        'currentClassName' => 'PHPStan\\Reflection\\Type\\UnresolvedPropertyPrototypeReflection',
        'aliasName' => NULL,
      ),
      'withFechedOnType' => 
      array (
        'name' => 'withFechedOnType',
        'parameters' => 
        array (
          'type' => 
          array (
            'name' => 'type',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\Type\\Type',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 43,
            'endLine' => 43,
            'startColumn' => 35,
            'endColumn' => 44,
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
            'name' => 'self',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 43,
        'endLine' => 43,
        'startColumn' => 2,
        'endColumn' => 52,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection\\Type',
        'declaringClassName' => 'PHPStan\\Reflection\\Type\\UnresolvedPropertyPrototypeReflection',
        'implementingClassName' => 'PHPStan\\Reflection\\Type\\UnresolvedPropertyPrototypeReflection',
        'currentClassName' => 'PHPStan\\Reflection\\Type\\UnresolvedPropertyPrototypeReflection',
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