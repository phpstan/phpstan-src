<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/src/Parallel/Scheduler.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\Parallel\Scheduler
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-e5f4572856600816ba5bbf6738f4bb8644b09d23eef8894460e0b7f59d3ffda6',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\Parallel\\Scheduler',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/src/Parallel/Scheduler.php',
      ),
    ),
    'namespace' => 'PHPStan\\Parallel',
    'name' => 'PHPStan\\Parallel\\Scheduler',
    'shortName' => 'Scheduler',
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
    'endLine' => 75,
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
      'storedData' => 
      array (
        'declaringClassName' => 'PHPStan\\Parallel\\Scheduler',
        'implementingClassName' => 'PHPStan\\Parallel\\Scheduler',
        'name' => 'storedData',
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
            'startLine' => 21,
            'endLine' => 21,
            'startTokenPos' => 105,
            'startFilePos' => 506,
            'endTokenPos' => 105,
            'endFilePos' => 509,
          ),
        ),
        'docComment' => '/** @var array{int, int, int, int}|null */',
        'attributes' => 
        array (
        ),
        'startLine' => 21,
        'endLine' => 21,
        'startColumn' => 2,
        'endColumn' => 35,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'jobSize' => 
      array (
        'declaringClassName' => 'PHPStan\\Parallel\\Scheduler',
        'implementingClassName' => 'PHPStan\\Parallel\\Scheduler',
        'name' => 'jobSize',
        'modifiers' => 4,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'int',
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
                'code' => '\'%parallel.jobSize%\'',
                'attributes' => 
                array (
                  'startLine' => 29,
                  'endLine' => 29,
                  'startTokenPos' => 123,
                  'startFilePos' => 719,
                  'endTokenPos' => 123,
                  'endFilePos' => 738,
                ),
              ),
            ),
          ),
        ),
        'startLine' => 29,
        'endLine' => 30,
        'startColumn' => 3,
        'endColumn' => 22,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'maximumNumberOfProcesses' => 
      array (
        'declaringClassName' => 'PHPStan\\Parallel\\Scheduler',
        'implementingClassName' => 'PHPStan\\Parallel\\Scheduler',
        'name' => 'maximumNumberOfProcesses',
        'modifiers' => 4,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'int',
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
                'code' => '\'%parallel.maximumNumberOfProcesses%\'',
                'attributes' => 
                array (
                  'startLine' => 31,
                  'endLine' => 31,
                  'startTokenPos' => 140,
                  'startFilePos' => 794,
                  'endTokenPos' => 140,
                  'endFilePos' => 830,
                ),
              ),
            ),
          ),
        ),
        'startLine' => 31,
        'endLine' => 32,
        'startColumn' => 3,
        'endColumn' => 39,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'minimumNumberOfJobsPerProcess' => 
      array (
        'declaringClassName' => 'PHPStan\\Parallel\\Scheduler',
        'implementingClassName' => 'PHPStan\\Parallel\\Scheduler',
        'name' => 'minimumNumberOfJobsPerProcess',
        'modifiers' => 4,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'int',
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
                'code' => '\'%parallel.minimumNumberOfJobsPerProcess%\'',
                'attributes' => 
                array (
                  'startLine' => 33,
                  'endLine' => 33,
                  'startTokenPos' => 157,
                  'startFilePos' => 903,
                  'endTokenPos' => 157,
                  'endFilePos' => 944,
                ),
              ),
            ),
          ),
        ),
        'startLine' => 33,
        'endLine' => 34,
        'startColumn' => 3,
        'endColumn' => 44,
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
          'jobSize' => 
          array (
            'name' => 'jobSize',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'int',
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
                    'code' => '\'%parallel.jobSize%\'',
                    'attributes' => 
                    array (
                      'startLine' => 29,
                      'endLine' => 29,
                      'startTokenPos' => 123,
                      'startFilePos' => 719,
                      'endTokenPos' => 123,
                      'endFilePos' => 738,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 29,
            'endLine' => 30,
            'startColumn' => 3,
            'endColumn' => 22,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'maximumNumberOfProcesses' => 
          array (
            'name' => 'maximumNumberOfProcesses',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'int',
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
                    'code' => '\'%parallel.maximumNumberOfProcesses%\'',
                    'attributes' => 
                    array (
                      'startLine' => 31,
                      'endLine' => 31,
                      'startTokenPos' => 140,
                      'startFilePos' => 794,
                      'endTokenPos' => 140,
                      'endFilePos' => 830,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 31,
            'endLine' => 32,
            'startColumn' => 3,
            'endColumn' => 39,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'minimumNumberOfJobsPerProcess' => 
          array (
            'name' => 'minimumNumberOfJobsPerProcess',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'int',
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
                    'code' => '\'%parallel.minimumNumberOfJobsPerProcess%\'',
                    'attributes' => 
                    array (
                      'startLine' => 33,
                      'endLine' => 33,
                      'startTokenPos' => 157,
                      'startFilePos' => 903,
                      'endTokenPos' => 157,
                      'endFilePos' => 944,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 33,
            'endLine' => 34,
            'startColumn' => 3,
            'endColumn' => 44,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @param positive-int $jobSize
 * @param positive-int $maximumNumberOfProcesses
 * @param positive-int $minimumNumberOfJobsPerProcess
 */',
        'startLine' => 28,
        'endLine' => 37,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Parallel',
        'declaringClassName' => 'PHPStan\\Parallel\\Scheduler',
        'implementingClassName' => 'PHPStan\\Parallel\\Scheduler',
        'currentClassName' => 'PHPStan\\Parallel\\Scheduler',
        'aliasName' => NULL,
      ),
      'scheduleWork' => 
      array (
        'name' => 'scheduleWork',
        'parameters' => 
        array (
          'cpuCores' => 
          array (
            'name' => 'cpuCores',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'int',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 43,
            'endLine' => 43,
            'startColumn' => 3,
            'endColumn' => 15,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'files' => 
          array (
            'name' => 'files',
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
            'startLine' => 44,
            'endLine' => 44,
            'startColumn' => 3,
            'endColumn' => 14,
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
            'name' => 'PHPStan\\Parallel\\Schedule',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @param array<string> $files
 */',
        'startLine' => 42,
        'endLine' => 57,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Parallel',
        'declaringClassName' => 'PHPStan\\Parallel\\Scheduler',
        'implementingClassName' => 'PHPStan\\Parallel\\Scheduler',
        'currentClassName' => 'PHPStan\\Parallel\\Scheduler',
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
            'startLine' => 59,
            'endLine' => 59,
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
        'startLine' => 59,
        'endLine' => 73,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Parallel',
        'declaringClassName' => 'PHPStan\\Parallel\\Scheduler',
        'implementingClassName' => 'PHPStan\\Parallel\\Scheduler',
        'currentClassName' => 'PHPStan\\Parallel\\Scheduler',
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