<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/tests/PHPStan/Rules/Types/InvalidTypesInUnionRuleTest.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\Rules\Types\InvalidTypesInUnionRuleTest
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-904a914f66d3d85354094cf505e833951efb012e98c1165d7a3dc73351cc3cc9',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\Rules\\Types\\InvalidTypesInUnionRuleTest',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/tests/PHPStan/Rules/Types/InvalidTypesInUnionRuleTest.php',
      ),
    ),
    'namespace' => 'PHPStan\\Rules\\Types',
    'name' => 'PHPStan\\Rules\\Types\\InvalidTypesInUnionRuleTest',
    'shortName' => 'InvalidTypesInUnionRuleTest',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * @extends RuleTestCase<InvalidTypesInUnionRule>
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 12,
    'endLine' => 88,
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
        'startLine' => 15,
        'endLine' => 18,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'PHPStan\\Rules\\Types',
        'declaringClassName' => 'PHPStan\\Rules\\Types\\InvalidTypesInUnionRuleTest',
        'implementingClassName' => 'PHPStan\\Rules\\Types\\InvalidTypesInUnionRuleTest',
        'currentClassName' => 'PHPStan\\Rules\\Types\\InvalidTypesInUnionRuleTest',
        'aliasName' => NULL,
      ),
      'testRuleOnUnionWithVoid' => 
      array (
        'name' => 'testRuleOnUnionWithVoid',
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
        ),
        'docComment' => NULL,
        'startLine' => 20,
        'endLine' => 32,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Rules\\Types',
        'declaringClassName' => 'PHPStan\\Rules\\Types\\InvalidTypesInUnionRuleTest',
        'implementingClassName' => 'PHPStan\\Rules\\Types\\InvalidTypesInUnionRuleTest',
        'currentClassName' => 'PHPStan\\Rules\\Types\\InvalidTypesInUnionRuleTest',
        'aliasName' => NULL,
      ),
      'testRuleOnUnionWithMixed' => 
      array (
        'name' => 'testRuleOnUnionWithMixed',
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
                'code' => '\'>=8.0.0\'',
                'attributes' => 
                array (
                  'startLine' => 34,
                  'endLine' => 34,
                  'startTokenPos' => 126,
                  'startFilePos' => 678,
                  'endTokenPos' => 126,
                  'endFilePos' => 686,
                ),
              ),
            ),
          ),
        ),
        'docComment' => NULL,
        'startLine' => 34,
        'endLine' => 71,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Rules\\Types',
        'declaringClassName' => 'PHPStan\\Rules\\Types\\InvalidTypesInUnionRuleTest',
        'implementingClassName' => 'PHPStan\\Rules\\Types\\InvalidTypesInUnionRuleTest',
        'currentClassName' => 'PHPStan\\Rules\\Types\\InvalidTypesInUnionRuleTest',
        'aliasName' => NULL,
      ),
      'testRuleOnUnionWithNever' => 
      array (
        'name' => 'testRuleOnUnionWithNever',
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
                'code' => '\'>=8.1.0\'',
                'attributes' => 
                array (
                  'startLine' => 73,
                  'endLine' => 73,
                  'startTokenPos' => 255,
                  'startFilePos' => 1496,
                  'endTokenPos' => 255,
                  'endFilePos' => 1504,
                ),
              ),
            ),
          ),
        ),
        'docComment' => NULL,
        'startLine' => 73,
        'endLine' => 86,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Rules\\Types',
        'declaringClassName' => 'PHPStan\\Rules\\Types\\InvalidTypesInUnionRuleTest',
        'implementingClassName' => 'PHPStan\\Rules\\Types\\InvalidTypesInUnionRuleTest',
        'currentClassName' => 'PHPStan\\Rules\\Types\\InvalidTypesInUnionRuleTest',
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