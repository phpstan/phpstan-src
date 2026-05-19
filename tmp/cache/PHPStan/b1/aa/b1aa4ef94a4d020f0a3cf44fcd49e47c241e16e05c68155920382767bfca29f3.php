<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/tests/PHPStan/Rules/Missing/MissingReturnRuleTest.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\Rules\Missing\MissingReturnRuleTest
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-49db46d87523bf8e04681a7e3fe013ce79051fb19cb5226135276d43ae91fc6b',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\Rules\\Missing\\MissingReturnRuleTest',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/tests/PHPStan/Rules/Missing/MissingReturnRuleTest.php',
      ),
    ),
    'namespace' => 'PHPStan\\Rules\\Missing',
    'name' => 'PHPStan\\Rules\\Missing\\MissingReturnRuleTest',
    'shortName' => 'MissingReturnRuleTest',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * @extends RuleTestCase<MissingReturnRule>
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 13,
    'endLine' => 383,
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
      'checkExplicitMixedMissingReturn' => 
      array (
        'declaringClassName' => 'PHPStan\\Rules\\Missing\\MissingReturnRuleTest',
        'implementingClassName' => 'PHPStan\\Rules\\Missing\\MissingReturnRuleTest',
        'name' => 'checkExplicitMixedMissingReturn',
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
        'startLine' => 16,
        'endLine' => 16,
        'startColumn' => 2,
        'endColumn' => 47,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'checkPhpDocMissingReturn' => 
      array (
        'declaringClassName' => 'PHPStan\\Rules\\Missing\\MissingReturnRuleTest',
        'implementingClassName' => 'PHPStan\\Rules\\Missing\\MissingReturnRuleTest',
        'name' => 'checkPhpDocMissingReturn',
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
        'default' => 
        array (
          'code' => 'true',
          'attributes' => 
          array (
            'startLine' => 18,
            'endLine' => 18,
            'startTokenPos' => 63,
            'startFilePos' => 415,
            'endTokenPos' => 63,
            'endFilePos' => 418,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 18,
        'endLine' => 18,
        'startColumn' => 2,
        'endColumn' => 47,
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
        'endLine' => 23,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'PHPStan\\Rules\\Missing',
        'declaringClassName' => 'PHPStan\\Rules\\Missing\\MissingReturnRuleTest',
        'implementingClassName' => 'PHPStan\\Rules\\Missing\\MissingReturnRuleTest',
        'currentClassName' => 'PHPStan\\Rules\\Missing\\MissingReturnRuleTest',
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
        ),
        'docComment' => NULL,
        'startLine' => 25,
        'endLine' => 127,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Rules\\Missing',
        'declaringClassName' => 'PHPStan\\Rules\\Missing\\MissingReturnRuleTest',
        'implementingClassName' => 'PHPStan\\Rules\\Missing\\MissingReturnRuleTest',
        'currentClassName' => 'PHPStan\\Rules\\Missing\\MissingReturnRuleTest',
        'aliasName' => NULL,
      ),
      'testCheckMissingReturnWithTemplateMixedType' => 
      array (
        'name' => 'testCheckMissingReturnWithTemplateMixedType',
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
        'startLine' => 129,
        'endLine' => 138,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Rules\\Missing',
        'declaringClassName' => 'PHPStan\\Rules\\Missing\\MissingReturnRuleTest',
        'implementingClassName' => 'PHPStan\\Rules\\Missing\\MissingReturnRuleTest',
        'currentClassName' => 'PHPStan\\Rules\\Missing\\MissingReturnRuleTest',
        'aliasName' => NULL,
      ),
      'testBug2875' => 
      array (
        'name' => 'testBug2875',
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
        'startLine' => 140,
        'endLine' => 144,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Rules\\Missing',
        'declaringClassName' => 'PHPStan\\Rules\\Missing\\MissingReturnRuleTest',
        'implementingClassName' => 'PHPStan\\Rules\\Missing\\MissingReturnRuleTest',
        'currentClassName' => 'PHPStan\\Rules\\Missing\\MissingReturnRuleTest',
        'aliasName' => NULL,
      ),
      'testMissingMixedReturnInEmptyBody' => 
      array (
        'name' => 'testMissingMixedReturnInEmptyBody',
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
        'startLine' => 146,
        'endLine' => 155,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Rules\\Missing',
        'declaringClassName' => 'PHPStan\\Rules\\Missing\\MissingReturnRuleTest',
        'implementingClassName' => 'PHPStan\\Rules\\Missing\\MissingReturnRuleTest',
        'currentClassName' => 'PHPStan\\Rules\\Missing\\MissingReturnRuleTest',
        'aliasName' => NULL,
      ),
      'testBug3488' => 
      array (
        'name' => 'testBug3488',
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
                  'startLine' => 157,
                  'endLine' => 157,
                  'startTokenPos' => 558,
                  'startFilePos' => 4720,
                  'endTokenPos' => 558,
                  'endFilePos' => 4729,
                ),
              ),
            ),
          ),
        ),
        'docComment' => NULL,
        'startLine' => 157,
        'endLine' => 162,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Rules\\Missing',
        'declaringClassName' => 'PHPStan\\Rules\\Missing\\MissingReturnRuleTest',
        'implementingClassName' => 'PHPStan\\Rules\\Missing\\MissingReturnRuleTest',
        'currentClassName' => 'PHPStan\\Rules\\Missing\\MissingReturnRuleTest',
        'aliasName' => NULL,
      ),
      'testBug3669' => 
      array (
        'name' => 'testBug3669',
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
        'startLine' => 164,
        'endLine' => 170,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Rules\\Missing',
        'declaringClassName' => 'PHPStan\\Rules\\Missing\\MissingReturnRuleTest',
        'implementingClassName' => 'PHPStan\\Rules\\Missing\\MissingReturnRuleTest',
        'currentClassName' => 'PHPStan\\Rules\\Missing\\MissingReturnRuleTest',
        'aliasName' => NULL,
      ),
      'dataCheckPhpDocMissingReturn' => 
      array (
        'name' => 'dataCheckPhpDocMissingReturn',
        'parameters' => 
        array (
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
        'startLine' => 172,
        'endLine' => 258,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'PHPStan\\Rules\\Missing',
        'declaringClassName' => 'PHPStan\\Rules\\Missing\\MissingReturnRuleTest',
        'implementingClassName' => 'PHPStan\\Rules\\Missing\\MissingReturnRuleTest',
        'currentClassName' => 'PHPStan\\Rules\\Missing\\MissingReturnRuleTest',
        'aliasName' => NULL,
      ),
      'testCheckPhpDocMissingReturn' => 
      array (
        'name' => 'testCheckPhpDocMissingReturn',
        'parameters' => 
        array (
          'checkPhpDocMissingReturn' => 
          array (
            'name' => 'checkPhpDocMissingReturn',
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
            'startLine' => 265,
            'endLine' => 265,
            'startColumn' => 47,
            'endColumn' => 76,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'errors' => 
          array (
            'name' => 'errors',
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
            'startLine' => 265,
            'endLine' => 265,
            'startColumn' => 79,
            'endColumn' => 91,
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
            'name' => 'PHPUnit\\Framework\\Attributes\\RequiresPhp',
            'isRepeated' => false,
            'arguments' => 
            array (
              0 => 
              array (
                'code' => '\'>= 8.0.0\'',
                'attributes' => 
                array (
                  'startLine' => 263,
                  'endLine' => 263,
                  'startTokenPos' => 908,
                  'startFilePos' => 7786,
                  'endTokenPos' => 908,
                  'endFilePos' => 7795,
                ),
              ),
            ),
          ),
          1 => 
          array (
            'name' => 'PHPUnit\\Framework\\Attributes\\DataProvider',
            'isRepeated' => false,
            'arguments' => 
            array (
              0 => 
              array (
                'code' => '\'dataCheckPhpDocMissingReturn\'',
                'attributes' => 
                array (
                  'startLine' => 264,
                  'endLine' => 264,
                  'startTokenPos' => 915,
                  'startFilePos' => 7815,
                  'endTokenPos' => 915,
                  'endFilePos' => 7844,
                ),
              ),
            ),
          ),
        ),
        'docComment' => '/**
 * @param list<array{0: string, 1: int, 2?: string}> $errors
 */',
        'startLine' => 263,
        'endLine' => 270,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Rules\\Missing',
        'declaringClassName' => 'PHPStan\\Rules\\Missing\\MissingReturnRuleTest',
        'implementingClassName' => 'PHPStan\\Rules\\Missing\\MissingReturnRuleTest',
        'currentClassName' => 'PHPStan\\Rules\\Missing\\MissingReturnRuleTest',
        'aliasName' => NULL,
      ),
      'dataModelMixin' => 
      array (
        'name' => 'dataModelMixin',
        'parameters' => 
        array (
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
        'startLine' => 272,
        'endLine' => 282,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'PHPStan\\Rules\\Missing',
        'declaringClassName' => 'PHPStan\\Rules\\Missing\\MissingReturnRuleTest',
        'implementingClassName' => 'PHPStan\\Rules\\Missing\\MissingReturnRuleTest',
        'currentClassName' => 'PHPStan\\Rules\\Missing\\MissingReturnRuleTest',
        'aliasName' => NULL,
      ),
      'testModelMixin' => 
      array (
        'name' => 'testModelMixin',
        'parameters' => 
        array (
          'checkExplicitMixedMissingReturn' => 
          array (
            'name' => 'checkExplicitMixedMissingReturn',
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
            'startLine' => 286,
            'endLine' => 286,
            'startColumn' => 33,
            'endColumn' => 69,
            'parameterIndex' => 0,
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
            'name' => 'PHPUnit\\Framework\\Attributes\\RequiresPhp',
            'isRepeated' => false,
            'arguments' => 
            array (
              0 => 
              array (
                'code' => '\'>= 8.0.0\'',
                'attributes' => 
                array (
                  'startLine' => 284,
                  'endLine' => 284,
                  'startTokenPos' => 1020,
                  'startFilePos' => 8275,
                  'endTokenPos' => 1020,
                  'endFilePos' => 8284,
                ),
              ),
            ),
          ),
          1 => 
          array (
            'name' => 'PHPUnit\\Framework\\Attributes\\DataProvider',
            'isRepeated' => false,
            'arguments' => 
            array (
              0 => 
              array (
                'code' => '\'dataModelMixin\'',
                'attributes' => 
                array (
                  'startLine' => 285,
                  'endLine' => 285,
                  'startTokenPos' => 1027,
                  'startFilePos' => 8304,
                  'endTokenPos' => 1027,
                  'endFilePos' => 8319,
                ),
              ),
            ),
          ),
        ),
        'docComment' => NULL,
        'startLine' => 284,
        'endLine' => 296,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Rules\\Missing',
        'declaringClassName' => 'PHPStan\\Rules\\Missing\\MissingReturnRuleTest',
        'implementingClassName' => 'PHPStan\\Rules\\Missing\\MissingReturnRuleTest',
        'currentClassName' => 'PHPStan\\Rules\\Missing\\MissingReturnRuleTest',
        'aliasName' => NULL,
      ),
      'testBug6257' => 
      array (
        'name' => 'testBug6257',
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
                  'startLine' => 298,
                  'endLine' => 298,
                  'startTokenPos' => 1100,
                  'startFilePos' => 8737,
                  'endTokenPos' => 1100,
                  'endFilePos' => 8746,
                ),
              ),
            ),
          ),
        ),
        'docComment' => NULL,
        'startLine' => 298,
        'endLine' => 309,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Rules\\Missing',
        'declaringClassName' => 'PHPStan\\Rules\\Missing\\MissingReturnRuleTest',
        'implementingClassName' => 'PHPStan\\Rules\\Missing\\MissingReturnRuleTest',
        'currentClassName' => 'PHPStan\\Rules\\Missing\\MissingReturnRuleTest',
        'aliasName' => NULL,
      ),
      'testBug7384' => 
      array (
        'name' => 'testBug7384',
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
        'startLine' => 311,
        'endLine' => 316,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Rules\\Missing',
        'declaringClassName' => 'PHPStan\\Rules\\Missing\\MissingReturnRuleTest',
        'implementingClassName' => 'PHPStan\\Rules\\Missing\\MissingReturnRuleTest',
        'currentClassName' => 'PHPStan\\Rules\\Missing\\MissingReturnRuleTest',
        'aliasName' => NULL,
      ),
      'testBug9309' => 
      array (
        'name' => 'testBug9309',
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
        'startLine' => 318,
        'endLine' => 322,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Rules\\Missing',
        'declaringClassName' => 'PHPStan\\Rules\\Missing\\MissingReturnRuleTest',
        'implementingClassName' => 'PHPStan\\Rules\\Missing\\MissingReturnRuleTest',
        'currentClassName' => 'PHPStan\\Rules\\Missing\\MissingReturnRuleTest',
        'aliasName' => NULL,
      ),
      'testBug6807' => 
      array (
        'name' => 'testBug6807',
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
        'startLine' => 324,
        'endLine' => 328,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Rules\\Missing',
        'declaringClassName' => 'PHPStan\\Rules\\Missing\\MissingReturnRuleTest',
        'implementingClassName' => 'PHPStan\\Rules\\Missing\\MissingReturnRuleTest',
        'currentClassName' => 'PHPStan\\Rules\\Missing\\MissingReturnRuleTest',
        'aliasName' => NULL,
      ),
      'testBug8463' => 
      array (
        'name' => 'testBug8463',
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
        'startLine' => 330,
        'endLine' => 334,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Rules\\Missing',
        'declaringClassName' => 'PHPStan\\Rules\\Missing\\MissingReturnRuleTest',
        'implementingClassName' => 'PHPStan\\Rules\\Missing\\MissingReturnRuleTest',
        'currentClassName' => 'PHPStan\\Rules\\Missing\\MissingReturnRuleTest',
        'aliasName' => NULL,
      ),
      'testBug9374' => 
      array (
        'name' => 'testBug9374',
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
        'startLine' => 336,
        'endLine' => 340,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Rules\\Missing',
        'declaringClassName' => 'PHPStan\\Rules\\Missing\\MissingReturnRuleTest',
        'implementingClassName' => 'PHPStan\\Rules\\Missing\\MissingReturnRuleTest',
        'currentClassName' => 'PHPStan\\Rules\\Missing\\MissingReturnRuleTest',
        'aliasName' => NULL,
      ),
      'testPropertyHooks' => 
      array (
        'name' => 'testPropertyHooks',
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
                'code' => '\'>= 8.4.0\'',
                'attributes' => 
                array (
                  'startLine' => 342,
                  'endLine' => 342,
                  'startTokenPos' => 1389,
                  'startFilePos' => 9896,
                  'endTokenPos' => 1389,
                  'endFilePos' => 9905,
                ),
              ),
            ),
          ),
        ),
        'docComment' => NULL,
        'startLine' => 342,
        'endLine' => 356,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Rules\\Missing',
        'declaringClassName' => 'PHPStan\\Rules\\Missing\\MissingReturnRuleTest',
        'implementingClassName' => 'PHPStan\\Rules\\Missing\\MissingReturnRuleTest',
        'currentClassName' => 'PHPStan\\Rules\\Missing\\MissingReturnRuleTest',
        'aliasName' => NULL,
      ),
      'testBug3488Two' => 
      array (
        'name' => 'testBug3488Two',
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
        'startLine' => 358,
        'endLine' => 367,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Rules\\Missing',
        'declaringClassName' => 'PHPStan\\Rules\\Missing\\MissingReturnRuleTest',
        'implementingClassName' => 'PHPStan\\Rules\\Missing\\MissingReturnRuleTest',
        'currentClassName' => 'PHPStan\\Rules\\Missing\\MissingReturnRuleTest',
        'aliasName' => NULL,
      ),
      'testBug12722' => 
      array (
        'name' => 'testBug12722',
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
                  'startLine' => 369,
                  'endLine' => 369,
                  'startTokenPos' => 1515,
                  'startFilePos' => 10637,
                  'endTokenPos' => 1515,
                  'endFilePos' => 10646,
                ),
              ),
            ),
          ),
        ),
        'docComment' => NULL,
        'startLine' => 369,
        'endLine' => 374,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Rules\\Missing',
        'declaringClassName' => 'PHPStan\\Rules\\Missing\\MissingReturnRuleTest',
        'implementingClassName' => 'PHPStan\\Rules\\Missing\\MissingReturnRuleTest',
        'currentClassName' => 'PHPStan\\Rules\\Missing\\MissingReturnRuleTest',
        'aliasName' => NULL,
      ),
      'testBug14638' => 
      array (
        'name' => 'testBug14638',
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
                'code' => '\'>= 8.1\'',
                'attributes' => 
                array (
                  'startLine' => 376,
                  'endLine' => 376,
                  'startTokenPos' => 1564,
                  'startFilePos' => 10816,
                  'endTokenPos' => 1564,
                  'endFilePos' => 10823,
                ),
              ),
            ),
          ),
        ),
        'docComment' => NULL,
        'startLine' => 376,
        'endLine' => 381,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Rules\\Missing',
        'declaringClassName' => 'PHPStan\\Rules\\Missing\\MissingReturnRuleTest',
        'implementingClassName' => 'PHPStan\\Rules\\Missing\\MissingReturnRuleTest',
        'currentClassName' => 'PHPStan\\Rules\\Missing\\MissingReturnRuleTest',
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