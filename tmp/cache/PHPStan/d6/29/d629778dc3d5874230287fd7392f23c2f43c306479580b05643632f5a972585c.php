<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/tests/PHPStan/Rules/Comparison/MatchCallbackScopeRegressionTest.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\Rules\Comparison\MatchCallbackScopeRegressionTest
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-a7dd3458df4ef254187fc9c5416e55bb8c499112b8fee526dfec2e7627332b82',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\Rules\\Comparison\\MatchCallbackScopeRegressionTest',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/tests/PHPStan/Rules/Comparison/MatchCallbackScopeRegressionTest.php',
      ),
    ),
    'namespace' => 'PHPStan\\Rules\\Comparison',
    'name' => 'PHPStan\\Rules\\Comparison\\MatchCallbackScopeRegressionTest',
    'shortName' => 'MatchCallbackScopeRegressionTest',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Regression test: the scope passed to the MatchExpressionNode callback
 * must reflect the original match condition type, not the merged arm body
 * scope which contains narrowed types from individual arms.
 *
 * @extends RuleTestCase<MatchCallbackScopeRegressionRule>
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 16,
    'endLine' => 35,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'PHPStan\\Testing\\RuleTestCase',
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
    ),
    'immediateMethods' => 
    array (
      'getRule' => 
      array (
        'name' => 'getRule',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPStan\\Rules\\Rule',
            'isIdentifier' => false,
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
        'modifiers' => 2,
        'namespace' => 'PHPStan\\Rules\\Comparison',
        'declaringClassName' => 'PHPStan\\Rules\\Comparison\\MatchCallbackScopeRegressionTest',
        'implementingClassName' => 'PHPStan\\Rules\\Comparison\\MatchCallbackScopeRegressionTest',
        'currentClassName' => 'PHPStan\\Rules\\Comparison\\MatchCallbackScopeRegressionTest',
        'aliasName' => NULL,
      ),
      'testExhaustiveMatchCallbackScope' => 
      array (
        'name' => 'testExhaustiveMatchCallbackScope',
        'parameters' => 
        array (
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
          0 => 
          array (
            'name' => 'PHPUnit\\Framework\\Attributes\\RequiresPhp',
            'isRepeated' => false,
            'arguments' => 
            array (
              0 => 
              array (
                'code' => '\'>= 8.1.0\'',
                'attributes' => 
                array (
                  'startLine' => 24,
                  'endLine' => 24,
                  'startTokenPos' => 70,
                  'startFilePos' => 625,
                  'endTokenPos' => 70,
                  'endFilePos' => 634,
                ),
              ),
            ),
          ),
        ),
        'docComment' => NULL,
        'startLine' => 24,
        'endLine' => 33,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Rules\\Comparison',
        'declaringClassName' => 'PHPStan\\Rules\\Comparison\\MatchCallbackScopeRegressionTest',
        'implementingClassName' => 'PHPStan\\Rules\\Comparison\\MatchCallbackScopeRegressionTest',
        'currentClassName' => 'PHPStan\\Rules\\Comparison\\MatchCallbackScopeRegressionTest',
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