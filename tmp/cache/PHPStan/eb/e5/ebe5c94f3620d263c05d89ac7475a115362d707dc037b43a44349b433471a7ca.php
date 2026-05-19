<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/tests/PHPStan/Rules/Methods/CallToStaticMethodStatementWithoutSideEffectsRuleTest.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\Rules\Methods\CallToStaticMethodStatementWithoutSideEffectsRuleTest
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-34a6b62ffce005d9bfd10343d3226f3a8b067c74837c5a2f102c1194af94c8d6',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\Rules\\Methods\\CallToStaticMethodStatementWithoutSideEffectsRuleTest',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/tests/PHPStan/Rules/Methods/CallToStaticMethodStatementWithoutSideEffectsRuleTest.php',
      ),
    ),
    'namespace' => 'PHPStan\\Rules\\Methods',
    'name' => 'PHPStan\\Rules\\Methods\\CallToStaticMethodStatementWithoutSideEffectsRuleTest',
    'shortName' => 'CallToStaticMethodStatementWithoutSideEffectsRuleTest',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * @extends RuleTestCase<CallToStaticMethodStatementWithoutSideEffectsRule>
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 14,
    'endLine' => 166,
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
        'startLine' => 17,
        'endLine' => 33,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'PHPStan\\Rules\\Methods',
        'declaringClassName' => 'PHPStan\\Rules\\Methods\\CallToStaticMethodStatementWithoutSideEffectsRuleTest',
        'implementingClassName' => 'PHPStan\\Rules\\Methods\\CallToStaticMethodStatementWithoutSideEffectsRuleTest',
        'currentClassName' => 'PHPStan\\Rules\\Methods\\CallToStaticMethodStatementWithoutSideEffectsRuleTest',
        'aliasName' => NULL,
      ),
      'testRule' => 
      array (
        'name' => 'testRule',
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
                'code' => '\'>= 8.0.0\'',
                'attributes' => 
                array (
                  'startLine' => 35,
                  'endLine' => 35,
                  'startTokenPos' => 150,
                  'startFilePos' => 842,
                  'endTokenPos' => 150,
                  'endFilePos' => 851,
                ),
              ),
            ),
          ),
        ),
        'docComment' => NULL,
        'startLine' => 35,
        'endLine' => 44,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Rules\\Methods',
        'declaringClassName' => 'PHPStan\\Rules\\Methods\\CallToStaticMethodStatementWithoutSideEffectsRuleTest',
        'implementingClassName' => 'PHPStan\\Rules\\Methods\\CallToStaticMethodStatementWithoutSideEffectsRuleTest',
        'currentClassName' => 'PHPStan\\Rules\\Methods\\CallToStaticMethodStatementWithoutSideEffectsRuleTest',
        'aliasName' => NULL,
      ),
      'testRulePhp7' => 
      array (
        'name' => 'testRulePhp7',
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
                'code' => '\'< 8.0.0\'',
                'attributes' => 
                array (
                  'startLine' => 46,
                  'endLine' => 46,
                  'startTokenPos' => 202,
                  'startFilePos' => 1100,
                  'endTokenPos' => 202,
                  'endFilePos' => 1108,
                ),
              ),
            ),
          ),
        ),
        'docComment' => NULL,
        'startLine' => 46,
        'endLine' => 63,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Rules\\Methods',
        'declaringClassName' => 'PHPStan\\Rules\\Methods\\CallToStaticMethodStatementWithoutSideEffectsRuleTest',
        'implementingClassName' => 'PHPStan\\Rules\\Methods\\CallToStaticMethodStatementWithoutSideEffectsRuleTest',
        'currentClassName' => 'PHPStan\\Rules\\Methods\\CallToStaticMethodStatementWithoutSideEffectsRuleTest',
        'aliasName' => NULL,
      ),
      'testPhpDoc' => 
      array (
        'name' => 'testPhpDoc',
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
        'startLine' => 65,
        'endLine' => 93,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Rules\\Methods',
        'declaringClassName' => 'PHPStan\\Rules\\Methods\\CallToStaticMethodStatementWithoutSideEffectsRuleTest',
        'implementingClassName' => 'PHPStan\\Rules\\Methods\\CallToStaticMethodStatementWithoutSideEffectsRuleTest',
        'currentClassName' => 'PHPStan\\Rules\\Methods\\CallToStaticMethodStatementWithoutSideEffectsRuleTest',
        'aliasName' => NULL,
      ),
      'testBug4455' => 
      array (
        'name' => 'testBug4455',
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
        'startLine' => 95,
        'endLine' => 98,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Rules\\Methods',
        'declaringClassName' => 'PHPStan\\Rules\\Methods\\CallToStaticMethodStatementWithoutSideEffectsRuleTest',
        'implementingClassName' => 'PHPStan\\Rules\\Methods\\CallToStaticMethodStatementWithoutSideEffectsRuleTest',
        'currentClassName' => 'PHPStan\\Rules\\Methods\\CallToStaticMethodStatementWithoutSideEffectsRuleTest',
        'aliasName' => NULL,
      ),
      'testBug12224' => 
      array (
        'name' => 'testBug12224',
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
        'startLine' => 100,
        'endLine' => 103,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Rules\\Methods',
        'declaringClassName' => 'PHPStan\\Rules\\Methods\\CallToStaticMethodStatementWithoutSideEffectsRuleTest',
        'implementingClassName' => 'PHPStan\\Rules\\Methods\\CallToStaticMethodStatementWithoutSideEffectsRuleTest',
        'currentClassName' => 'PHPStan\\Rules\\Methods\\CallToStaticMethodStatementWithoutSideEffectsRuleTest',
        'aliasName' => NULL,
      ),
      'testFirstClassCallables' => 
      array (
        'name' => 'testFirstClassCallables',
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
        'startLine' => 105,
        'endLine' => 121,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Rules\\Methods',
        'declaringClassName' => 'PHPStan\\Rules\\Methods\\CallToStaticMethodStatementWithoutSideEffectsRuleTest',
        'implementingClassName' => 'PHPStan\\Rules\\Methods\\CallToStaticMethodStatementWithoutSideEffectsRuleTest',
        'currentClassName' => 'PHPStan\\Rules\\Methods\\CallToStaticMethodStatementWithoutSideEffectsRuleTest',
        'aliasName' => NULL,
      ),
      'testBug10819' => 
      array (
        'name' => 'testBug10819',
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
        'startLine' => 123,
        'endLine' => 135,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Rules\\Methods',
        'declaringClassName' => 'PHPStan\\Rules\\Methods\\CallToStaticMethodStatementWithoutSideEffectsRuleTest',
        'implementingClassName' => 'PHPStan\\Rules\\Methods\\CallToStaticMethodStatementWithoutSideEffectsRuleTest',
        'currentClassName' => 'PHPStan\\Rules\\Methods\\CallToStaticMethodStatementWithoutSideEffectsRuleTest',
        'aliasName' => NULL,
      ),
      'testDynamicStaticCall' => 
      array (
        'name' => 'testDynamicStaticCall',
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
        'startLine' => 137,
        'endLine' => 153,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Rules\\Methods',
        'declaringClassName' => 'PHPStan\\Rules\\Methods\\CallToStaticMethodStatementWithoutSideEffectsRuleTest',
        'implementingClassName' => 'PHPStan\\Rules\\Methods\\CallToStaticMethodStatementWithoutSideEffectsRuleTest',
        'currentClassName' => 'PHPStan\\Rules\\Methods\\CallToStaticMethodStatementWithoutSideEffectsRuleTest',
        'aliasName' => NULL,
      ),
      'testPipeOperator' => 
      array (
        'name' => 'testPipeOperator',
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
                'code' => '\'>= 8.5.0\'',
                'attributes' => 
                array (
                  'startLine' => 155,
                  'endLine' => 155,
                  'startTokenPos' => 650,
                  'startFilePos' => 4164,
                  'endTokenPos' => 650,
                  'endFilePos' => 4173,
                ),
              ),
            ),
          ),
        ),
        'docComment' => NULL,
        'startLine' => 155,
        'endLine' => 164,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Rules\\Methods',
        'declaringClassName' => 'PHPStan\\Rules\\Methods\\CallToStaticMethodStatementWithoutSideEffectsRuleTest',
        'implementingClassName' => 'PHPStan\\Rules\\Methods\\CallToStaticMethodStatementWithoutSideEffectsRuleTest',
        'currentClassName' => 'PHPStan\\Rules\\Methods\\CallToStaticMethodStatementWithoutSideEffectsRuleTest',
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