<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/src/DependencyInjection/Nette/NetteContainer.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\DependencyInjection\Nette\NetteContainer
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-dd812c414e3e1d875e7456b7a17e87e20165a75df657edfea55564fdaef9d75c',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\DependencyInjection\\Nette\\NetteContainer',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/src/DependencyInjection/Nette/NetteContainer.php',
      ),
    ),
    'namespace' => 'PHPStan\\DependencyInjection\\Nette',
    'name' => 'PHPStan\\DependencyInjection\\Nette\\NetteContainer',
    'shortName' => 'NetteContainer',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * @internal
 */',
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
            'code' => '\\PHPStan\\DependencyInjection\\Nette\\NetteContainer::class',
            'attributes' => 
            array (
              'startLine' => 16,
              'endLine' => 16,
              'startTokenPos' => 65,
              'startFilePos' => 415,
              'endTokenPos' => 67,
              'endFilePos' => 435,
            ),
          ),
        ),
      ),
    ),
    'startLine' => 16,
    'endLine' => 115,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
      0 => 'PHPStan\\DependencyInjection\\Container',
    ),
    'traitClassNames' => 
    array (
    ),
    'immediateConstants' => 
    array (
    ),
    'immediateProperties' => 
    array (
      'parameters' => 
      array (
        'declaringClassName' => 'PHPStan\\DependencyInjection\\Nette\\NetteContainer',
        'implementingClassName' => 'PHPStan\\DependencyInjection\\Nette\\NetteContainer',
        'name' => 'parameters',
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
                  'name' => 'array',
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
        'default' => 
        array (
          'code' => 'null',
          'attributes' => 
          array (
            'startLine' => 21,
            'endLine' => 21,
            'startTokenPos' => 94,
            'startFilePos' => 541,
            'endTokenPos' => 94,
            'endFilePos' => 544,
          ),
        ),
        'docComment' => '/** @var mixed[] */',
        'attributes' => 
        array (
        ),
        'startLine' => 21,
        'endLine' => 21,
        'startColumn' => 2,
        'endColumn' => 35,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'container' => 
      array (
        'declaringClassName' => 'PHPStan\\DependencyInjection\\Nette\\NetteContainer',
        'implementingClassName' => 'PHPStan\\DependencyInjection\\Nette\\NetteContainer',
        'name' => 'container',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Nette\\DI\\Container',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 24,
        'endLine' => 24,
        'startColumn' => 3,
        'endColumn' => 49,
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
                'name' => 'Nette\\DI\\Container',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 24,
            'endLine' => 24,
            'startColumn' => 3,
            'endColumn' => 49,
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
        'startLine' => 23,
        'endLine' => 27,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\DependencyInjection\\Nette',
        'declaringClassName' => 'PHPStan\\DependencyInjection\\Nette\\NetteContainer',
        'implementingClassName' => 'PHPStan\\DependencyInjection\\Nette\\NetteContainer',
        'currentClassName' => 'PHPStan\\DependencyInjection\\Nette\\NetteContainer',
        'aliasName' => NULL,
      ),
      'hasService' => 
      array (
        'name' => 'hasService',
        'parameters' => 
        array (
          'serviceName' => 
          array (
            'name' => 'serviceName',
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
            'startLine' => 29,
            'endLine' => 29,
            'startColumn' => 29,
            'endColumn' => 47,
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
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 29,
        'endLine' => 32,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\DependencyInjection\\Nette',
        'declaringClassName' => 'PHPStan\\DependencyInjection\\Nette\\NetteContainer',
        'implementingClassName' => 'PHPStan\\DependencyInjection\\Nette\\NetteContainer',
        'currentClassName' => 'PHPStan\\DependencyInjection\\Nette\\NetteContainer',
        'aliasName' => NULL,
      ),
      'getService' => 
      array (
        'name' => 'getService',
        'parameters' => 
        array (
          'serviceName' => 
          array (
            'name' => 'serviceName',
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
            'startLine' => 37,
            'endLine' => 37,
            'startColumn' => 29,
            'endColumn' => 47,
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
 * @return mixed
 */',
        'startLine' => 37,
        'endLine' => 44,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\DependencyInjection\\Nette',
        'declaringClassName' => 'PHPStan\\DependencyInjection\\Nette\\NetteContainer',
        'implementingClassName' => 'PHPStan\\DependencyInjection\\Nette\\NetteContainer',
        'currentClassName' => 'PHPStan\\DependencyInjection\\Nette\\NetteContainer',
        'aliasName' => NULL,
      ),
      'getByType' => 
      array (
        'name' => 'getByType',
        'parameters' => 
        array (
          'className' => 
          array (
            'name' => 'className',
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
            'startLine' => 51,
            'endLine' => 51,
            'startColumn' => 28,
            'endColumn' => 44,
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
 * @template T of object
 * @param class-string<T> $className
 * @return T
 */',
        'startLine' => 51,
        'endLine' => 58,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\DependencyInjection\\Nette',
        'declaringClassName' => 'PHPStan\\DependencyInjection\\Nette\\NetteContainer',
        'implementingClassName' => 'PHPStan\\DependencyInjection\\Nette\\NetteContainer',
        'currentClassName' => 'PHPStan\\DependencyInjection\\Nette\\NetteContainer',
        'aliasName' => NULL,
      ),
      'findServiceNamesByType' => 
      array (
        'name' => 'findServiceNamesByType',
        'parameters' => 
        array (
          'className' => 
          array (
            'name' => 'className',
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
            'startLine' => 64,
            'endLine' => 64,
            'startColumn' => 41,
            'endColumn' => 57,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @param class-string $className
 * @return string[]
 */',
        'startLine' => 64,
        'endLine' => 67,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\DependencyInjection\\Nette',
        'declaringClassName' => 'PHPStan\\DependencyInjection\\Nette\\NetteContainer',
        'implementingClassName' => 'PHPStan\\DependencyInjection\\Nette\\NetteContainer',
        'currentClassName' => 'PHPStan\\DependencyInjection\\Nette\\NetteContainer',
        'aliasName' => NULL,
      ),
      'getServicesByTag' => 
      array (
        'name' => 'getServicesByTag',
        'parameters' => 
        array (
          'tagName' => 
          array (
            'name' => 'tagName',
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
            'startLine' => 72,
            'endLine' => 72,
            'startColumn' => 35,
            'endColumn' => 49,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @return mixed[]
 */',
        'startLine' => 72,
        'endLine' => 75,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\DependencyInjection\\Nette',
        'declaringClassName' => 'PHPStan\\DependencyInjection\\Nette\\NetteContainer',
        'implementingClassName' => 'PHPStan\\DependencyInjection\\Nette\\NetteContainer',
        'currentClassName' => 'PHPStan\\DependencyInjection\\Nette\\NetteContainer',
        'aliasName' => NULL,
      ),
      'getParameters' => 
      array (
        'name' => 'getParameters',
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
 * @return mixed[]
 */',
        'startLine' => 80,
        'endLine' => 83,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\DependencyInjection\\Nette',
        'declaringClassName' => 'PHPStan\\DependencyInjection\\Nette\\NetteContainer',
        'implementingClassName' => 'PHPStan\\DependencyInjection\\Nette\\NetteContainer',
        'currentClassName' => 'PHPStan\\DependencyInjection\\Nette\\NetteContainer',
        'aliasName' => NULL,
      ),
      'hasParameter' => 
      array (
        'name' => 'hasParameter',
        'parameters' => 
        array (
          'parameterName' => 
          array (
            'name' => 'parameterName',
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
            'startLine' => 85,
            'endLine' => 85,
            'startColumn' => 31,
            'endColumn' => 51,
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
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 85,
        'endLine' => 90,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\DependencyInjection\\Nette',
        'declaringClassName' => 'PHPStan\\DependencyInjection\\Nette\\NetteContainer',
        'implementingClassName' => 'PHPStan\\DependencyInjection\\Nette\\NetteContainer',
        'currentClassName' => 'PHPStan\\DependencyInjection\\Nette\\NetteContainer',
        'aliasName' => NULL,
      ),
      'getParameter' => 
      array (
        'name' => 'getParameter',
        'parameters' => 
        array (
          'parameterName' => 
          array (
            'name' => 'parameterName',
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
            'startLine' => 95,
            'endLine' => 95,
            'startColumn' => 31,
            'endColumn' => 51,
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
 * @return mixed
 */',
        'startLine' => 95,
        'endLine' => 104,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\DependencyInjection\\Nette',
        'declaringClassName' => 'PHPStan\\DependencyInjection\\Nette\\NetteContainer',
        'implementingClassName' => 'PHPStan\\DependencyInjection\\Nette\\NetteContainer',
        'currentClassName' => 'PHPStan\\DependencyInjection\\Nette\\NetteContainer',
        'aliasName' => NULL,
      ),
      'tagsToServices' => 
      array (
        'name' => 'tagsToServices',
        'parameters' => 
        array (
          'tags' => 
          array (
            'name' => 'tags',
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
            'startLine' => 110,
            'endLine' => 110,
            'startColumn' => 34,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @param mixed[] $tags
 * @return mixed[]
 */',
        'startLine' => 110,
        'endLine' => 113,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'PHPStan\\DependencyInjection\\Nette',
        'declaringClassName' => 'PHPStan\\DependencyInjection\\Nette\\NetteContainer',
        'implementingClassName' => 'PHPStan\\DependencyInjection\\Nette\\NetteContainer',
        'currentClassName' => 'PHPStan\\DependencyInjection\\Nette\\NetteContainer',
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