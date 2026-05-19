<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/tests/PHPStan/Rules/Classes/InvalidPromotedPropertiesRuleTest.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\Rules\Classes\InvalidPromotedPropertiesRuleTest
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-94e80d6ad4ead917303ff67047a7637d5243f99cdb15c501394acd045827b946',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\Rules\\Classes\\InvalidPromotedPropertiesRuleTest',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/tests/PHPStan/Rules/Classes/InvalidPromotedPropertiesRuleTest.php',
      ),
    ),
    'namespace' => 'PHPStan\\Rules\\Classes',
    'name' => 'PHPStan\\Rules\\Classes\\InvalidPromotedPropertiesRuleTest',
    'shortName' => 'InvalidPromotedPropertiesRuleTest',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * @extends RuleTestCase<InvalidPromotedPropertiesRule>
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 14,
    'endLine' => 133,
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
      'phpVersion' => 
      array (
        'declaringClassName' => 'PHPStan\\Rules\\Classes\\InvalidPromotedPropertiesRuleTest',
        'implementingClassName' => 'PHPStan\\Rules\\Classes\\InvalidPromotedPropertiesRuleTest',
        'name' => 'phpVersion',
        'modifiers' => 4,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'int',
            'isIdentifier' => true,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 17,
        'endLine' => 17,
        'startColumn' => 2,
        'endColumn' => 25,
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
        'namespace' => 'PHPStan\\Rules\\Classes',
        'declaringClassName' => 'PHPStan\\Rules\\Classes\\InvalidPromotedPropertiesRuleTest',
        'implementingClassName' => 'PHPStan\\Rules\\Classes\\InvalidPromotedPropertiesRuleTest',
        'currentClassName' => 'PHPStan\\Rules\\Classes\\InvalidPromotedPropertiesRuleTest',
        'aliasName' => NULL,
      ),
      'testNotSupportedOnPhp7' => 
      array (
        'name' => 'testNotSupportedOnPhp7',
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
        'startLine' => 24,
        'endLine' => 61,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Rules\\Classes',
        'declaringClassName' => 'PHPStan\\Rules\\Classes\\InvalidPromotedPropertiesRuleTest',
        'implementingClassName' => 'PHPStan\\Rules\\Classes\\InvalidPromotedPropertiesRuleTest',
        'currentClassName' => 'PHPStan\\Rules\\Classes\\InvalidPromotedPropertiesRuleTest',
        'aliasName' => NULL,
      ),
      'testSupportedOnPhp8' => 
      array (
        'name' => 'testSupportedOnPhp8',
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
        'startLine' => 63,
        'endLine' => 96,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Rules\\Classes',
        'declaringClassName' => 'PHPStan\\Rules\\Classes\\InvalidPromotedPropertiesRuleTest',
        'implementingClassName' => 'PHPStan\\Rules\\Classes\\InvalidPromotedPropertiesRuleTest',
        'currentClassName' => 'PHPStan\\Rules\\Classes\\InvalidPromotedPropertiesRuleTest',
        'aliasName' => NULL,
      ),
      'testBug9577' => 
      array (
        'name' => 'testBug9577',
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
                  'startLine' => 98,
                  'endLine' => 98,
                  'startTokenPos' => 348,
                  'startFilePos' => 2085,
                  'endTokenPos' => 348,
                  'endFilePos' => 2094,
                ),
              ),
            ),
          ),
        ),
        'docComment' => NULL,
        'startLine' => 98,
        'endLine' => 103,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Rules\\Classes',
        'declaringClassName' => 'PHPStan\\Rules\\Classes\\InvalidPromotedPropertiesRuleTest',
        'implementingClassName' => 'PHPStan\\Rules\\Classes\\InvalidPromotedPropertiesRuleTest',
        'currentClassName' => 'PHPStan\\Rules\\Classes\\InvalidPromotedPropertiesRuleTest',
        'aliasName' => NULL,
      ),
      'testHooks' => 
      array (
        'name' => 'testHooks',
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
                  'startLine' => 105,
                  'endLine' => 105,
                  'startTokenPos' => 397,
                  'startFilePos' => 2242,
                  'endTokenPos' => 397,
                  'endFilePos' => 2251,
                ),
              ),
            ),
          ),
        ),
        'docComment' => NULL,
        'startLine' => 105,
        'endLine' => 115,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Rules\\Classes',
        'declaringClassName' => 'PHPStan\\Rules\\Classes\\InvalidPromotedPropertiesRuleTest',
        'implementingClassName' => 'PHPStan\\Rules\\Classes\\InvalidPromotedPropertiesRuleTest',
        'currentClassName' => 'PHPStan\\Rules\\Classes\\InvalidPromotedPropertiesRuleTest',
        'aliasName' => NULL,
      ),
      'testFinalProperty' => 
      array (
        'name' => 'testFinalProperty',
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
                  'startLine' => 117,
                  'endLine' => 117,
                  'startTokenPos' => 458,
                  'startFilePos' => 2490,
                  'endTokenPos' => 458,
                  'endFilePos' => 2499,
                ),
              ),
            ),
          ),
        ),
        'docComment' => NULL,
        'startLine' => 117,
        'endLine' => 131,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Rules\\Classes',
        'declaringClassName' => 'PHPStan\\Rules\\Classes\\InvalidPromotedPropertiesRuleTest',
        'implementingClassName' => 'PHPStan\\Rules\\Classes\\InvalidPromotedPropertiesRuleTest',
        'currentClassName' => 'PHPStan\\Rules\\Classes\\InvalidPromotedPropertiesRuleTest',
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