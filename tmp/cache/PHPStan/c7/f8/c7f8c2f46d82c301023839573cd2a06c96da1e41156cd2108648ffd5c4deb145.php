<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/src/Rules/Functions/PrintfParametersRule.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\Rules\Functions\PrintfParametersRule
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-bf862406eb02cd42d311223fe488eae7c19df96be2cb1f41d2d66f1f2e6f990e',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\Rules\\Functions\\PrintfParametersRule',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/src/Rules/Functions/PrintfParametersRule.php',
      ),
    ),
    'namespace' => 'PHPStan\\Rules\\Functions',
    'name' => 'PHPStan\\Rules\\Functions\\PrintfParametersRule',
    'shortName' => 'PrintfParametersRule',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * @implements Rule<Node\\Expr\\FuncCall>
 */',
    'attributes' => 
    array (
      0 => 
      array (
        'name' => 'PHPStan\\DependencyInjection\\RegisteredRule',
        'isRepeated' => false,
        'arguments' => 
        array (
          'level' => 
          array (
            'code' => '0',
            'attributes' => 
            array (
              'startLine' => 20,
              'endLine' => 20,
              'startTokenPos' => 87,
              'startFilePos' => 472,
              'endTokenPos' => 87,
              'endFilePos' => 472,
            ),
          ),
        ),
      ),
    ),
    'startLine' => 20,
    'endLine' => 129,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
      0 => 'PHPStan\\Rules\\Rule',
    ),
    'traitClassNames' => 
    array (
    ),
    'immediateConstants' => 
    array (
      'FORMAT_ARGUMENT_POSITIONS' => 
      array (
        'declaringClassName' => 'PHPStan\\Rules\\Functions\\PrintfParametersRule',
        'implementingClassName' => 'PHPStan\\Rules\\Functions\\PrintfParametersRule',
        'name' => 'FORMAT_ARGUMENT_POSITIONS',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'printf\' => 0, \'sprintf\' => 0, \'sscanf\' => 1, \'fscanf\' => 1]',
          'attributes' => 
          array (
            'startLine' => 24,
            'endLine' => 29,
            'startTokenPos' => 111,
            'startFilePos' => 571,
            'endTokenPos' => 141,
            'endFilePos' => 643,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 24,
        'endLine' => 29,
        'startColumn' => 2,
        'endColumn' => 3,
      ),
      'MINIMUM_NUMBER_OF_ARGUMENTS' => 
      array (
        'declaringClassName' => 'PHPStan\\Rules\\Functions\\PrintfParametersRule',
        'implementingClassName' => 'PHPStan\\Rules\\Functions\\PrintfParametersRule',
        'name' => 'MINIMUM_NUMBER_OF_ARGUMENTS',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'printf\' => 1, \'sprintf\' => 1, \'sscanf\' => 3, \'fscanf\' => 3]',
          'attributes' => 
          array (
            'startLine' => 30,
            'endLine' => 35,
            'startTokenPos' => 152,
            'startFilePos' => 691,
            'endTokenPos' => 182,
            'endFilePos' => 763,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 30,
        'endLine' => 35,
        'startColumn' => 2,
        'endColumn' => 3,
      ),
    ),
    'immediateProperties' => 
    array (
      'printfHelper' => 
      array (
        'declaringClassName' => 'PHPStan\\Rules\\Functions\\PrintfParametersRule',
        'implementingClassName' => 'PHPStan\\Rules\\Functions\\PrintfParametersRule',
        'name' => 'printfHelper',
        'modifiers' => 4,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPStan\\Rules\\Functions\\PrintfHelper',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 38,
        'endLine' => 38,
        'startColumn' => 3,
        'endColumn' => 36,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'reflectionProvider' => 
      array (
        'declaringClassName' => 'PHPStan\\Rules\\Functions\\PrintfParametersRule',
        'implementingClassName' => 'PHPStan\\Rules\\Functions\\PrintfParametersRule',
        'name' => 'reflectionProvider',
        'modifiers' => 4,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPStan\\Reflection\\ReflectionProvider',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 39,
        'endLine' => 39,
        'startColumn' => 3,
        'endColumn' => 48,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
    ),
    'immediateMethods' => 
    array (
      '__construct' => 
      array (
        'name' => '__construct',
        'parameters' => 
        array (
          'printfHelper' => 
          array (
            'name' => 'printfHelper',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\Rules\\Functions\\PrintfHelper',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 38,
            'endLine' => 38,
            'startColumn' => 3,
            'endColumn' => 36,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'reflectionProvider' => 
          array (
            'name' => 'reflectionProvider',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\Reflection\\ReflectionProvider',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 39,
            'endLine' => 39,
            'startColumn' => 3,
            'endColumn' => 48,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 37,
        'endLine' => 42,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Rules\\Functions',
        'declaringClassName' => 'PHPStan\\Rules\\Functions\\PrintfParametersRule',
        'implementingClassName' => 'PHPStan\\Rules\\Functions\\PrintfParametersRule',
        'currentClassName' => 'PHPStan\\Rules\\Functions\\PrintfParametersRule',
        'aliasName' => NULL,
      ),
      'getNodeType' => 
      array (
        'name' => 'getNodeType',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 44,
        'endLine' => 47,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Rules\\Functions',
        'declaringClassName' => 'PHPStan\\Rules\\Functions\\PrintfParametersRule',
        'implementingClassName' => 'PHPStan\\Rules\\Functions\\PrintfParametersRule',
        'currentClassName' => 'PHPStan\\Rules\\Functions\\PrintfParametersRule',
        'aliasName' => NULL,
      ),
      'processNode' => 
      array (
        'name' => 'processNode',
        'parameters' => 
        array (
          'node' => 
          array (
            'name' => 'node',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PhpParser\\Node',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 49,
            'endLine' => 49,
            'startColumn' => 30,
            'endColumn' => 39,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'scope' => 
          array (
            'name' => 'scope',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\Analyser\\Scope',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 49,
            'endLine' => 49,
            'startColumn' => 42,
            'endColumn' => 53,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 49,
        'endLine' => 127,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Rules\\Functions',
        'declaringClassName' => 'PHPStan\\Rules\\Functions\\PrintfParametersRule',
        'implementingClassName' => 'PHPStan\\Rules\\Functions\\PrintfParametersRule',
        'currentClassName' => 'PHPStan\\Rules\\Functions\\PrintfParametersRule',
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