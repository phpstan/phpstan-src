<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/tests/PHPStan/Rules/Exceptions/MethodThrowTypeCovarianceRuleTest.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\Rules\Exceptions\MethodThrowTypeCovarianceRuleTest
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-8ca2a33f6019c1d4fe51f10b562cec191b563682e53ebf3ccd112af6a724994f',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\Rules\\Exceptions\\MethodThrowTypeCovarianceRuleTest',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/tests/PHPStan/Rules/Exceptions/MethodThrowTypeCovarianceRuleTest.php',
      ),
    ),
    'namespace' => 'PHPStan\\Rules\\Exceptions',
    'name' => 'PHPStan\\Rules\\Exceptions\\MethodThrowTypeCovarianceRuleTest',
    'shortName' => 'MethodThrowTypeCovarianceRuleTest',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * @extends RuleTestCase<MethodThrowTypeCovarianceRule>
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 14,
    'endLine' => 71,
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
      'implicitThrows' => 
      array (
        'declaringClassName' => 'PHPStan\\Rules\\Exceptions\\MethodThrowTypeCovarianceRuleTest',
        'implementingClassName' => 'PHPStan\\Rules\\Exceptions\\MethodThrowTypeCovarianceRuleTest',
        'name' => 'implicitThrows',
        'modifiers' => 4,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'bool',
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
        'endColumn' => 30,
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
        'endLine' => 24,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'PHPStan\\Rules\\Exceptions',
        'declaringClassName' => 'PHPStan\\Rules\\Exceptions\\MethodThrowTypeCovarianceRuleTest',
        'implementingClassName' => 'PHPStan\\Rules\\Exceptions\\MethodThrowTypeCovarianceRuleTest',
        'currentClassName' => 'PHPStan\\Rules\\Exceptions\\MethodThrowTypeCovarianceRuleTest',
        'aliasName' => NULL,
      ),
      'dataRule' => 
      array (
        'name' => 'dataRule',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'iterable',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 26,
        'endLine' => 59,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => true,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'PHPStan\\Rules\\Exceptions',
        'declaringClassName' => 'PHPStan\\Rules\\Exceptions\\MethodThrowTypeCovarianceRuleTest',
        'implementingClassName' => 'PHPStan\\Rules\\Exceptions\\MethodThrowTypeCovarianceRuleTest',
        'currentClassName' => 'PHPStan\\Rules\\Exceptions\\MethodThrowTypeCovarianceRuleTest',
        'aliasName' => NULL,
      ),
      'testRule' => 
      array (
        'name' => 'testRule',
        'parameters' => 
        array (
          'implicitThrows' => 
          array (
            'name' => 'implicitThrows',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'bool',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 65,
            'endLine' => 65,
            'startColumn' => 27,
            'endColumn' => 46,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'expectedErrors' => 
          array (
            'name' => 'expectedErrors',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'array',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 65,
            'endLine' => 65,
            'startColumn' => 49,
            'endColumn' => 69,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
          0 => 
          array (
            'name' => 'PHPUnit\\Framework\\Attributes\\DataProvider',
            'isRepeated' => false,
            'arguments' => 
            array (
              0 => 
              array (
                'code' => '\'dataRule\'',
                'attributes' => 
                array (
                  'startLine' => 64,
                  'endLine' => 64,
                  'startTokenPos' => 220,
                  'startFilePos' => 2045,
                  'endTokenPos' => 220,
                  'endFilePos' => 2054,
                ),
              ),
            ),
          ),
        ),
        'docComment' => '/**
 * @param list<array{0: string, 1: int, 2?: string|null}> $expectedErrors
 */',
        'startLine' => 64,
        'endLine' => 69,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Rules\\Exceptions',
        'declaringClassName' => 'PHPStan\\Rules\\Exceptions\\MethodThrowTypeCovarianceRuleTest',
        'implementingClassName' => 'PHPStan\\Rules\\Exceptions\\MethodThrowTypeCovarianceRuleTest',
        'currentClassName' => 'PHPStan\\Rules\\Exceptions\\MethodThrowTypeCovarianceRuleTest',
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