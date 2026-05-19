<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/src/DependencyInjection/Reflection/LazyClassReflectionExtensionRegistryProvider.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\DependencyInjection\Reflection\LazyClassReflectionExtensionRegistryProvider
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-68bf7ae854d0505e8fb866262a024cb6df7c954d73339bc8c5e50b8fa43838ee',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\DependencyInjection\\Reflection\\LazyClassReflectionExtensionRegistryProvider',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/src/DependencyInjection/Reflection/LazyClassReflectionExtensionRegistryProvider.php',
      ),
    ),
    'namespace' => 'PHPStan\\DependencyInjection\\Reflection',
    'name' => 'PHPStan\\DependencyInjection\\Reflection\\LazyClassReflectionExtensionRegistryProvider',
    'shortName' => 'LazyClassReflectionExtensionRegistryProvider',
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
            'code' => '\\PHPStan\\DependencyInjection\\Reflection\\ClassReflectionExtensionRegistryProvider::class',
            'attributes' => 
            array (
              'startLine' => 20,
              'endLine' => 20,
              'startTokenPos' => 94,
              'startFilePos' => 999,
              'endTokenPos' => 96,
              'endFilePos' => 1045,
            ),
          ),
        ),
      ),
    ),
    'startLine' => 20,
    'endLine' => 54,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
      0 => 'PHPStan\\DependencyInjection\\Reflection\\ClassReflectionExtensionRegistryProvider',
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
        'declaringClassName' => 'PHPStan\\DependencyInjection\\Reflection\\LazyClassReflectionExtensionRegistryProvider',
        'implementingClassName' => 'PHPStan\\DependencyInjection\\Reflection\\LazyClassReflectionExtensionRegistryProvider',
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
                  'name' => 'PHPStan\\Reflection\\ClassReflectionExtensionRegistry',
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
            'startLine' => 24,
            'endLine' => 24,
            'startTokenPos' => 121,
            'startFilePos' => 1216,
            'endTokenPos' => 121,
            'endFilePos' => 1219,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 24,
        'endLine' => 24,
        'startColumn' => 2,
        'endColumn' => 60,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'container' => 
      array (
        'declaringClassName' => 'PHPStan\\DependencyInjection\\Reflection\\LazyClassReflectionExtensionRegistryProvider',
        'implementingClassName' => 'PHPStan\\DependencyInjection\\Reflection\\LazyClassReflectionExtensionRegistryProvider',
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
        'startLine' => 26,
        'endLine' => 26,
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
            'startLine' => 26,
            'endLine' => 26,
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
        'startLine' => 26,
        'endLine' => 28,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\DependencyInjection\\Reflection',
        'declaringClassName' => 'PHPStan\\DependencyInjection\\Reflection\\LazyClassReflectionExtensionRegistryProvider',
        'implementingClassName' => 'PHPStan\\DependencyInjection\\Reflection\\LazyClassReflectionExtensionRegistryProvider',
        'currentClassName' => 'PHPStan\\DependencyInjection\\Reflection\\LazyClassReflectionExtensionRegistryProvider',
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
            'name' => 'PHPStan\\Reflection\\ClassReflectionExtensionRegistry',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 30,
        'endLine' => 52,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\DependencyInjection\\Reflection',
        'declaringClassName' => 'PHPStan\\DependencyInjection\\Reflection\\LazyClassReflectionExtensionRegistryProvider',
        'implementingClassName' => 'PHPStan\\DependencyInjection\\Reflection\\LazyClassReflectionExtensionRegistryProvider',
        'currentClassName' => 'PHPStan\\DependencyInjection\\Reflection\\LazyClassReflectionExtensionRegistryProvider',
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