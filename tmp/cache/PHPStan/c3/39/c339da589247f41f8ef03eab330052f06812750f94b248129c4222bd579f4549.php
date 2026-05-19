<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/tests/PHPStan/Analyser/TypeSpecifierTest.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\Analyser\TypeSpecifierTest
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-df5133e6c5c1ea74a0d7b18065037a77667f3095f6c1eecfa772d7effdbe88c3',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\Analyser\\TypeSpecifierTest',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/tests/PHPStan/Analyser/TypeSpecifierTest.php',
      ),
    ),
    'namespace' => 'PHPStan\\Analyser',
    'name' => 'PHPStan\\Analyser\\TypeSpecifierTest',
    'shortName' => 'TypeSpecifierTest',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => NULL,
    'attributes' => 
    array (
    ),
    'startLine' => 46,
    'endLine' => 1365,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'PHPStan\\Testing\\PHPStanTestCase',
    'implementsClassNames' => 
    array (
    ),
    'traitClassNames' => 
    array (
    ),
    'immediateConstants' => 
    array (
      'FALSEY_TYPE_DESCRIPTION' => 
      array (
        'declaringClassName' => 'PHPStan\\Analyser\\TypeSpecifierTest',
        'implementingClassName' => 'PHPStan\\Analyser\\TypeSpecifierTest',
        'name' => 'FALSEY_TYPE_DESCRIPTION',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'0|0.0|\\\'\\\'|\\\'0\\\'|array{}|false|null\'',
          'attributes' => 
          array (
            'startLine' => 49,
            'endLine' => 49,
            'startTokenPos' => 244,
            'startFilePos' => 1466,
            'endTokenPos' => 244,
            'endFilePos' => 1502,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 49,
        'endLine' => 49,
        'startColumn' => 2,
        'endColumn' => 79,
      ),
      'TRUTHY_TYPE_DESCRIPTION' => 
      array (
        'declaringClassName' => 'PHPStan\\Analyser\\TypeSpecifierTest',
        'implementingClassName' => 'PHPStan\\Analyser\\TypeSpecifierTest',
        'name' => 'TRUTHY_TYPE_DESCRIPTION',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'mixed~(\' . self::FALSEY_TYPE_DESCRIPTION . \')\'',
          'attributes' => 
          array (
            'startLine' => 50,
            'endLine' => 50,
            'startTokenPos' => 255,
            'startFilePos' => 1546,
            'endTokenPos' => 265,
            'endFilePos' => 1592,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 50,
        'endLine' => 50,
        'startColumn' => 2,
        'endColumn' => 89,
      ),
      'SURE_NOT_FALSEY' => 
      array (
        'declaringClassName' => 'PHPStan\\Analyser\\TypeSpecifierTest',
        'implementingClassName' => 'PHPStan\\Analyser\\TypeSpecifierTest',
        'name' => 'SURE_NOT_FALSEY',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'~\' . self::FALSEY_TYPE_DESCRIPTION',
          'attributes' => 
          array (
            'startLine' => 51,
            'endLine' => 51,
            'startTokenPos' => 276,
            'startFilePos' => 1628,
            'endTokenPos' => 282,
            'endFilePos' => 1662,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 51,
        'endLine' => 51,
        'startColumn' => 2,
        'endColumn' => 69,
      ),
      'SURE_NOT_TRUTHY' => 
      array (
        'declaringClassName' => 'PHPStan\\Analyser\\TypeSpecifierTest',
        'implementingClassName' => 'PHPStan\\Analyser\\TypeSpecifierTest',
        'name' => 'SURE_NOT_TRUTHY',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'~\' . self::TRUTHY_TYPE_DESCRIPTION',
          'attributes' => 
          array (
            'startLine' => 52,
            'endLine' => 52,
            'startTokenPos' => 293,
            'startFilePos' => 1698,
            'endTokenPos' => 299,
            'endFilePos' => 1732,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 52,
        'endLine' => 52,
        'startColumn' => 2,
        'endColumn' => 69,
      ),
    ),
    'immediateProperties' => 
    array (
      'printer' => 
      array (
        'declaringClassName' => 'PHPStan\\Analyser\\TypeSpecifierTest',
        'implementingClassName' => 'PHPStan\\Analyser\\TypeSpecifierTest',
        'name' => 'printer',
        'modifiers' => 4,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PhpParser\\PrettyPrinter\\Standard',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => '/** @var Standard () */',
        'attributes' => 
        array (
        ),
        'startLine' => 55,
        'endLine' => 55,
        'startColumn' => 2,
        'endColumn' => 27,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'typeSpecifier' => 
      array (
        'declaringClassName' => 'PHPStan\\Analyser\\TypeSpecifierTest',
        'implementingClassName' => 'PHPStan\\Analyser\\TypeSpecifierTest',
        'name' => 'typeSpecifier',
        'modifiers' => 4,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPStan\\Analyser\\TypeSpecifier',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 57,
        'endLine' => 57,
        'startColumn' => 2,
        'endColumn' => 38,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'scope' => 
      array (
        'declaringClassName' => 'PHPStan\\Analyser\\TypeSpecifierTest',
        'implementingClassName' => 'PHPStan\\Analyser\\TypeSpecifierTest',
        'name' => 'scope',
        'modifiers' => 4,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPStan\\Analyser\\Scope',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 59,
        'endLine' => 59,
        'startColumn' => 2,
        'endColumn' => 22,
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
      'setUp' => 
      array (
        'name' => 'setUp',
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
            'name' => 'Override',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => NULL,
        'startLine' => 61,
        'endLine' => 83,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'PHPStan\\Analyser',
        'declaringClassName' => 'PHPStan\\Analyser\\TypeSpecifierTest',
        'implementingClassName' => 'PHPStan\\Analyser\\TypeSpecifierTest',
        'currentClassName' => 'PHPStan\\Analyser\\TypeSpecifierTest',
        'aliasName' => NULL,
      ),
      'testCondition' => 
      array (
        'name' => 'testCondition',
        'parameters' => 
        array (
          'expr' => 
          array (
            'name' => 'expr',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PhpParser\\Node\\Expr',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 90,
            'endLine' => 90,
            'startColumn' => 32,
            'endColumn' => 41,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'expectedPositiveResult' => 
          array (
            'name' => 'expectedPositiveResult',
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
            'startLine' => 90,
            'endLine' => 90,
            'startColumn' => 44,
            'endColumn' => 72,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'expectedNegatedResult' => 
          array (
            'name' => 'expectedNegatedResult',
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
            'startLine' => 90,
            'endLine' => 90,
            'startColumn' => 75,
            'endColumn' => 102,
            'parameterIndex' => 2,
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
                'code' => '\'dataCondition\'',
                'attributes' => 
                array (
                  'startLine' => 89,
                  'endLine' => 89,
                  'startTokenPos' => 1147,
                  'startFilePos' => 4696,
                  'endTokenPos' => 1147,
                  'endFilePos' => 4710,
                ),
              ),
            ),
          ),
        ),
        'docComment' => '/**
 * @param mixed[] $expectedPositiveResult
 * @param mixed[] $expectedNegatedResult
 */',
        'startLine' => 89,
        'endLine' => 99,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Analyser',
        'declaringClassName' => 'PHPStan\\Analyser\\TypeSpecifierTest',
        'implementingClassName' => 'PHPStan\\Analyser\\TypeSpecifierTest',
        'currentClassName' => 'PHPStan\\Analyser\\TypeSpecifierTest',
        'aliasName' => NULL,
      ),
      'dataCondition' => 
      array (
        'name' => 'dataCondition',
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
        'startLine' => 101,
        'endLine' => 1324,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => true,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'PHPStan\\Analyser',
        'declaringClassName' => 'PHPStan\\Analyser\\TypeSpecifierTest',
        'implementingClassName' => 'PHPStan\\Analyser\\TypeSpecifierTest',
        'currentClassName' => 'PHPStan\\Analyser\\TypeSpecifierTest',
        'aliasName' => NULL,
      ),
      'toReadableResult' => 
      array (
        'name' => 'toReadableResult',
        'parameters' => 
        array (
          'specifiedTypes' => 
          array (
            'name' => 'specifiedTypes',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\Analyser\\SpecifiedTypes',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1329,
            'endLine' => 1329,
            'startColumn' => 36,
            'endColumn' => 65,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @return mixed[]
 */',
        'startLine' => 1329,
        'endLine' => 1347,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'PHPStan\\Analyser',
        'declaringClassName' => 'PHPStan\\Analyser\\TypeSpecifierTest',
        'implementingClassName' => 'PHPStan\\Analyser\\TypeSpecifierTest',
        'currentClassName' => 'PHPStan\\Analyser\\TypeSpecifierTest',
        'aliasName' => NULL,
      ),
      'createInstanceOf' => 
      array (
        'name' => 'createInstanceOf',
        'parameters' => 
        array (
          'className' => 
          array (
            'name' => 'className',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'string',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1352,
            'endLine' => 1352,
            'startColumn' => 43,
            'endColumn' => 59,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'variableName' => 
          array (
            'name' => 'variableName',
            'default' => 
            array (
              'code' => '\'foo\'',
              'attributes' => 
              array (
                'startLine' => 1352,
                'endLine' => 1352,
                'startTokenPos' => 9132,
                'startFilePos' => 33799,
                'endTokenPos' => 9132,
                'endFilePos' => 33803,
              ),
            ),
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'string',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1352,
            'endLine' => 1352,
            'startColumn' => 62,
            'endColumn' => 89,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PhpParser\\Node\\Expr\\Instanceof_',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @param non-empty-string $className
 */',
        'startLine' => 1352,
        'endLine' => 1355,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 20,
        'namespace' => 'PHPStan\\Analyser',
        'declaringClassName' => 'PHPStan\\Analyser\\TypeSpecifierTest',
        'implementingClassName' => 'PHPStan\\Analyser\\TypeSpecifierTest',
        'currentClassName' => 'PHPStan\\Analyser\\TypeSpecifierTest',
        'aliasName' => NULL,
      ),
      'createFunctionCall' => 
      array (
        'name' => 'createFunctionCall',
        'parameters' => 
        array (
          'functionName' => 
          array (
            'name' => 'functionName',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'string',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1360,
            'endLine' => 1360,
            'startColumn' => 45,
            'endColumn' => 64,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'variableName' => 
          array (
            'name' => 'variableName',
            'default' => 
            array (
              'code' => '\'foo\'',
              'attributes' => 
              array (
                'startLine' => 1360,
                'endLine' => 1360,
                'startTokenPos' => 9186,
                'startFilePos' => 34054,
                'endTokenPos' => 9186,
                'endFilePos' => 34058,
              ),
            ),
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'string',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 1360,
            'endLine' => 1360,
            'startColumn' => 67,
            'endColumn' => 94,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PhpParser\\Node\\Expr\\FuncCall',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @param non-empty-string $functionName
 */',
        'startLine' => 1360,
        'endLine' => 1363,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 20,
        'namespace' => 'PHPStan\\Analyser',
        'declaringClassName' => 'PHPStan\\Analyser\\TypeSpecifierTest',
        'implementingClassName' => 'PHPStan\\Analyser\\TypeSpecifierTest',
        'currentClassName' => 'PHPStan\\Analyser\\TypeSpecifierTest',
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