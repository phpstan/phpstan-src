<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/src/Rules/Types/InvalidTypesInUnionRule.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\Rules\Types\InvalidTypesInUnionRule
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-402ad8704a59a542879c5bb2e34df345045792f91456c904b99314421416da37',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\Rules\\Types\\InvalidTypesInUnionRule',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/src/Rules/Types/InvalidTypesInUnionRule.php',
      ),
    ),
    'namespace' => 'PHPStan\\Rules\\Types',
    'name' => 'PHPStan\\Rules\\Types\\InvalidTypesInUnionRule',
    'shortName' => 'InvalidTypesInUnionRule',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * @implements Rule<Node>
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
              'startLine' => 19,
              'endLine' => 19,
              'startTokenPos' => 80,
              'startFilePos' => 427,
              'endTokenPos' => 80,
              'endFilePos' => 427,
            ),
          ),
        ),
      ),
    ),
    'startLine' => 19,
    'endLine' => 127,
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
      'ONLY_STANDALONE_TYPES' => 
      array (
        'declaringClassName' => 'PHPStan\\Rules\\Types\\InvalidTypesInUnionRule',
        'implementingClassName' => 'PHPStan\\Rules\\Types\\InvalidTypesInUnionRule',
        'name' => 'ONLY_STANDALONE_TYPES',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'mixed\', \'never\', \'void\']',
          'attributes' => 
          array (
            'startLine' => 23,
            'endLine' => 27,
            'startTokenPos' => 104,
            'startFilePos' => 525,
            'endTokenPos' => 115,
            'endFilePos' => 560,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 23,
        'endLine' => 27,
        'startColumn' => 2,
        'endColumn' => 3,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
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
        'startLine' => 29,
        'endLine' => 32,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Rules\\Types',
        'declaringClassName' => 'PHPStan\\Rules\\Types\\InvalidTypesInUnionRule',
        'implementingClassName' => 'PHPStan\\Rules\\Types\\InvalidTypesInUnionRule',
        'currentClassName' => 'PHPStan\\Rules\\Types\\InvalidTypesInUnionRule',
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
            'startLine' => 34,
            'endLine' => 34,
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
            'startLine' => 34,
            'endLine' => 34,
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
        'startLine' => 34,
        'endLine' => 45,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Rules\\Types',
        'declaringClassName' => 'PHPStan\\Rules\\Types\\InvalidTypesInUnionRule',
        'implementingClassName' => 'PHPStan\\Rules\\Types\\InvalidTypesInUnionRule',
        'currentClassName' => 'PHPStan\\Rules\\Types\\InvalidTypesInUnionRule',
        'aliasName' => NULL,
      ),
      'processFunctionLikeNode' => 
      array (
        'name' => 'processFunctionLikeNode',
        'parameters' => 
        array (
          'functionLike' => 
          array (
            'name' => 'functionLike',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PhpParser\\Node\\FunctionLike',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 50,
            'endLine' => 50,
            'startColumn' => 43,
            'endColumn' => 73,
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
 * @return list<IdentifierRuleError>
 */',
        'startLine' => 50,
        'endLine' => 67,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'PHPStan\\Rules\\Types',
        'declaringClassName' => 'PHPStan\\Rules\\Types\\InvalidTypesInUnionRule',
        'implementingClassName' => 'PHPStan\\Rules\\Types\\InvalidTypesInUnionRule',
        'currentClassName' => 'PHPStan\\Rules\\Types\\InvalidTypesInUnionRule',
        'aliasName' => NULL,
      ),
      'processClassPropertyNode' => 
      array (
        'name' => 'processClassPropertyNode',
        'parameters' => 
        array (
          'classPropertyNode' => 
          array (
            'name' => 'classPropertyNode',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\Node\\ClassPropertyNode',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 72,
            'endLine' => 72,
            'startColumn' => 44,
            'endColumn' => 79,
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
 * @return list<IdentifierRuleError>
 */',
        'startLine' => 72,
        'endLine' => 79,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'PHPStan\\Rules\\Types',
        'declaringClassName' => 'PHPStan\\Rules\\Types\\InvalidTypesInUnionRule',
        'implementingClassName' => 'PHPStan\\Rules\\Types\\InvalidTypesInUnionRule',
        'currentClassName' => 'PHPStan\\Rules\\Types\\InvalidTypesInUnionRule',
        'aliasName' => NULL,
      ),
      'processComplexType' => 
      array (
        'name' => 'processComplexType',
        'parameters' => 
        array (
          'complexType' => 
          array (
            'name' => 'complexType',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PhpParser\\Node\\ComplexType',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 84,
            'endLine' => 84,
            'startColumn' => 38,
            'endColumn' => 66,
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
 * @return list<IdentifierRuleError>
 */',
        'startLine' => 84,
        'endLine' => 125,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'PHPStan\\Rules\\Types',
        'declaringClassName' => 'PHPStan\\Rules\\Types\\InvalidTypesInUnionRule',
        'implementingClassName' => 'PHPStan\\Rules\\Types\\InvalidTypesInUnionRule',
        'currentClassName' => 'PHPStan\\Rules\\Types\\InvalidTypesInUnionRule',
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