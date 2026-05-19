<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/tests/PHPStan/Rules/Comparison/MatchEnumPartialArmRegressionTest.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\Rules\Comparison\MatchEnumPartialArmRegressionTest
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-8d376b512b99d5b4727a70a866ae596c33850fc5aed6b9aade30c8caeb7cb150',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\Rules\\Comparison\\MatchEnumPartialArmRegressionTest',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/tests/PHPStan/Rules/Comparison/MatchEnumPartialArmRegressionTest.php',
      ),
    ),
    'namespace' => 'PHPStan\\Rules\\Comparison',
    'name' => 'PHPStan\\Rules\\Comparison\\MatchEnumPartialArmRegressionTest',
    'shortName' => 'MatchEnumPartialArmRegressionTest',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Regression test: when a match arm has multiple enum case conditions and the
 * enum fast-path analysis cannot handle all of them (e.g. because the condition
 * type is narrowed to a single case), the analysis must not partially consume
 * enum cases from the unused pool. Partial consumption caused the remaining
 * type to become NeverType, corrupting the scope for subsequent match expressions.
 *
 * @extends RuleTestCase<MatchCallbackScopeRegressionRule>
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 18,
    'endLine' => 41,
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
        'startLine' => 21,
        'endLine' => 24,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'PHPStan\\Rules\\Comparison',
        'declaringClassName' => 'PHPStan\\Rules\\Comparison\\MatchEnumPartialArmRegressionTest',
        'implementingClassName' => 'PHPStan\\Rules\\Comparison\\MatchEnumPartialArmRegressionTest',
        'currentClassName' => 'PHPStan\\Rules\\Comparison\\MatchEnumPartialArmRegressionTest',
        'aliasName' => NULL,
      ),
      'testEnumPartialArmConsumption' => 
      array (
        'name' => 'testEnumPartialArmConsumption',
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
                  'startLine' => 26,
                  'endLine' => 26,
                  'startTokenPos' => 70,
                  'startFilePos' => 817,
                  'endTokenPos' => 70,
                  'endFilePos' => 826,
                ),
              ),
            ),
          ),
        ),
        'docComment' => NULL,
        'startLine' => 26,
        'endLine' => 39,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Rules\\Comparison',
        'declaringClassName' => 'PHPStan\\Rules\\Comparison\\MatchEnumPartialArmRegressionTest',
        'implementingClassName' => 'PHPStan\\Rules\\Comparison\\MatchEnumPartialArmRegressionTest',
        'currentClassName' => 'PHPStan\\Rules\\Comparison\\MatchEnumPartialArmRegressionTest',
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