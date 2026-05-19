<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/src/DependencyInjection/Type/LazyOperatorTypeSpecifyingExtensionRegistryProvider.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\DependencyInjection\Type\LazyOperatorTypeSpecifyingExtensionRegistryProvider
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-7508615a9680136699d9c7a227db018b1e3ec8d11feac60ad5c864023007991b',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\DependencyInjection\\Type\\LazyOperatorTypeSpecifyingExtensionRegistryProvider',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/src/DependencyInjection/Type/LazyOperatorTypeSpecifyingExtensionRegistryProvider.php',
      ),
    ),
    'namespace' => 'PHPStan\\DependencyInjection\\Type',
    'name' => 'PHPStan\\DependencyInjection\\Type\\LazyOperatorTypeSpecifyingExtensionRegistryProvider',
    'shortName' => 'LazyOperatorTypeSpecifyingExtensionRegistryProvider',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => NULL,
    'attributes' => 
    array (
      0 => 
      array (
        'name' => 'PHPStan\\DependencyInjection\\AutowiredService',
        'isRepeated' => false,
        'arguments' => 
        array (
          'as' => 
          array (
            'code' => '\\PHPStan\\DependencyInjection\\Type\\OperatorTypeSpecifyingExtensionRegistryProvider::class',
            'attributes' => 
            array (
              'startLine' => 10,
              'endLine' => 10,
              'startTokenPos' => 42,
              'startFilePos' => 288,
              'endTokenPos' => 44,
              'endFilePos' => 341,
            ),
          ),
        ),
      ),
    ),
    'startLine' => 10,
    'endLine' => 27,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
      0 => 'PHPStan\\DependencyInjection\\Type\\OperatorTypeSpecifyingExtensionRegistryProvider',
    ),
    'traitClassNames' => 
    array (
    ),
    'immediateConstants' => 
    array (
    ),
    'immediateProperties' => 
    array (
      'registry' => 
      array (
        'declaringClassName' => 'PHPStan\\DependencyInjection\\Type\\LazyOperatorTypeSpecifyingExtensionRegistryProvider',
        'implementingClassName' => 'PHPStan\\DependencyInjection\\Type\\LazyOperatorTypeSpecifyingExtensionRegistryProvider',
        'name' => 'registry',
        'modifiers' => 4,
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
                  'name' => 'PHPStan\\Type\\OperatorTypeSpecifyingExtensionRegistry',
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
        'default' => 
        array (
          'code' => 'null',
          'attributes' => 
          array (
            'startLine' => 14,
            'endLine' => 14,
            'startTokenPos' => 69,
            'startFilePos' => 533,
            'endTokenPos' => 69,
            'endFilePos' => 536,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 14,
        'endLine' => 14,
        'startColumn' => 2,
        'endColumn' => 67,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'container' => 
      array (
        'declaringClassName' => 'PHPStan\\DependencyInjection\\Type\\LazyOperatorTypeSpecifyingExtensionRegistryProvider',
        'implementingClassName' => 'PHPStan\\DependencyInjection\\Type\\LazyOperatorTypeSpecifyingExtensionRegistryProvider',
        'name' => 'container',
        'modifiers' => 4,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPStan\\DependencyInjection\\Container',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 16,
        'endLine' => 16,
        'startColumn' => 30,
        'endColumn' => 57,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
    ),
    'immediateMethods' => 
    array (
      '__construct' => 
      array (
        'name' => '__construct',
        'parameters' => 
        array (
          'container' => 
          array (
            'name' => 'container',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\DependencyInjection\\Container',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 16,
            'endLine' => 16,
            'startColumn' => 30,
            'endColumn' => 57,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 16,
        'endLine' => 18,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\DependencyInjection\\Type',
        'declaringClassName' => 'PHPStan\\DependencyInjection\\Type\\LazyOperatorTypeSpecifyingExtensionRegistryProvider',
        'implementingClassName' => 'PHPStan\\DependencyInjection\\Type\\LazyOperatorTypeSpecifyingExtensionRegistryProvider',
        'currentClassName' => 'PHPStan\\DependencyInjection\\Type\\LazyOperatorTypeSpecifyingExtensionRegistryProvider',
        'aliasName' => NULL,
      ),
      'getRegistry' => 
      array (
        'name' => 'getRegistry',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPStan\\Type\\OperatorTypeSpecifyingExtensionRegistry',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 20,
        'endLine' => 25,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\DependencyInjection\\Type',
        'declaringClassName' => 'PHPStan\\DependencyInjection\\Type\\LazyOperatorTypeSpecifyingExtensionRegistryProvider',
        'implementingClassName' => 'PHPStan\\DependencyInjection\\Type\\LazyOperatorTypeSpecifyingExtensionRegistryProvider',
        'currentClassName' => 'PHPStan\\DependencyInjection\\Type\\LazyOperatorTypeSpecifyingExtensionRegistryProvider',
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