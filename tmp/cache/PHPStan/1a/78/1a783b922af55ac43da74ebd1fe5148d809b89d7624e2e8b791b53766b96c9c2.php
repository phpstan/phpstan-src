<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/src/Reflection/Type/UnresolvedMethodPrototypeReflection.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\Reflection\Type\UnresolvedMethodPrototypeReflection
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-9ba4b7ace7ae1821105ca824b29f5e8a0f93a54f5ef1bf01cf570e97be152b89',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\Reflection\\Type\\UnresolvedMethodPrototypeReflection',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/src/Reflection/Type/UnresolvedMethodPrototypeReflection.php',
      ),
    ),
    'namespace' => 'PHPStan\\Reflection\\Type',
    'name' => 'PHPStan\\Reflection\\Type\\UnresolvedMethodPrototypeReflection',
    'shortName' => 'UnresolvedMethodPrototypeReflection',
    'isInterface' => true,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Lazy method reflection that defers template type and static type resolution.
 *
 * When calling a method on a generic type, the method\'s parameter and return types
 * need to be transformed by substituting template type parameters with their concrete
 * arguments. This interface allows that resolution to be deferred and configured:
 *
 * - getNakedMethod() returns the method as declared (before template substitution)
 * - getTransformedMethod() returns the method with templates resolved
 * - doNotResolveTemplateTypeMapToBounds() prevents falling back to template bounds
 *   when concrete types are unknown (used during type inference)
 * - withCalledOnType() sets the type the method is being called on
 *
 * This exists primarily because of StaticType. ObjectType uses
 * CalledOnTypeUnresolvedMethodPrototypeReflection which has hardcoded logic
 * to transform static types. StaticType uses CallbackUnresolvedMethodPrototypeReflection
 * which accepts a custom callback for context-aware static type transformation.
 *
 * This is the return type of Type::getUnresolvedMethodPrototype().
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 28,
    'endLine' => 43,
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
        'startLine' => 31,
        'endLine' => 31,
        'startColumn' => 2,
        'endColumn' => 61,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection\\Type',
        'declaringClassName' => 'PHPStan\\Reflection\\Type\\UnresolvedMethodPrototypeReflection',
        'implementingClassName' => 'PHPStan\\Reflection\\Type\\UnresolvedMethodPrototypeReflection',
        'currentClassName' => 'PHPStan\\Reflection\\Type\\UnresolvedMethodPrototypeReflection',
        'aliasName' => NULL,
      ),
      'getNakedMethod' => 
      array (
        'name' => 'getNakedMethod',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPStan\\Reflection\\ExtendedMethodReflection',
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
        'endColumn' => 60,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection\\Type',
        'declaringClassName' => 'PHPStan\\Reflection\\Type\\UnresolvedMethodPrototypeReflection',
        'implementingClassName' => 'PHPStan\\Reflection\\Type\\UnresolvedMethodPrototypeReflection',
        'currentClassName' => 'PHPStan\\Reflection\\Type\\UnresolvedMethodPrototypeReflection',
        'aliasName' => NULL,
      ),
      'getTransformedMethod' => 
      array (
        'name' => 'getTransformedMethod',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPStan\\Reflection\\ExtendedMethodReflection',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Returns the method reflection with template types substituted from the
 * called-on type\'s generic arguments.
 */',
        'startLine' => 39,
        'endLine' => 39,
        'startColumn' => 2,
        'endColumn' => 66,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection\\Type',
        'declaringClassName' => 'PHPStan\\Reflection\\Type\\UnresolvedMethodPrototypeReflection',
        'implementingClassName' => 'PHPStan\\Reflection\\Type\\UnresolvedMethodPrototypeReflection',
        'currentClassName' => 'PHPStan\\Reflection\\Type\\UnresolvedMethodPrototypeReflection',
        'aliasName' => NULL,
      ),
      'withCalledOnType' => 
      array (
        'name' => 'withCalledOnType',
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
            'startLine' => 41,
            'endLine' => 41,
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
        'startLine' => 41,
        'endLine' => 41,
        'startColumn' => 2,
        'endColumn' => 52,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection\\Type',
        'declaringClassName' => 'PHPStan\\Reflection\\Type\\UnresolvedMethodPrototypeReflection',
        'implementingClassName' => 'PHPStan\\Reflection\\Type\\UnresolvedMethodPrototypeReflection',
        'currentClassName' => 'PHPStan\\Reflection\\Type\\UnresolvedMethodPrototypeReflection',
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