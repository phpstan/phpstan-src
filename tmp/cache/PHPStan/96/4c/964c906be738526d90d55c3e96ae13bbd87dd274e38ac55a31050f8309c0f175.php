<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/src/DependencyInjection/Type/LazyDynamicReturnTypeExtensionRegistryProvider.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\DependencyInjection\Type\LazyDynamicReturnTypeExtensionRegistryProvider
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-3b21b7590b4bc3f610b047af15d895e49c26846095f4907ba2da4476a0c496f7',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\DependencyInjection\\Type\\LazyDynamicReturnTypeExtensionRegistryProvider',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/src/DependencyInjection/Type/LazyDynamicReturnTypeExtensionRegistryProvider.php',
      ),
    ),
    'namespace' => 'PHPStan\\DependencyInjection\\Type',
    'name' => 'PHPStan\\DependencyInjection\\Type\\LazyDynamicReturnTypeExtensionRegistryProvider',
    'shortName' => 'LazyDynamicReturnTypeExtensionRegistryProvider',
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
            'code' => '\\PHPStan\\DependencyInjection\\Type\\DynamicReturnTypeExtensionRegistryProvider::class',
            'attributes' => 
            array (
              'startLine' => 11,
              'endLine' => 11,
              'startTokenPos' => 47,
              'startFilePos' => 326,
              'endTokenPos' => 49,
              'endFilePos' => 374,
            ),
          ),
        ),
      ),
    ),
    'startLine' => 11,
    'endLine' => 31,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
      0 => 'PHPStan\\DependencyInjection\\Type\\DynamicReturnTypeExtensionRegistryProvider',
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
        'declaringClassName' => 'PHPStan\\DependencyInjection\\Type\\LazyDynamicReturnTypeExtensionRegistryProvider',
        'implementingClassName' => 'PHPStan\\DependencyInjection\\Type\\LazyDynamicReturnTypeExtensionRegistryProvider',
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
                  'name' => 'PHPStan\\Type\\DynamicReturnTypeExtensionRegistry',
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
            'startLine' => 15,
            'endLine' => 15,
            'startTokenPos' => 74,
            'startFilePos' => 551,
            'endTokenPos' => 74,
            'endFilePos' => 554,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 15,
        'endLine' => 15,
        'startColumn' => 2,
        'endColumn' => 62,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'container' => 
      array (
        'declaringClassName' => 'PHPStan\\DependencyInjection\\Type\\LazyDynamicReturnTypeExtensionRegistryProvider',
        'implementingClassName' => 'PHPStan\\DependencyInjection\\Type\\LazyDynamicReturnTypeExtensionRegistryProvider',
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
        'startLine' => 17,
        'endLine' => 17,
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
            'startLine' => 17,
            'endLine' => 17,
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
        'startLine' => 17,
        'endLine' => 19,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\DependencyInjection\\Type',
        'declaringClassName' => 'PHPStan\\DependencyInjection\\Type\\LazyDynamicReturnTypeExtensionRegistryProvider',
        'implementingClassName' => 'PHPStan\\DependencyInjection\\Type\\LazyDynamicReturnTypeExtensionRegistryProvider',
        'currentClassName' => 'PHPStan\\DependencyInjection\\Type\\LazyDynamicReturnTypeExtensionRegistryProvider',
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
            'name' => 'PHPStan\\Type\\DynamicReturnTypeExtensionRegistry',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 21,
        'endLine' => 29,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\DependencyInjection\\Type',
        'declaringClassName' => 'PHPStan\\DependencyInjection\\Type\\LazyDynamicReturnTypeExtensionRegistryProvider',
        'implementingClassName' => 'PHPStan\\DependencyInjection\\Type\\LazyDynamicReturnTypeExtensionRegistryProvider',
        'currentClassName' => 'PHPStan\\DependencyInjection\\Type\\LazyDynamicReturnTypeExtensionRegistryProvider',
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