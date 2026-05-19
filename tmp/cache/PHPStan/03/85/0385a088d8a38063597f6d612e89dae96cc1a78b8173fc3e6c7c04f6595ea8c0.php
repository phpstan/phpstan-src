<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/src/Collectors/Registry.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\Collectors\Registry
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-2ea2b06c8ad97729ea0bf88b7080ded4d146c5207d61d26b0aa945eca7f5fa41',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\Collectors\\Registry',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/src/Collectors/Registry.php',
      ),
    ),
    'namespace' => 'PHPStan\\Collectors',
    'name' => 'PHPStan\\Collectors\\Registry',
    'shortName' => 'Registry',
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
          'factory' => 
          array (
            'code' => '\'@PHPStan\\Collectors\\RegistryFactory::create\'',
            'attributes' => 
            array (
              'startLine' => 10,
              'endLine' => 10,
              'startTokenPos' => 46,
              'startFilePos' => 223,
              'endTokenPos' => 46,
              'endFilePos' => 267,
            ),
          ),
        ),
      ),
    ),
    'startLine' => 10,
    'endLine' => 58,
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
      'collectors' => 
      array (
        'declaringClassName' => 'PHPStan\\Collectors\\Registry',
        'implementingClassName' => 'PHPStan\\Collectors\\Registry',
        'name' => 'collectors',
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
            'startLine' => 15,
            'endLine' => 15,
            'startTokenPos' => 68,
            'startFilePos' => 351,
            'endTokenPos' => 69,
            'endFilePos' => 352,
          ),
        ),
        'docComment' => '/** @var Collector[][] */',
        'attributes' => 
        array (
        ),
        'startLine' => 15,
        'endLine' => 15,
        'startColumn' => 2,
        'endColumn' => 32,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'cache' => 
      array (
        'declaringClassName' => 'PHPStan\\Collectors\\Registry',
        'implementingClassName' => 'PHPStan\\Collectors\\Registry',
        'name' => 'cache',
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
            'startLine' => 18,
            'endLine' => 18,
            'startTokenPos' => 82,
            'startFilePos' => 407,
            'endTokenPos' => 83,
            'endFilePos' => 408,
          ),
        ),
        'docComment' => '/** @var Collector[][] */',
        'attributes' => 
        array (
        ),
        'startLine' => 18,
        'endLine' => 18,
        'startColumn' => 2,
        'endColumn' => 27,
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
          'collectors' => 
          array (
            'name' => 'collectors',
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
            'startLine' => 23,
            'endLine' => 23,
            'startColumn' => 30,
            'endColumn' => 46,
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
 * @param Collector[] $collectors
 */',
        'startLine' => 23,
        'endLine' => 28,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Collectors',
        'declaringClassName' => 'PHPStan\\Collectors\\Registry',
        'implementingClassName' => 'PHPStan\\Collectors\\Registry',
        'currentClassName' => 'PHPStan\\Collectors\\Registry',
        'aliasName' => NULL,
      ),
      'getCollectors' => 
      array (
        'name' => 'getCollectors',
        'parameters' => 
        array (
          'nodeType' => 
          array (
            'name' => 'nodeType',
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
            'startLine' => 35,
            'endLine' => 35,
            'startColumn' => 32,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @template TNodeType of Node
 * @param class-string<TNodeType> $nodeType
 * @return array<Collector<TNodeType, mixed>>
 */',
        'startLine' => 35,
        'endLine' => 56,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Collectors',
        'declaringClassName' => 'PHPStan\\Collectors\\Registry',
        'implementingClassName' => 'PHPStan\\Collectors\\Registry',
        'currentClassName' => 'PHPStan\\Collectors\\Registry',
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