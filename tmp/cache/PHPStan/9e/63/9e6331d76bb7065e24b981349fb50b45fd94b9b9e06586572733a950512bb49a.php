<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/src/DependencyInjection/Configurator.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\DependencyInjection\Configurator
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-a20011e8cf50b96be64aeacafbc85566c03ddcee90da6b6f390d40189291067f',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\DependencyInjection\\Configurator',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/src/DependencyInjection/Configurator.php',
      ),
    ),
    'namespace' => 'PHPStan\\DependencyInjection',
    'name' => 'PHPStan\\DependencyInjection\\Configurator',
    'shortName' => 'Configurator',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => NULL,
    'attributes' => 
    array (
    ),
    'startLine' => 38,
    'endLine' => 253,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'Nette\\Bootstrap\\Configurator',
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
      'allConfigFiles' => 
      array (
        'declaringClassName' => 'PHPStan\\DependencyInjection\\Configurator',
        'implementingClassName' => 'PHPStan\\DependencyInjection\\Configurator',
        'name' => 'allConfigFiles',
        'modifiers' => 4,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'default' => 
        array (
          'code' => '[]',
          'attributes' => 
          array (
            'startLine' => 42,
            'endLine' => 42,
            'startTokenPos' => 246,
            'startFilePos' => 1067,
            'endTokenPos' => 247,
            'endFilePos' => 1068,
          ),
        ),
        'docComment' => '/** @var string[] */',
        'attributes' => 
        array (
        ),
        'startLine' => 42,
        'endLine' => 42,
        'startColumn' => 2,
        'endColumn' => 36,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'loaderFactory' => 
      array (
        'declaringClassName' => 'PHPStan\\DependencyInjection\\Configurator',
        'implementingClassName' => 'PHPStan\\DependencyInjection\\Configurator',
        'name' => 'loaderFactory',
        'modifiers' => 4,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPStan\\DependencyInjection\\LoaderFactory',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 44,
        'endLine' => 44,
        'startColumn' => 30,
        'endColumn' => 65,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'journalContainer' => 
      array (
        'declaringClassName' => 'PHPStan\\DependencyInjection\\Configurator',
        'implementingClassName' => 'PHPStan\\DependencyInjection\\Configurator',
        'name' => 'journalContainer',
        'modifiers' => 4,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 44,
        'endLine' => 44,
        'startColumn' => 68,
        'endColumn' => 97,
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
          'loaderFactory' => 
          array (
            'name' => 'loaderFactory',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\DependencyInjection\\LoaderFactory',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 44,
            'endLine' => 44,
            'startColumn' => 30,
            'endColumn' => 65,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'journalContainer' => 
          array (
            'name' => 'journalContainer',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'bool',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 44,
            'endLine' => 44,
            'startColumn' => 68,
            'endColumn' => 97,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 44,
        'endLine' => 47,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\DependencyInjection',
        'declaringClassName' => 'PHPStan\\DependencyInjection\\Configurator',
        'implementingClassName' => 'PHPStan\\DependencyInjection\\Configurator',
        'currentClassName' => 'PHPStan\\DependencyInjection\\Configurator',
        'aliasName' => NULL,
      ),
      'createLoader' => 
      array (
        'name' => 'createLoader',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Nette\\DI\\Config\\Loader',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
          0 => 
          array (
            'name' => 'Override',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => NULL,
        'startLine' => 49,
        'endLine' => 53,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'PHPStan\\DependencyInjection',
        'declaringClassName' => 'PHPStan\\DependencyInjection\\Configurator',
        'implementingClassName' => 'PHPStan\\DependencyInjection\\Configurator',
        'currentClassName' => 'PHPStan\\DependencyInjection\\Configurator',
        'aliasName' => NULL,
      ),
      'setAllConfigFiles' => 
      array (
        'name' => 'setAllConfigFiles',
        'parameters' => 
        array (
          'allConfigFiles' => 
          array (
            'name' => 'allConfigFiles',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'array',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 58,
            'endLine' => 58,
            'startColumn' => 36,
            'endColumn' => 56,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @param string[] $allConfigFiles
 */',
        'startLine' => 58,
        'endLine' => 61,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\DependencyInjection',
        'declaringClassName' => 'PHPStan\\DependencyInjection\\Configurator',
        'implementingClassName' => 'PHPStan\\DependencyInjection\\Configurator',
        'currentClassName' => 'PHPStan\\DependencyInjection\\Configurator',
        'aliasName' => NULL,
      ),
      'getDefaultParameters' => 
      array (
        'name' => 'getDefaultParameters',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
          0 => 
          array (
            'name' => 'Override',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => '/**
 * @return mixed[]
 */',
        'startLine' => 66,
        'endLine' => 70,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'PHPStan\\DependencyInjection',
        'declaringClassName' => 'PHPStan\\DependencyInjection\\Configurator',
        'implementingClassName' => 'PHPStan\\DependencyInjection\\Configurator',
        'currentClassName' => 'PHPStan\\DependencyInjection\\Configurator',
        'aliasName' => NULL,
      ),
      'getContainerCacheDirectory' => 
      array (
        'name' => 'getContainerCacheDirectory',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 72,
        'endLine' => 75,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\DependencyInjection',
        'declaringClassName' => 'PHPStan\\DependencyInjection\\Configurator',
        'implementingClassName' => 'PHPStan\\DependencyInjection\\Configurator',
        'currentClassName' => 'PHPStan\\DependencyInjection\\Configurator',
        'aliasName' => NULL,
      ),
      'loadContainer' => 
      array (
        'name' => 'loadContainer',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
          0 => 
          array (
            'name' => 'Override',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => NULL,
        'startLine' => 77,
        'endLine' => 121,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\DependencyInjection',
        'declaringClassName' => 'PHPStan\\DependencyInjection\\Configurator',
        'implementingClassName' => 'PHPStan\\DependencyInjection\\Configurator',
        'currentClassName' => 'PHPStan\\DependencyInjection\\Configurator',
        'aliasName' => NULL,
      ),
      'journal' => 
      array (
        'name' => 'journal',
        'parameters' => 
        array (
          'currentContainerClassName' => 
          array (
            'name' => 'currentContainerClassName',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'string',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 123,
            'endLine' => 123,
            'startColumn' => 27,
            'endColumn' => 59,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 123,
        'endLine' => 211,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'PHPStan\\DependencyInjection',
        'declaringClassName' => 'PHPStan\\DependencyInjection\\Configurator',
        'implementingClassName' => 'PHPStan\\DependencyInjection\\Configurator',
        'currentClassName' => 'PHPStan\\DependencyInjection\\Configurator',
        'aliasName' => NULL,
      ),
      'createContainer' => 
      array (
        'name' => 'createContainer',
        'parameters' => 
        array (
          'initialize' => 
          array (
            'name' => 'initialize',
            'default' => 
            array (
              'code' => 'true',
              'attributes' => 
              array (
                'startLine' => 214,
                'endLine' => 214,
                'startTokenPos' => 1326,
                'startFilePos' => 5442,
                'endTokenPos' => 1326,
                'endFilePos' => 5445,
              ),
            ),
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'bool',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 214,
            'endLine' => 214,
            'startColumn' => 34,
            'endColumn' => 56,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Nette\\DI\\Container',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
          0 => 
          array (
            'name' => 'Override',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => NULL,
        'startLine' => 213,
        'endLine' => 232,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\DependencyInjection',
        'declaringClassName' => 'PHPStan\\DependencyInjection\\Configurator',
        'implementingClassName' => 'PHPStan\\DependencyInjection\\Configurator',
        'currentClassName' => 'PHPStan\\DependencyInjection\\Configurator',
        'aliasName' => NULL,
      ),
      'getAllConfigFilesHashes' => 
      array (
        'name' => 'getAllConfigFilesHashes',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @return string[]
 */',
        'startLine' => 237,
        'endLine' => 251,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'PHPStan\\DependencyInjection',
        'declaringClassName' => 'PHPStan\\DependencyInjection\\Configurator',
        'implementingClassName' => 'PHPStan\\DependencyInjection\\Configurator',
        'currentClassName' => 'PHPStan\\DependencyInjection\\Configurator',
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