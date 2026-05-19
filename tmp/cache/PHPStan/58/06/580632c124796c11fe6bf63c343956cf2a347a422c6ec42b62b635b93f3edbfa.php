<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/src/Process/ProcessPromise.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\Process\ProcessPromise
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-d408a189592f4e2e4d47da66ce8ef1873172fe54df5163acbacae29742097dae',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\Process\\ProcessPromise',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/src/Process/ProcessPromise.php',
      ),
    ),
    'namespace' => 'PHPStan\\Process',
    'name' => 'PHPStan\\Process\\ProcessPromise',
    'shortName' => 'ProcessPromise',
    'isInterface' => true,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * A PHPStan Pro analysis worker as seen by FixerApplication.
 *
 * Implementations differ only in how the worker process comes to life:
 * SpawnedProcessPromise spawns a fresh PHP process via react/child-process,
 * ForkedProcessPromise forks the already-booted main process via pcntl_fork().
 * Both yield a promise that resolves on success and rejects with
 * ProcessCrashedException / ProcessCanceledException otherwise.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 16,
    'endLine' => 24,
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
    ),
    'immediateMethods' => 
    array (
      'run' => 
      array (
        'name' => 'run',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'React\\Promise\\PromiseInterface',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @return PromiseInterface<string>
 */',
        'startLine' => 22,
        'endLine' => 22,
        'startColumn' => 2,
        'endColumn' => 41,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Process',
        'declaringClassName' => 'PHPStan\\Process\\ProcessPromise',
        'implementingClassName' => 'PHPStan\\Process\\ProcessPromise',
        'currentClassName' => 'PHPStan\\Process\\ProcessPromise',
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