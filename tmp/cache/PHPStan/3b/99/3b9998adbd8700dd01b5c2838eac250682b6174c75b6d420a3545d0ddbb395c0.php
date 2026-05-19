<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/src/Rules/Comparison/PossiblyImpureTipHelper.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\Rules\Comparison\PossiblyImpureTipHelper
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-aa1301011f359c75cb0d555cff891d1ba98257a5517e00645c84b84732b132d2',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\Rules\\Comparison\\PossiblyImpureTipHelper',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/src/Rules/Comparison/PossiblyImpureTipHelper.php',
      ),
    ),
    'namespace' => 'PHPStan\\Rules\\Comparison',
    'name' => 'PHPStan\\Rules\\Comparison\\PossiblyImpureTipHelper',
    'shortName' => 'PossiblyImpureTipHelper',
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
        ),
      ),
    ),
    'startLine' => 14,
    'endLine' => 50,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
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
      'possiblyImpureTip' => 
      array (
        'declaringClassName' => 'PHPStan\\Rules\\Comparison\\PossiblyImpureTipHelper',
        'implementingClassName' => 'PHPStan\\Rules\\Comparison\\PossiblyImpureTipHelper',
        'name' => 'possiblyImpureTip',
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
          0 => 
          array (
            'name' => 'PHPStan\\DependencyInjection\\AutowiredParameter',
            'isRepeated' => false,
            'arguments' => 
            array (
              'ref' => 
              array (
                'code' => '\'%tips.possiblyImpure%\'',
                'attributes' => 
                array (
                  'startLine' => 19,
                  'endLine' => 19,
                  'startTokenPos' => 83,
                  'startFilePos' => 465,
                  'endTokenPos' => 83,
                  'endFilePos' => 487,
                ),
              ),
            ),
          ),
        ),
        'startLine' => 19,
        'endLine' => 20,
        'startColumn' => 3,
        'endColumn' => 33,
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
          'possiblyImpureTip' => 
          array (
            'name' => 'possiblyImpureTip',
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
            'isPromoted' => true,
            'attributes' => 
            array (
              0 => 
              array (
                'name' => 'PHPStan\\DependencyInjection\\AutowiredParameter',
                'isRepeated' => false,
                'arguments' => 
                array (
                  'ref' => 
                  array (
                    'code' => '\'%tips.possiblyImpure%\'',
                    'attributes' => 
                    array (
                      'startLine' => 19,
                      'endLine' => 19,
                      'startTokenPos' => 83,
                      'startFilePos' => 465,
                      'endTokenPos' => 83,
                      'endFilePos' => 487,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 19,
            'endLine' => 20,
            'startColumn' => 3,
            'endColumn' => 33,
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
        'startLine' => 18,
        'endLine' => 23,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Rules\\Comparison',
        'declaringClassName' => 'PHPStan\\Rules\\Comparison\\PossiblyImpureTipHelper',
        'implementingClassName' => 'PHPStan\\Rules\\Comparison\\PossiblyImpureTipHelper',
        'currentClassName' => 'PHPStan\\Rules\\Comparison\\PossiblyImpureTipHelper',
        'aliasName' => NULL,
      ),
      'addTip' => 
      array (
        'name' => 'addTip',
        'parameters' => 
        array (
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
            'startLine' => 31,
            'endLine' => 31,
            'startColumn' => 3,
            'endColumn' => 14,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'conditionExpr' => 
          array (
            'name' => 'conditionExpr',
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
            'startLine' => 32,
            'endLine' => 32,
            'startColumn' => 3,
            'endColumn' => 21,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'ruleErrorBuilder' => 
          array (
            'name' => 'ruleErrorBuilder',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\Rules\\RuleErrorBuilder',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 33,
            'endLine' => 33,
            'startColumn' => 3,
            'endColumn' => 36,
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
            'name' => 'PHPStan\\Rules\\RuleErrorBuilder',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @template T of RuleError
 * @param RuleErrorBuilder<T> $ruleErrorBuilder
 * @return RuleErrorBuilder<T>
 */',
        'startLine' => 30,
        'endLine' => 48,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Rules\\Comparison',
        'declaringClassName' => 'PHPStan\\Rules\\Comparison\\PossiblyImpureTipHelper',
        'implementingClassName' => 'PHPStan\\Rules\\Comparison\\PossiblyImpureTipHelper',
        'currentClassName' => 'PHPStan\\Rules\\Comparison\\PossiblyImpureTipHelper',
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