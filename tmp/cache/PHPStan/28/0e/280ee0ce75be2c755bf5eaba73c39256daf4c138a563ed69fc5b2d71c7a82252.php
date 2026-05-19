<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/src/Rules/DeadCode/PossiblyPureStaticCallCollector.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\Rules\DeadCode\PossiblyPureStaticCallCollector
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-0701536a8a056f215aa2e0f43d4f97f4465262704998680032cb536a61c7b645',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\Rules\\DeadCode\\PossiblyPureStaticCallCollector',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/src/Rules/DeadCode/PossiblyPureStaticCallCollector.php',
      ),
    ),
    'namespace' => 'PHPStan\\Rules\\DeadCode',
    'name' => 'PHPStan\\Rules\\DeadCode\\PossiblyPureStaticCallCollector',
    'shortName' => 'PossiblyPureStaticCallCollector',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * @implements Collector<Node\\Stmt\\Expression, array{class-string, string, int}>
 */',
    'attributes' => 
    array (
      0 => 
      array (
        'name' => 'PHPStan\\DependencyInjection\\RegisteredCollector',
        'isRepeated' => false,
        'arguments' => 
        array (
          'level' => 
          array (
            'code' => '4',
            'attributes' => 
            array (
              'startLine' => 14,
              'endLine' => 14,
              'startTokenPos' => 49,
              'startFilePos' => 359,
              'endTokenPos' => 49,
              'endFilePos' => 359,
            ),
          ),
        ),
      ),
    ),
    'startLine' => 14,
    'endLine' => 69,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
      0 => 'PHPStan\\Collectors\\Collector',
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
        'startLine' => 18,
        'endLine' => 20,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Rules\\DeadCode',
        'declaringClassName' => 'PHPStan\\Rules\\DeadCode\\PossiblyPureStaticCallCollector',
        'implementingClassName' => 'PHPStan\\Rules\\DeadCode\\PossiblyPureStaticCallCollector',
        'currentClassName' => 'PHPStan\\Rules\\DeadCode\\PossiblyPureStaticCallCollector',
        'aliasName' => NULL,
      ),
      'getNodeType' => 
      array (
        'name' => 'getNodeType',
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
        'startLine' => 22,
        'endLine' => 25,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Rules\\DeadCode',
        'declaringClassName' => 'PHPStan\\Rules\\DeadCode\\PossiblyPureStaticCallCollector',
        'implementingClassName' => 'PHPStan\\Rules\\DeadCode\\PossiblyPureStaticCallCollector',
        'currentClassName' => 'PHPStan\\Rules\\DeadCode\\PossiblyPureStaticCallCollector',
        'aliasName' => NULL,
      ),
      'processNode' => 
      array (
        'name' => 'processNode',
        'parameters' => 
        array (
          'node' => 
          array (
            'name' => 'node',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PhpParser\\Node',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 27,
            'endLine' => 27,
            'startColumn' => 30,
            'endColumn' => 39,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'scope' => 
          array (
            'name' => 'scope',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\Analyser\\Scope',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 27,
            'endLine' => 27,
            'startColumn' => 42,
            'endColumn' => 53,
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
        'startLine' => 27,
        'endLine' => 67,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Rules\\DeadCode',
        'declaringClassName' => 'PHPStan\\Rules\\DeadCode\\PossiblyPureStaticCallCollector',
        'implementingClassName' => 'PHPStan\\Rules\\DeadCode\\PossiblyPureStaticCallCollector',
        'currentClassName' => 'PHPStan\\Rules\\DeadCode\\PossiblyPureStaticCallCollector',
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