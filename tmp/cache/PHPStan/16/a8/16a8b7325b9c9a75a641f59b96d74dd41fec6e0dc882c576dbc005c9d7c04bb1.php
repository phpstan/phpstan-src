<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/src/PhpDoc/LazyTypeNodeResolverExtensionRegistryProvider.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\PhpDoc\LazyTypeNodeResolverExtensionRegistryProvider
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-836403036cf4d67e0b82685ed73e42a31a65a3875ad9931e1b953292e5f42e93',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\PhpDoc\\LazyTypeNodeResolverExtensionRegistryProvider',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/src/PhpDoc/LazyTypeNodeResolverExtensionRegistryProvider.php',
      ),
    ),
    'namespace' => 'PHPStan\\PhpDoc',
    'name' => 'PHPStan\\PhpDoc\\LazyTypeNodeResolverExtensionRegistryProvider',
    'shortName' => 'LazyTypeNodeResolverExtensionRegistryProvider',
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
            'code' => '\\PHPStan\\PhpDoc\\TypeNodeResolverExtensionRegistryProvider::class',
            'attributes' => 
            array (
              'startLine' => 8,
              'endLine' => 8,
              'startTokenPos' => 32,
              'startFilePos' => 178,
              'endTokenPos' => 34,
              'endFilePos' => 225,
            ),
          ),
        ),
      ),
    ),
    'startLine' => 8,
    'endLine' => 26,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
      0 => 'PHPStan\\PhpDoc\\TypeNodeResolverExtensionRegistryProvider',
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
        'declaringClassName' => 'PHPStan\\PhpDoc\\LazyTypeNodeResolverExtensionRegistryProvider',
        'implementingClassName' => 'PHPStan\\PhpDoc\\LazyTypeNodeResolverExtensionRegistryProvider',
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
                  'name' => 'PHPStan\\PhpDoc\\TypeNodeResolverExtensionRegistry',
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
            'startLine' => 12,
            'endLine' => 12,
            'startTokenPos' => 59,
            'startFilePos' => 399,
            'endTokenPos' => 59,
            'endFilePos' => 402,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 12,
        'endLine' => 12,
        'startColumn' => 2,
        'endColumn' => 61,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'container' => 
      array (
        'declaringClassName' => 'PHPStan\\PhpDoc\\LazyTypeNodeResolverExtensionRegistryProvider',
        'implementingClassName' => 'PHPStan\\PhpDoc\\LazyTypeNodeResolverExtensionRegistryProvider',
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
        'startLine' => 14,
        'endLine' => 14,
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
            'startLine' => 14,
            'endLine' => 14,
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
        'startLine' => 14,
        'endLine' => 16,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\PhpDoc',
        'declaringClassName' => 'PHPStan\\PhpDoc\\LazyTypeNodeResolverExtensionRegistryProvider',
        'implementingClassName' => 'PHPStan\\PhpDoc\\LazyTypeNodeResolverExtensionRegistryProvider',
        'currentClassName' => 'PHPStan\\PhpDoc\\LazyTypeNodeResolverExtensionRegistryProvider',
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
            'name' => 'PHPStan\\PhpDoc\\TypeNodeResolverExtensionRegistry',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 18,
        'endLine' => 24,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\PhpDoc',
        'declaringClassName' => 'PHPStan\\PhpDoc\\LazyTypeNodeResolverExtensionRegistryProvider',
        'implementingClassName' => 'PHPStan\\PhpDoc\\LazyTypeNodeResolverExtensionRegistryProvider',
        'currentClassName' => 'PHPStan\\PhpDoc\\LazyTypeNodeResolverExtensionRegistryProvider',
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