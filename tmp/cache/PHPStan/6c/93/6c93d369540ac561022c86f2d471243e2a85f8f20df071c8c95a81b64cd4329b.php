<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/tests/PHPStan/Rules/Methods/MethodCallableRuleTest.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\Rules\Methods\MethodCallableRuleTest
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-3ea2a9defc9d986965a0aba8ce792a0790ef555925efe199a295e16ac04f21b8',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\Rules\\Methods\\MethodCallableRuleTest',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/tests/PHPStan/Rules/Methods/MethodCallableRuleTest.php',
      ),
    ),
    'namespace' => 'PHPStan\\Rules\\Methods',
    'name' => 'PHPStan\\Rules\\Methods\\MethodCallableRuleTest',
    'shortName' => 'MethodCallableRuleTest',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * @extends RuleTestCase<MethodCallableRule>
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 15,
    'endLine' => 94,
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
        'declaringClassName' => 'PHPStan\\Rules\\Methods\\MethodCallableRuleTest',
        'implementingClassName' => 'PHPStan\\Rules\\Methods\\MethodCallableRuleTest',
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
        'default' => 
        array (
          'code' => '\\PHP_VERSION_ID',
          'attributes' => 
          array (
            'startLine' => 18,
            'endLine' => 18,
            'startTokenPos' => 68,
            'startFilePos' => 395,
            'endTokenPos' => 68,
            'endFilePos' => 408,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 18,
        'endLine' => 18,
        'startColumn' => 2,
        'endColumn' => 42,
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
        'startLine' => 20,
        'endLine' => 43,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'PHPStan\\Rules\\Methods',
        'declaringClassName' => 'PHPStan\\Rules\\Methods\\MethodCallableRuleTest',
        'implementingClassName' => 'PHPStan\\Rules\\Methods\\MethodCallableRuleTest',
        'currentClassName' => 'PHPStan\\Rules\\Methods\\MethodCallableRuleTest',
        'aliasName' => NULL,
      ),
      'testNotSupportedOnOlderVersions' => 
      array (
        'name' => 'testNotSupportedOnOlderVersions',
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
                'code' => '\'< 8.1.0\'',
                'attributes' => 
                array (
                  'startLine' => 45,
                  'endLine' => 45,
                  'startTokenPos' => 203,
                  'startFilePos' => 1021,
                  'endTokenPos' => 203,
                  'endFilePos' => 1029,
                ),
              ),
            ),
          ),
        ),
        'docComment' => NULL,
        'startLine' => 45,
        'endLine' => 54,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Rules\\Methods',
        'declaringClassName' => 'PHPStan\\Rules\\Methods\\MethodCallableRuleTest',
        'implementingClassName' => 'PHPStan\\Rules\\Methods\\MethodCallableRuleTest',
        'currentClassName' => 'PHPStan\\Rules\\Methods\\MethodCallableRuleTest',
        'aliasName' => NULL,
      ),
      'testBug13596' => 
      array (
        'name' => 'testBug13596',
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
                  'startLine' => 56,
                  'endLine' => 56,
                  'startTokenPos' => 255,
                  'startFilePos' => 1281,
                  'endTokenPos' => 255,
                  'endFilePos' => 1290,
                ),
              ),
            ),
          ),
        ),
        'docComment' => NULL,
        'startLine' => 56,
        'endLine' => 60,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Rules\\Methods',
        'declaringClassName' => 'PHPStan\\Rules\\Methods\\MethodCallableRuleTest',
        'implementingClassName' => 'PHPStan\\Rules\\Methods\\MethodCallableRuleTest',
        'currentClassName' => 'PHPStan\\Rules\\Methods\\MethodCallableRuleTest',
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
                'code' => '\'>= 8.1.0\'',
                'attributes' => 
                array (
                  'startLine' => 62,
                  'endLine' => 62,
                  'startTokenPos' => 295,
                  'startFilePos' => 1411,
                  'endTokenPos' => 295,
                  'endFilePos' => 1420,
                ),
              ),
            ),
          ),
        ),
        'docComment' => NULL,
        'startLine' => 62,
        'endLine' => 92,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Rules\\Methods',
        'declaringClassName' => 'PHPStan\\Rules\\Methods\\MethodCallableRuleTest',
        'implementingClassName' => 'PHPStan\\Rules\\Methods\\MethodCallableRuleTest',
        'currentClassName' => 'PHPStan\\Rules\\Methods\\MethodCallableRuleTest',
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