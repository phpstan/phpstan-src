<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/vendor/nette/di/src/DI/Definitions/ServiceDefinition.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Nette\DI\Definitions\ServiceDefinition
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-a15f246c3d66c0b638c654ccbf12fccf6edf6da114d01331aa47110756eb532b',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Nette\\DI\\Definitions\\ServiceDefinition',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/vendor/nette/di/src/DI/Definitions/ServiceDefinition.php',
      ),
    ),
    'namespace' => 'Nette\\DI\\Definitions',
    'name' => 'Nette\\DI\\Definitions\\ServiceDefinition',
    'shortName' => 'ServiceDefinition',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Definition of standard service.
 *
 * @property string|null $class
 * @property Statement $factory
 * @property Statement[] $setup
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 23,
    'endLine' => 218,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'Nette\\DI\\Definitions\\Definition',
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
      'creator' => 
      array (
        'declaringClassName' => 'Nette\\DI\\Definitions\\ServiceDefinition',
        'implementingClassName' => 'Nette\\DI\\Definitions\\ServiceDefinition',
        'name' => 'creator',
        'modifiers' => 4,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/** @var Statement */',
        'attributes' => 
        array (
        ),
        'startLine' => 26,
        'endLine' => 26,
        'startColumn' => 2,
        'endColumn' => 18,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'setup' => 
      array (
        'declaringClassName' => 'Nette\\DI\\Definitions\\ServiceDefinition',
        'implementingClassName' => 'Nette\\DI\\Definitions\\ServiceDefinition',
        'name' => 'setup',
        'modifiers' => 4,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[]',
          'attributes' => 
          array (
            'startLine' => 29,
            'endLine' => 29,
            'startTokenPos' => 56,
            'startFilePos' => 529,
            'endTokenPos' => 57,
            'endFilePos' => 530,
          ),
        ),
        'docComment' => '/** @var Statement[] */',
        'attributes' => 
        array (
        ),
        'startLine' => 29,
        'endLine' => 29,
        'startColumn' => 2,
        'endColumn' => 21,
        'isPromoted' => false,
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
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 32,
        'endLine' => 35,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Nette\\DI\\Definitions',
        'declaringClassName' => 'Nette\\DI\\Definitions\\ServiceDefinition',
        'implementingClassName' => 'Nette\\DI\\Definitions\\ServiceDefinition',
        'currentClassName' => 'Nette\\DI\\Definitions\\ServiceDefinition',
        'aliasName' => NULL,
      ),
      'setType' => 
      array (
        'name' => 'setType',
        'parameters' => 
        array (
          'type' => 
          array (
            'name' => 'type',
            'default' => NULL,
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
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 39,
            'endLine' => 39,
            'startColumn' => 26,
            'endColumn' => 38,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/** @return static */',
        'startLine' => 39,
        'endLine' => 42,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Nette\\DI\\Definitions',
        'declaringClassName' => 'Nette\\DI\\Definitions\\ServiceDefinition',
        'implementingClassName' => 'Nette\\DI\\Definitions\\ServiceDefinition',
        'currentClassName' => 'Nette\\DI\\Definitions\\ServiceDefinition',
        'aliasName' => NULL,
      ),
      'setFactory' => 
      array (
        'name' => 'setFactory',
        'parameters' => 
        array (
          'factory' => 
          array (
            'name' => 'factory',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 50,
            'endLine' => 50,
            'startColumn' => 29,
            'endColumn' => 36,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'args' => 
          array (
            'name' => 'args',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 50,
                'endLine' => 50,
                'startTokenPos' => 131,
                'startFilePos' => 892,
                'endTokenPos' => 132,
                'endFilePos' => 893,
              ),
            ),
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
            'startLine' => 50,
            'endLine' => 50,
            'startColumn' => 39,
            'endColumn' => 54,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Alias for setCreator()
 * @param  string|array|Definition|Reference|Statement  $factory
 * @return static
 */',
        'startLine' => 50,
        'endLine' => 53,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Nette\\DI\\Definitions',
        'declaringClassName' => 'Nette\\DI\\Definitions\\ServiceDefinition',
        'implementingClassName' => 'Nette\\DI\\Definitions\\ServiceDefinition',
        'currentClassName' => 'Nette\\DI\\Definitions\\ServiceDefinition',
        'aliasName' => NULL,
      ),
      'getFactory' => 
      array (
        'name' => 'getFactory',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Nette\\DI\\Definitions\\Statement',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Alias for getCreator()
 */',
        'startLine' => 59,
        'endLine' => 62,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Nette\\DI\\Definitions',
        'declaringClassName' => 'Nette\\DI\\Definitions\\ServiceDefinition',
        'implementingClassName' => 'Nette\\DI\\Definitions\\ServiceDefinition',
        'currentClassName' => 'Nette\\DI\\Definitions\\ServiceDefinition',
        'aliasName' => NULL,
      ),
      'setCreator' => 
      array (
        'name' => 'setCreator',
        'parameters' => 
        array (
          'creator' => 
          array (
            'name' => 'creator',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 69,
            'endLine' => 69,
            'startColumn' => 29,
            'endColumn' => 36,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'args' => 
          array (
            'name' => 'args',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 69,
                'endLine' => 69,
                'startTokenPos' => 195,
                'startFilePos' => 1212,
                'endTokenPos' => 196,
                'endFilePos' => 1213,
              ),
            ),
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
            'startLine' => 69,
            'endLine' => 69,
            'startColumn' => 39,
            'endColumn' => 54,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @param  string|array|Definition|Reference|Statement  $creator
 * @return static
 */',
        'startLine' => 69,
        'endLine' => 75,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Nette\\DI\\Definitions',
        'declaringClassName' => 'Nette\\DI\\Definitions\\ServiceDefinition',
        'implementingClassName' => 'Nette\\DI\\Definitions\\ServiceDefinition',
        'currentClassName' => 'Nette\\DI\\Definitions\\ServiceDefinition',
        'aliasName' => NULL,
      ),
      'getCreator' => 
      array (
        'name' => 'getCreator',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Nette\\DI\\Definitions\\Statement',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 78,
        'endLine' => 81,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Nette\\DI\\Definitions',
        'declaringClassName' => 'Nette\\DI\\Definitions\\ServiceDefinition',
        'implementingClassName' => 'Nette\\DI\\Definitions\\ServiceDefinition',
        'currentClassName' => 'Nette\\DI\\Definitions\\ServiceDefinition',
        'aliasName' => NULL,
      ),
      'getEntity' => 
      array (
        'name' => 'getEntity',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/** @return string|array|Definition|Reference|null */',
        'startLine' => 85,
        'endLine' => 88,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Nette\\DI\\Definitions',
        'declaringClassName' => 'Nette\\DI\\Definitions\\ServiceDefinition',
        'implementingClassName' => 'Nette\\DI\\Definitions\\ServiceDefinition',
        'currentClassName' => 'Nette\\DI\\Definitions\\ServiceDefinition',
        'aliasName' => NULL,
      ),
      'setArguments' => 
      array (
        'name' => 'setArguments',
        'parameters' => 
        array (
          'args' => 
          array (
            'name' => 'args',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 92,
                'endLine' => 92,
                'startTokenPos' => 298,
                'startFilePos' => 1611,
                'endTokenPos' => 299,
                'endFilePos' => 1612,
              ),
            ),
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
            'startLine' => 92,
            'endLine' => 92,
            'startColumn' => 31,
            'endColumn' => 46,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/** @return static */',
        'startLine' => 92,
        'endLine' => 96,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Nette\\DI\\Definitions',
        'declaringClassName' => 'Nette\\DI\\Definitions\\ServiceDefinition',
        'implementingClassName' => 'Nette\\DI\\Definitions\\ServiceDefinition',
        'currentClassName' => 'Nette\\DI\\Definitions\\ServiceDefinition',
        'aliasName' => NULL,
      ),
      'setArgument' => 
      array (
        'name' => 'setArgument',
        'parameters' => 
        array (
          'key' => 
          array (
            'name' => 'key',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 100,
            'endLine' => 100,
            'startColumn' => 30,
            'endColumn' => 33,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'value' => 
          array (
            'name' => 'value',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 100,
            'endLine' => 100,
            'startColumn' => 36,
            'endColumn' => 41,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/** @return static */',
        'startLine' => 100,
        'endLine' => 104,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Nette\\DI\\Definitions',
        'declaringClassName' => 'Nette\\DI\\Definitions\\ServiceDefinition',
        'implementingClassName' => 'Nette\\DI\\Definitions\\ServiceDefinition',
        'currentClassName' => 'Nette\\DI\\Definitions\\ServiceDefinition',
        'aliasName' => NULL,
      ),
      'setSetup' => 
      array (
        'name' => 'setSetup',
        'parameters' => 
        array (
          'setup' => 
          array (
            'name' => 'setup',
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
            'startLine' => 111,
            'endLine' => 111,
            'startColumn' => 27,
            'endColumn' => 38,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @param  Statement[]  $setup
 * @return static
 */',
        'startLine' => 111,
        'endLine' => 121,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Nette\\DI\\Definitions',
        'declaringClassName' => 'Nette\\DI\\Definitions\\ServiceDefinition',
        'implementingClassName' => 'Nette\\DI\\Definitions\\ServiceDefinition',
        'currentClassName' => 'Nette\\DI\\Definitions\\ServiceDefinition',
        'aliasName' => NULL,
      ),
      'getSetup' => 
      array (
        'name' => 'getSetup',
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
        'docComment' => '/** @return Statement[] */',
        'startLine' => 125,
        'endLine' => 128,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Nette\\DI\\Definitions',
        'declaringClassName' => 'Nette\\DI\\Definitions\\ServiceDefinition',
        'implementingClassName' => 'Nette\\DI\\Definitions\\ServiceDefinition',
        'currentClassName' => 'Nette\\DI\\Definitions\\ServiceDefinition',
        'aliasName' => NULL,
      ),
      'addSetup' => 
      array (
        'name' => 'addSetup',
        'parameters' => 
        array (
          'entity' => 
          array (
            'name' => 'entity',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 135,
            'endLine' => 135,
            'startColumn' => 27,
            'endColumn' => 33,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'args' => 
          array (
            'name' => 'args',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 135,
                'endLine' => 135,
                'startTokenPos' => 470,
                'startFilePos' => 2369,
                'endTokenPos' => 471,
                'endFilePos' => 2370,
              ),
            ),
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
            'startLine' => 135,
            'endLine' => 135,
            'startColumn' => 36,
            'endColumn' => 51,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @param  string|array|Definition|Reference|Statement  $entity
 * @return static
 */',
        'startLine' => 135,
        'endLine' => 141,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Nette\\DI\\Definitions',
        'declaringClassName' => 'Nette\\DI\\Definitions\\ServiceDefinition',
        'implementingClassName' => 'Nette\\DI\\Definitions\\ServiceDefinition',
        'currentClassName' => 'Nette\\DI\\Definitions\\ServiceDefinition',
        'aliasName' => NULL,
      ),
      'resolveType' => 
      array (
        'name' => 'resolveType',
        'parameters' => 
        array (
          'resolver' => 
          array (
            'name' => 'resolver',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Nette\\DI\\Resolver',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 144,
            'endLine' => 144,
            'startColumn' => 30,
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
        'docComment' => NULL,
        'startLine' => 144,
        'endLine' => 167,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Nette\\DI\\Definitions',
        'declaringClassName' => 'Nette\\DI\\Definitions\\ServiceDefinition',
        'implementingClassName' => 'Nette\\DI\\Definitions\\ServiceDefinition',
        'currentClassName' => 'Nette\\DI\\Definitions\\ServiceDefinition',
        'aliasName' => NULL,
      ),
      'complete' => 
      array (
        'name' => 'complete',
        'parameters' => 
        array (
          'resolver' => 
          array (
            'name' => 'resolver',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Nette\\DI\\Resolver',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 170,
            'endLine' => 170,
            'startColumn' => 27,
            'endColumn' => 53,
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
        'startLine' => 170,
        'endLine' => 190,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Nette\\DI\\Definitions',
        'declaringClassName' => 'Nette\\DI\\Definitions\\ServiceDefinition',
        'implementingClassName' => 'Nette\\DI\\Definitions\\ServiceDefinition',
        'currentClassName' => 'Nette\\DI\\Definitions\\ServiceDefinition',
        'aliasName' => NULL,
      ),
      'generateMethod' => 
      array (
        'name' => 'generateMethod',
        'parameters' => 
        array (
          'method' => 
          array (
            'name' => 'method',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Nette\\PhpGenerator\\Method',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 193,
            'endLine' => 193,
            'startColumn' => 33,
            'endColumn' => 65,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'generator' => 
          array (
            'name' => 'generator',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Nette\\DI\\PhpGenerator',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 193,
            'endLine' => 193,
            'startColumn' => 68,
            'endColumn' => 99,
            'parameterIndex' => 1,
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
        'startLine' => 193,
        'endLine' => 209,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Nette\\DI\\Definitions',
        'declaringClassName' => 'Nette\\DI\\Definitions\\ServiceDefinition',
        'implementingClassName' => 'Nette\\DI\\Definitions\\ServiceDefinition',
        'currentClassName' => 'Nette\\DI\\Definitions\\ServiceDefinition',
        'aliasName' => NULL,
      ),
      '__clone' => 
      array (
        'name' => '__clone',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 212,
        'endLine' => 217,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Nette\\DI\\Definitions',
        'declaringClassName' => 'Nette\\DI\\Definitions\\ServiceDefinition',
        'implementingClassName' => 'Nette\\DI\\Definitions\\ServiceDefinition',
        'currentClassName' => 'Nette\\DI\\Definitions\\ServiceDefinition',
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