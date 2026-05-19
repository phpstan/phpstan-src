<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/src/Rules/LazyRegistry.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\Rules\LazyRegistry
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-4fc530d4b4a09ebda9bcb5973957afc065deb5805ed9f3f7ba42ba19c356d227',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\Rules\\LazyRegistry',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/src/Rules/LazyRegistry.php',
      ),
    ),
    'namespace' => 'PHPStan\\Rules',
    'name' => 'PHPStan\\Rules\\LazyRegistry',
    'shortName' => 'LazyRegistry',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => NULL,
    'attributes' => 
    array (
      0 => 
      array (
        'name' => 'PHPStan\\DependencyInjection\\AutowiredService',
        'isRepeated' => false,
        'arguments' => 
        array (
          'name' => 
          array (
            'code' => '\'registry\'',
            'attributes' => 
            array (
              'startLine' => 11,
              'endLine' => 11,
              'startTokenPos' => 51,
              'startFilePos' => 258,
              'endTokenPos' => 51,
              'endFilePos' => 267,
            ),
          ),
          'as' => 
          array (
            'code' => '\\PHPStan\\Rules\\Registry::class',
            'attributes' => 
            array (
              'startLine' => 11,
              'endLine' => 11,
              'startTokenPos' => 57,
              'startFilePos' => 274,
              'endTokenPos' => 59,
              'endFilePos' => 288,
            ),
          ),
        ),
      ),
    ),
    'startLine' => 11,
    'endLine' => 73,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
      0 => 'PHPStan\\Rules\\Registry',
    ),
    'traitClassNames' => 
    array (
    ),
    'immediateConstants' => 
    array (
      'RULE_TAG' => 
      array (
        'declaringClassName' => 'PHPStan\\Rules\\LazyRegistry',
        'implementingClassName' => 'PHPStan\\Rules\\LazyRegistry',
        'name' => 'RULE_TAG',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'phpstan.rules.rule\'',
          'attributes' => 
          array (
            'startLine' => 15,
            'endLine' => 15,
            'startTokenPos' => 83,
            'startFilePos' => 365,
            'endTokenPos' => 83,
            'endFilePos' => 384,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 15,
        'endLine' => 15,
        'startColumn' => 2,
        'endColumn' => 46,
      ),
    ),
    'immediateProperties' => 
    array (
      'rules' => 
      array (
        'declaringClassName' => 'PHPStan\\Rules\\LazyRegistry',
        'implementingClassName' => 'PHPStan\\Rules\\LazyRegistry',
        'name' => 'rules',
        'modifiers' => 4,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionUnionType',
          'data' => 
          array (
            'types' => 
            array (
              0 => 
              array (
                'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                'data' => 
                array (
                  'name' => 'array',
                  'isIdentifier' => true,
                ),
              ),
              1 => 
              array (
                'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                'data' => 
                array (
                  'name' => 'null',
                  'isIdentifier' => true,
                ),
              ),
            ),
          ),
        ),
        'default' => 
        array (
          'code' => 'null',
          'attributes' => 
          array (
            'startLine' => 18,
            'endLine' => 18,
            'startTokenPos' => 97,
            'startFilePos' => 440,
            'endTokenPos' => 97,
            'endFilePos' => 443,
          ),
        ),
        'docComment' => '/** @var Rule[][]|null */',
        'attributes' => 
        array (
        ),
        'startLine' => 18,
        'endLine' => 18,
        'startColumn' => 2,
        'endColumn' => 30,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'cache' => 
      array (
        'declaringClassName' => 'PHPStan\\Rules\\LazyRegistry',
        'implementingClassName' => 'PHPStan\\Rules\\LazyRegistry',
        'name' => 'cache',
        'modifiers' => 4,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'default' => 
        array (
          'code' => '[]',
          'attributes' => 
          array (
            'startLine' => 21,
            'endLine' => 21,
            'startTokenPos' => 110,
            'startFilePos' => 493,
            'endTokenPos' => 111,
            'endFilePos' => 494,
          ),
        ),
        'docComment' => '/** @var Rule[][] */',
        'attributes' => 
        array (
        ),
        'startLine' => 21,
        'endLine' => 21,
        'startColumn' => 2,
        'endColumn' => 27,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'container' => 
      array (
        'declaringClassName' => 'PHPStan\\Rules\\LazyRegistry',
        'implementingClassName' => 'PHPStan\\Rules\\LazyRegistry',
        'name' => 'container',
        'modifiers' => 4,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPStan\\DependencyInjection\\Container',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 23,
        'endLine' => 23,
        'startColumn' => 30,
        'endColumn' => 57,
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
          'container' => 
          array (
            'name' => 'container',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\DependencyInjection\\Container',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 23,
            'endLine' => 23,
            'startColumn' => 30,
            'endColumn' => 57,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 23,
        'endLine' => 25,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Rules',
        'declaringClassName' => 'PHPStan\\Rules\\LazyRegistry',
        'implementingClassName' => 'PHPStan\\Rules\\LazyRegistry',
        'currentClassName' => 'PHPStan\\Rules\\LazyRegistry',
        'aliasName' => NULL,
      ),
      'getRules' => 
      array (
        'name' => 'getRules',
        'parameters' => 
        array (
          'nodeType' => 
          array (
            'name' => 'nodeType',
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
            'startLine' => 32,
            'endLine' => 32,
            'startColumn' => 27,
            'endColumn' => 42,
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
 * @template TNodeType of Node
 * @param class-string<TNodeType> $nodeType
 * @return array<Rule<TNodeType>>
 */',
        'startLine' => 32,
        'endLine' => 54,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Rules',
        'declaringClassName' => 'PHPStan\\Rules\\LazyRegistry',
        'implementingClassName' => 'PHPStan\\Rules\\LazyRegistry',
        'currentClassName' => 'PHPStan\\Rules\\LazyRegistry',
        'aliasName' => NULL,
      ),
      'getRulesFromContainer' => 
      array (
        'name' => 'getRulesFromContainer',
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
        'docComment' => '/**
 * @return Rule[][]
 */',
        'startLine' => 59,
        'endLine' => 71,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'PHPStan\\Rules',
        'declaringClassName' => 'PHPStan\\Rules\\LazyRegistry',
        'implementingClassName' => 'PHPStan\\Rules\\LazyRegistry',
        'currentClassName' => 'PHPStan\\Rules\\LazyRegistry',
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