<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/src/Rules/ClassForbiddenNameCheck.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\Rules\ClassForbiddenNameCheck
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-514fb74d07c0a39103515b59ac0ac8951a030ab769f61a4d23768e0ff9598acc',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\Rules\\ClassForbiddenNameCheck',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/src/Rules/ClassForbiddenNameCheck.php',
      ),
    ),
    'namespace' => 'PHPStan\\Rules',
    'name' => 'PHPStan\\Rules\\ClassForbiddenNameCheck',
    'shortName' => 'ClassForbiddenNameCheck',
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
    'startLine' => 16,
    'endLine' => 96,
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
      'INTERNAL_CLASS_PREFIXES' => 
      array (
        'declaringClassName' => 'PHPStan\\Rules\\ClassForbiddenNameCheck',
        'implementingClassName' => 'PHPStan\\Rules\\ClassForbiddenNameCheck',
        'name' => 'INTERNAL_CLASS_PREFIXES',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'PHPStan\' => \'_PHPStan_\', \'Rector\' => \'RectorPrefix\', \'PHP-Scoper\' => \'_PhpScoper\', \'PHPUnit\' => \'PHPUnitPHAR\', \'Box\' => \'_HumbugBox\']',
          'attributes' => 
          array (
            'startLine' => 20,
            'endLine' => 26,
            'startTokenPos' => 100,
            'startFilePos' => 468,
            'endTokenPos' => 137,
            'endFilePos' => 616,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 20,
        'endLine' => 26,
        'startColumn' => 2,
        'endColumn' => 3,
      ),
    ),
    'immediateProperties' => 
    array (
      'container' => 
      array (
        'declaringClassName' => 'PHPStan\\Rules\\ClassForbiddenNameCheck',
        'implementingClassName' => 'PHPStan\\Rules\\ClassForbiddenNameCheck',
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
        'startLine' => 28,
        'endLine' => 28,
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
            'startLine' => 28,
            'endLine' => 28,
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
        'startLine' => 28,
        'endLine' => 30,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Rules',
        'declaringClassName' => 'PHPStan\\Rules\\ClassForbiddenNameCheck',
        'implementingClassName' => 'PHPStan\\Rules\\ClassForbiddenNameCheck',
        'currentClassName' => 'PHPStan\\Rules\\ClassForbiddenNameCheck',
        'aliasName' => NULL,
      ),
      'checkClassNames' => 
      array (
        'name' => 'checkClassNames',
        'parameters' => 
        array (
          'pairs' => 
          array (
            'name' => 'pairs',
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
            'startLine' => 36,
            'endLine' => 36,
            'startColumn' => 34,
            'endColumn' => 45,
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
 * @param ClassNameNodePair[] $pairs
 * @return list<IdentifierRuleError>
 */',
        'startLine' => 36,
        'endLine' => 94,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Rules',
        'declaringClassName' => 'PHPStan\\Rules\\ClassForbiddenNameCheck',
        'implementingClassName' => 'PHPStan\\Rules\\ClassForbiddenNameCheck',
        'currentClassName' => 'PHPStan\\Rules\\ClassForbiddenNameCheck',
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