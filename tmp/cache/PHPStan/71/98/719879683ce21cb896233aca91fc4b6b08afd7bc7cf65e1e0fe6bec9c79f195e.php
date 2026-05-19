<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/tests/PHPStan/Rules/Methods/CallToMethodStatementWithoutSideEffectsRuleTest.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\Rules\Methods\CallToMethodStatementWithoutSideEffectsRuleTest
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-a4c49e2ba89cc8b313d7959c87db8ce5d3cb97faccc6697ab4b87a78c399d863',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\Rules\\Methods\\CallToMethodStatementWithoutSideEffectsRuleTest',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/tests/PHPStan/Rules/Methods/CallToMethodStatementWithoutSideEffectsRuleTest.php',
      ),
    ),
    'namespace' => 'PHPStan\\Rules\\Methods',
    'name' => 'PHPStan\\Rules\\Methods\\CallToMethodStatementWithoutSideEffectsRuleTest',
    'shortName' => 'CallToMethodStatementWithoutSideEffectsRuleTest',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * @extends RuleTestCase<CallToMethodStatementWithoutSideEffectsRule>
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 15,
    'endLine' => 184,
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
        'startLine' => 18,
        'endLine' => 32,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'PHPStan\\Rules\\Methods',
        'declaringClassName' => 'PHPStan\\Rules\\Methods\\CallToMethodStatementWithoutSideEffectsRuleTest',
        'implementingClassName' => 'PHPStan\\Rules\\Methods\\CallToMethodStatementWithoutSideEffectsRuleTest',
        'currentClassName' => 'PHPStan\\Rules\\Methods\\CallToMethodStatementWithoutSideEffectsRuleTest',
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
                  'startLine' => 34,
                  'endLine' => 34,
                  'startTokenPos' => 147,
                  'startFilePos' => 817,
                  'endTokenPos' => 147,
                  'endFilePos' => 826,
                ),
              ),
            ),
          ),
        ),
        'docComment' => NULL,
        'startLine' => 34,
        'endLine' => 55,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Rules\\Methods',
        'declaringClassName' => 'PHPStan\\Rules\\Methods\\CallToMethodStatementWithoutSideEffectsRuleTest',
        'implementingClassName' => 'PHPStan\\Rules\\Methods\\CallToMethodStatementWithoutSideEffectsRuleTest',
        'currentClassName' => 'PHPStan\\Rules\\Methods\\CallToMethodStatementWithoutSideEffectsRuleTest',
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
                  'startLine' => 57,
                  'endLine' => 57,
                  'startTokenPos' => 232,
                  'startFilePos' => 1431,
                  'endTokenPos' => 232,
                  'endFilePos' => 1439,
                ),
              ),
            ),
          ),
        ),
        'docComment' => NULL,
        'startLine' => 57,
        'endLine' => 82,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Rules\\Methods',
        'declaringClassName' => 'PHPStan\\Rules\\Methods\\CallToMethodStatementWithoutSideEffectsRuleTest',
        'implementingClassName' => 'PHPStan\\Rules\\Methods\\CallToMethodStatementWithoutSideEffectsRuleTest',
        'currentClassName' => 'PHPStan\\Rules\\Methods\\CallToMethodStatementWithoutSideEffectsRuleTest',
        'aliasName' => NULL,
      ),
      'testNullsafe' => 
      array (
        'name' => 'testNullsafe',
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
        'startLine' => 84,
        'endLine' => 92,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Rules\\Methods',
        'declaringClassName' => 'PHPStan\\Rules\\Methods\\CallToMethodStatementWithoutSideEffectsRuleTest',
        'implementingClassName' => 'PHPStan\\Rules\\Methods\\CallToMethodStatementWithoutSideEffectsRuleTest',
        'currentClassName' => 'PHPStan\\Rules\\Methods\\CallToMethodStatementWithoutSideEffectsRuleTest',
        'aliasName' => NULL,
      ),
      'testBug4232' => 
      array (
        'name' => 'testBug4232',
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
        'startLine' => 94,
        'endLine' => 97,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Rules\\Methods',
        'declaringClassName' => 'PHPStan\\Rules\\Methods\\CallToMethodStatementWithoutSideEffectsRuleTest',
        'implementingClassName' => 'PHPStan\\Rules\\Methods\\CallToMethodStatementWithoutSideEffectsRuleTest',
        'currentClassName' => 'PHPStan\\Rules\\Methods\\CallToMethodStatementWithoutSideEffectsRuleTest',
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
        'startLine' => 99,
        'endLine' => 123,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Rules\\Methods',
        'declaringClassName' => 'PHPStan\\Rules\\Methods\\CallToMethodStatementWithoutSideEffectsRuleTest',
        'implementingClassName' => 'PHPStan\\Rules\\Methods\\CallToMethodStatementWithoutSideEffectsRuleTest',
        'currentClassName' => 'PHPStan\\Rules\\Methods\\CallToMethodStatementWithoutSideEffectsRuleTest',
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
        'startLine' => 125,
        'endLine' => 128,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Rules\\Methods',
        'declaringClassName' => 'PHPStan\\Rules\\Methods\\CallToMethodStatementWithoutSideEffectsRuleTest',
        'implementingClassName' => 'PHPStan\\Rules\\Methods\\CallToMethodStatementWithoutSideEffectsRuleTest',
        'currentClassName' => 'PHPStan\\Rules\\Methods\\CallToMethodStatementWithoutSideEffectsRuleTest',
        'aliasName' => NULL,
      ),
      'testBug11503' => 
      array (
        'name' => 'testBug11503',
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
        'startLine' => 130,
        'endLine' => 148,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Rules\\Methods',
        'declaringClassName' => 'PHPStan\\Rules\\Methods\\CallToMethodStatementWithoutSideEffectsRuleTest',
        'implementingClassName' => 'PHPStan\\Rules\\Methods\\CallToMethodStatementWithoutSideEffectsRuleTest',
        'currentClassName' => 'PHPStan\\Rules\\Methods\\CallToMethodStatementWithoutSideEffectsRuleTest',
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
        'startLine' => 150,
        'endLine' => 153,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Rules\\Methods',
        'declaringClassName' => 'PHPStan\\Rules\\Methods\\CallToMethodStatementWithoutSideEffectsRuleTest',
        'implementingClassName' => 'PHPStan\\Rules\\Methods\\CallToMethodStatementWithoutSideEffectsRuleTest',
        'currentClassName' => 'PHPStan\\Rules\\Methods\\CallToMethodStatementWithoutSideEffectsRuleTest',
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
        'startLine' => 155,
        'endLine' => 171,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Rules\\Methods',
        'declaringClassName' => 'PHPStan\\Rules\\Methods\\CallToMethodStatementWithoutSideEffectsRuleTest',
        'implementingClassName' => 'PHPStan\\Rules\\Methods\\CallToMethodStatementWithoutSideEffectsRuleTest',
        'currentClassName' => 'PHPStan\\Rules\\Methods\\CallToMethodStatementWithoutSideEffectsRuleTest',
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
                  'startLine' => 173,
                  'endLine' => 173,
                  'startTokenPos' => 762,
                  'startFilePos' => 4927,
                  'endTokenPos' => 762,
                  'endFilePos' => 4936,
                ),
              ),
            ),
          ),
        ),
        'docComment' => NULL,
        'startLine' => 173,
        'endLine' => 182,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Rules\\Methods',
        'declaringClassName' => 'PHPStan\\Rules\\Methods\\CallToMethodStatementWithoutSideEffectsRuleTest',
        'implementingClassName' => 'PHPStan\\Rules\\Methods\\CallToMethodStatementWithoutSideEffectsRuleTest',
        'currentClassName' => 'PHPStan\\Rules\\Methods\\CallToMethodStatementWithoutSideEffectsRuleTest',
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