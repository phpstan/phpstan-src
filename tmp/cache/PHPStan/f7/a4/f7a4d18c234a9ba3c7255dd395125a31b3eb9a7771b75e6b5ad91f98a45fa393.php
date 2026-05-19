<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/src/Parallel/ForkParallelChecker.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\Parallel\ForkParallelChecker
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-5d3a580644e6252790b844725f56941b3bb542739b9bf07b0e4e9a64cf5a8b12',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\Parallel\\ForkParallelChecker',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/src/Parallel/ForkParallelChecker.php',
      ),
    ),
    'namespace' => 'PHPStan\\Parallel',
    'name' => 'PHPStan\\Parallel\\ForkParallelChecker',
    'shortName' => 'ForkParallelChecker',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Decides whether parallel analysis should fork workers via pcntl_fork()
 * (see ForkedProcess) instead of spawning fresh PHP processes (see SpawnedProcess).
 *
 * Experimental and opt-in: enabled only when PHPSTAN_PARALLEL_FORK=1 is set,
 * the pcntl/posix functions exist, and OPcache + JIT are both off — their
 * shared memory is not safe to populate concurrently from forked children and
 * doing so corrupts analysis results.
 */',
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
    'startLine' => 22,
    'endLine' => 90,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
      0 => 'PHPStan\\Diagnose\\DiagnoseExtension',
    ),
    'traitClassNames' => 
    array (
    ),
    'immediateConstants' => 
    array (
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'isSupported' => 
      array (
        'name' => 'isSupported',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 26,
        'endLine' => 29,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Parallel',
        'declaringClassName' => 'PHPStan\\Parallel\\ForkParallelChecker',
        'implementingClassName' => 'PHPStan\\Parallel\\ForkParallelChecker',
        'currentClassName' => 'PHPStan\\Parallel\\ForkParallelChecker',
        'aliasName' => NULL,
      ),
      'print' => 
      array (
        'name' => 'print',
        'parameters' => 
        array (
          'output' => 
          array (
            'name' => 'output',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\Command\\Output',
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
            'startColumn' => 24,
            'endColumn' => 37,
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
        ),
        'docComment' => NULL,
        'startLine' => 31,
        'endLine' => 47,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Parallel',
        'declaringClassName' => 'PHPStan\\Parallel\\ForkParallelChecker',
        'implementingClassName' => 'PHPStan\\Parallel\\ForkParallelChecker',
        'currentClassName' => 'PHPStan\\Parallel\\ForkParallelChecker',
        'aliasName' => NULL,
      ),
      'getDisabledReason' => 
      array (
        'name' => 'getDisabledReason',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
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
                  'name' => 'string',
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
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 49,
        'endLine' => 70,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'PHPStan\\Parallel',
        'declaringClassName' => 'PHPStan\\Parallel\\ForkParallelChecker',
        'implementingClassName' => 'PHPStan\\Parallel\\ForkParallelChecker',
        'currentClassName' => 'PHPStan\\Parallel\\ForkParallelChecker',
        'aliasName' => NULL,
      ),
      'isOpcacheOrJitEnabled' => 
      array (
        'name' => 'isOpcacheOrJitEnabled',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 72,
        'endLine' => 88,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'PHPStan\\Parallel',
        'declaringClassName' => 'PHPStan\\Parallel\\ForkParallelChecker',
        'implementingClassName' => 'PHPStan\\Parallel\\ForkParallelChecker',
        'currentClassName' => 'PHPStan\\Parallel\\ForkParallelChecker',
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