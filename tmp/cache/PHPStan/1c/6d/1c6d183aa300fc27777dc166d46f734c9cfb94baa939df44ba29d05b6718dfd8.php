<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/src/Rules/DeadCode/CallToConstructorStatementWithoutImpurePointsRule.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\Rules\DeadCode\CallToConstructorStatementWithoutImpurePointsRule
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-c4cec482703ec0da9fce557ec85f6be269d6e2f59be30d995a47bf6dbe26e4ec',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\Rules\\DeadCode\\CallToConstructorStatementWithoutImpurePointsRule',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/src/Rules/DeadCode/CallToConstructorStatementWithoutImpurePointsRule.php',
      ),
    ),
    'namespace' => 'PHPStan\\Rules\\DeadCode',
    'name' => 'PHPStan\\Rules\\DeadCode\\CallToConstructorStatementWithoutImpurePointsRule',
    'shortName' => 'CallToConstructorStatementWithoutImpurePointsRule',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * @implements Rule<CollectedDataNode>
 */',
    'attributes' => 
    array (
      0 => 
      array (
        'name' => 'PHPStan\\DependencyInjection\\RegisteredRule',
        'isRepeated' => false,
        'arguments' => 
        array (
          'level' => 
          array (
            'code' => '4',
            'attributes' => 
            array (
              'startLine' => 18,
              'endLine' => 18,
              'startTokenPos' => 75,
              'startFilePos' => 411,
              'endTokenPos' => 75,
              'endFilePos' => 411,
            ),
          ),
        ),
      ),
    ),
    'startLine' => 18,
    'endLine' => 56,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
      0 => 'PHPStan\\Rules\\Rule',
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
        'declaringClassName' => 'PHPStan\\Rules\\DeadCode\\CallToConstructorStatementWithoutImpurePointsRule',
        'implementingClassName' => 'PHPStan\\Rules\\DeadCode\\CallToConstructorStatementWithoutImpurePointsRule',
        'currentClassName' => 'PHPStan\\Rules\\DeadCode\\CallToConstructorStatementWithoutImpurePointsRule',
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
        'docComment' => NULL,
        'startLine' => 27,
        'endLine' => 54,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Rules\\DeadCode',
        'declaringClassName' => 'PHPStan\\Rules\\DeadCode\\CallToConstructorStatementWithoutImpurePointsRule',
        'implementingClassName' => 'PHPStan\\Rules\\DeadCode\\CallToConstructorStatementWithoutImpurePointsRule',
        'currentClassName' => 'PHPStan\\Rules\\DeadCode\\CallToConstructorStatementWithoutImpurePointsRule',
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