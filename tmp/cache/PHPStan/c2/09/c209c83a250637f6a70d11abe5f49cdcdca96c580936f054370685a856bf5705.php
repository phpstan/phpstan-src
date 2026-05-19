<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/src/DependencyInjection/Type/LazyUnaryOperatorTypeSpecifyingExtensionRegistryProvider.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\DependencyInjection\Type\LazyUnaryOperatorTypeSpecifyingExtensionRegistryProvider
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-314a38f801903cd4ee701dc9b7211a5a2fb918dc4dd4151672327d85cecdac97',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\DependencyInjection\\Type\\LazyUnaryOperatorTypeSpecifyingExtensionRegistryProvider',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/src/DependencyInjection/Type/LazyUnaryOperatorTypeSpecifyingExtensionRegistryProvider.php',
      ),
    ),
    'namespace' => 'PHPStan\\DependencyInjection\\Type',
    'name' => 'PHPStan\\DependencyInjection\\Type\\LazyUnaryOperatorTypeSpecifyingExtensionRegistryProvider',
    'shortName' => 'LazyUnaryOperatorTypeSpecifyingExtensionRegistryProvider',
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
            'code' => '\\PHPStan\\DependencyInjection\\Type\\UnaryOperatorTypeSpecifyingExtensionRegistryProvider::class',
            'attributes' => 
            array (
              'startLine' => 10,
              'endLine' => 10,
              'startTokenPos' => 42,
              'startFilePos' => 293,
              'endTokenPos' => 44,
              'endFilePos' => 351,
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
      0 => 'PHPStan\\DependencyInjection\\Type\\UnaryOperatorTypeSpecifyingExtensionRegistryProvider',
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
        'declaringClassName' => 'PHPStan\\DependencyInjection\\Type\\LazyUnaryOperatorTypeSpecifyingExtensionRegistryProvider',
        'implementingClassName' => 'PHPStan\\DependencyInjection\\Type\\LazyUnaryOperatorTypeSpecifyingExtensionRegistryProvider',
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
                  'name' => 'PHPStan\\Type\\UnaryOperatorTypeSpecifyingExtensionRegistry',
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
            'startFilePos' => 558,
            'endTokenPos' => 69,
            'endFilePos' => 561,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 14,
        'endLine' => 14,
        'startColumn' => 2,
        'endColumn' => 72,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'container' => 
      array (
        'declaringClassName' => 'PHPStan\\DependencyInjection\\Type\\LazyUnaryOperatorTypeSpecifyingExtensionRegistryProvider',
        'implementingClassName' => 'PHPStan\\DependencyInjection\\Type\\LazyUnaryOperatorTypeSpecifyingExtensionRegistryProvider',
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
        'declaringClassName' => 'PHPStan\\DependencyInjection\\Type\\LazyUnaryOperatorTypeSpecifyingExtensionRegistryProvider',
        'implementingClassName' => 'PHPStan\\DependencyInjection\\Type\\LazyUnaryOperatorTypeSpecifyingExtensionRegistryProvider',
        'currentClassName' => 'PHPStan\\DependencyInjection\\Type\\LazyUnaryOperatorTypeSpecifyingExtensionRegistryProvider',
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
            'name' => 'PHPStan\\Type\\UnaryOperatorTypeSpecifyingExtensionRegistry',
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
        'declaringClassName' => 'PHPStan\\DependencyInjection\\Type\\LazyUnaryOperatorTypeSpecifyingExtensionRegistryProvider',
        'implementingClassName' => 'PHPStan\\DependencyInjection\\Type\\LazyUnaryOperatorTypeSpecifyingExtensionRegistryProvider',
        'currentClassName' => 'PHPStan\\DependencyInjection\\Type\\LazyUnaryOperatorTypeSpecifyingExtensionRegistryProvider',
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