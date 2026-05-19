<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/src/Rules/DeadCode/FunctionWithoutImpurePointsCollector.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\Rules\DeadCode\FunctionWithoutImpurePointsCollector
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-c5766ab9638d03285238d80bf807b3ea9f15e46ada12065620d31009a50cf454',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\Rules\\DeadCode\\FunctionWithoutImpurePointsCollector',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/src/Rules/DeadCode/FunctionWithoutImpurePointsCollector.php',
      ),
    ),
    'namespace' => 'PHPStan\\Rules\\DeadCode',
    'name' => 'PHPStan\\Rules\\DeadCode\\FunctionWithoutImpurePointsCollector',
    'shortName' => 'FunctionWithoutImpurePointsCollector',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * @implements Collector<FunctionReturnStatementsNode, string>
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
              'startLine' => 15,
              'endLine' => 15,
              'startTokenPos' => 56,
              'startFilePos' => 372,
              'endTokenPos' => 56,
              'endFilePos' => 372,
            ),
          ),
        ),
      ),
    ),
    'startLine' => 15,
    'endLine' => 57,
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
        'startLine' => 19,
        'endLine' => 22,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Rules\\DeadCode',
        'declaringClassName' => 'PHPStan\\Rules\\DeadCode\\FunctionWithoutImpurePointsCollector',
        'implementingClassName' => 'PHPStan\\Rules\\DeadCode\\FunctionWithoutImpurePointsCollector',
        'currentClassName' => 'PHPStan\\Rules\\DeadCode\\FunctionWithoutImpurePointsCollector',
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
            'startLine' => 24,
            'endLine' => 24,
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
            'startLine' => 24,
            'endLine' => 24,
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
        'startLine' => 24,
        'endLine' => 55,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Rules\\DeadCode',
        'declaringClassName' => 'PHPStan\\Rules\\DeadCode\\FunctionWithoutImpurePointsCollector',
        'implementingClassName' => 'PHPStan\\Rules\\DeadCode\\FunctionWithoutImpurePointsCollector',
        'currentClassName' => 'PHPStan\\Rules\\DeadCode\\FunctionWithoutImpurePointsCollector',
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