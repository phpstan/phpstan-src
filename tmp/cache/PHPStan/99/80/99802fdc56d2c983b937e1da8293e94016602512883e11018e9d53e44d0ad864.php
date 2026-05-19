<?php declare(strict_types = 1);

// osfsl-/home/runner/work/phpstan-src/phpstan-src/vendor/composer/../fidry/cpu-core-counter/src/CpuCoreCounter.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Fidry\CpuCoreCounter\CpuCoreCounter
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-29926d4990ff776659f70f2b26fa1ee4ca4f132bcc322ba347113dd5085c11ab-8.4.21-6.70.0.1',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Fidry\\CpuCoreCounter\\CpuCoreCounter',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/vendor/composer/../fidry/cpu-core-counter/src/CpuCoreCounter.php',
      ),
    ),
    'namespace' => 'Fidry\\CpuCoreCounter',
    'name' => 'Fidry\\CpuCoreCounter\\CpuCoreCounter',
    'shortName' => 'CpuCoreCounter',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => NULL,
    'attributes' => 
    array (
    ),
    'startLine' => 26,
    'endLine' => 270,
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
      'finders' => 
      array (
        'declaringClassName' => 'Fidry\\CpuCoreCounter\\CpuCoreCounter',
        'implementingClassName' => 'Fidry\\CpuCoreCounter\\CpuCoreCounter',
        'name' => 'finders',
        'modifiers' => 4,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/**
 * @var list<CpuCoreFinder>
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 31,
        'endLine' => 31,
        'startColumn' => 5,
        'endColumn' => 21,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'count' => 
      array (
        'declaringClassName' => 'Fidry\\CpuCoreCounter\\CpuCoreCounter',
        'implementingClassName' => 'Fidry\\CpuCoreCounter\\CpuCoreCounter',
        'name' => 'count',
        'modifiers' => 4,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/**
 * @var positive-int|null
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 36,
        'endLine' => 36,
        'startColumn' => 5,
        'endColumn' => 19,
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
      '__construct' => 
      array (
        'name' => '__construct',
        'parameters' => 
        array (
          'finders' => 
          array (
            'name' => 'finders',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 41,
                'endLine' => 41,
                'startTokenPos' => 109,
                'startFilePos' => 878,
                'endTokenPos' => 109,
                'endFilePos' => 881,
              ),
            ),
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
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 41,
            'endLine' => 41,
            'startColumn' => 33,
            'endColumn' => 54,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @param list<CpuCoreFinder>|null $finders
 */',
        'startLine' => 41,
        'endLine' => 44,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Fidry\\CpuCoreCounter',
        'declaringClassName' => 'Fidry\\CpuCoreCounter\\CpuCoreCounter',
        'implementingClassName' => 'Fidry\\CpuCoreCounter\\CpuCoreCounter',
        'currentClassName' => 'Fidry\\CpuCoreCounter\\CpuCoreCounter',
        'aliasName' => NULL,
      ),
      'getAvailableForParallelisation' => 
      array (
        'name' => 'getAvailableForParallelisation',
        'parameters' => 
        array (
          'reservedCpus' => 
          array (
            'name' => 'reservedCpus',
            'default' => 
            array (
              'code' => '0',
              'attributes' => 
              array (
                'startLine' => 81,
                'endLine' => 81,
                'startTokenPos' => 148,
                'startFilePos' => 4178,
                'endTokenPos' => 148,
                'endFilePos' => 4178,
              ),
            ),
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
            'startLine' => 81,
            'endLine' => 81,
            'startColumn' => 9,
            'endColumn' => 29,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
          'countLimit' => 
          array (
            'name' => 'countLimit',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 82,
                'endLine' => 82,
                'startTokenPos' => 158,
                'startFilePos' => 4208,
                'endTokenPos' => 158,
                'endFilePos' => 4211,
              ),
            ),
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
                      'name' => 'int',
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
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 82,
            'endLine' => 82,
            'startColumn' => 9,
            'endColumn' => 31,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
          'loadLimit' => 
          array (
            'name' => 'loadLimit',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 83,
                'endLine' => 83,
                'startTokenPos' => 168,
                'startFilePos' => 4242,
                'endTokenPos' => 168,
                'endFilePos' => 4245,
              ),
            ),
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
                      'name' => 'float',
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
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 83,
            'endLine' => 83,
            'startColumn' => 9,
            'endColumn' => 32,
            'parameterIndex' => 2,
            'isOptional' => true,
          ),
          'systemLoadAverage' => 
          array (
            'name' => 'systemLoadAverage',
            'default' => 
            array (
              'code' => '0.0',
              'attributes' => 
              array (
                'startLine' => 84,
                'endLine' => 84,
                'startTokenPos' => 178,
                'startFilePos' => 4284,
                'endTokenPos' => 178,
                'endFilePos' => 4285,
              ),
            ),
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
                      'name' => 'float',
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
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 84,
            'endLine' => 84,
            'startColumn' => 9,
            'endColumn' => 38,
            'parameterIndex' => 3,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Fidry\\CpuCoreCounter\\ParallelisationResult',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @param positive-int|0    $reservedCpus      Number of CPUs to reserve. This is useful when you want
 *                                             to reserve some CPUs for other processes. If the main
 *                                             process is going to be busy still, you may want to set
 *                                             this value to 1.
 * @param non-zero-int|null $countLimit        The maximum number of CPUs to return. If not provided, it
 *                                             may look for a limit in the environment variables, e.g.
 *                                             KUBERNETES_CPU_LIMIT. If negative, the limit will be
 *                                             the total number of cores found minus the absolute value.
 *                                             For instance if the system has 10 cores and countLimit=-2,
 *                                             then the effective limit considered will be 8.
 * @param float|null        $loadLimit         Element of [0., 1.]. Percentage representing the
 *                                             amount of cores that should be used among the available
 *                                             resources. For instance, if set to 0.7, it will use 70%
 *                                             of the available cores, i.e. if 1 core is reserved, 11
 *                                             cores are available and 5 are busy, it will use 70%
 *                                             of (11-1-5)=5 cores, so 3 cores. Set this parameter to null
 *                                             to skip this check. Beware that 1 does not mean "no limit",
 *                                             but 100% of the _available_ resources, i.e. with the
 *                                             previous example, it will return 5 cores. How busy is
 *                                             the system is determined by the system load average
 *                                             (see $systemLoadAverage).
 * @param float|null        $systemLoadAverage The system load average. If passed, it will use
 *                                             this information to limit the available cores based
 *                                             on the _available_ resources. For instance, if there
 *                                             is 10 cores but 3 are busy, then only 7 cores will
 *                                             be considered for further calculation. If set to
 *                                             `null`, it will use `sys_getloadavg()` to check the
 *                                             load of the system in the past minute. You can
 *                                             otherwise pass an arbitrary value. Should be a
 *                                             positive float.
 *
 * @see https://php.net/manual/en/function.sys-getloadavg.php
 */',
        'startLine' => 80,
        'endLine' => 129,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Fidry\\CpuCoreCounter',
        'declaringClassName' => 'Fidry\\CpuCoreCounter\\CpuCoreCounter',
        'implementingClassName' => 'Fidry\\CpuCoreCounter\\CpuCoreCounter',
        'currentClassName' => 'Fidry\\CpuCoreCounter\\CpuCoreCounter',
        'aliasName' => NULL,
      ),
      'getCount' => 
      array (
        'name' => 'getCount',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'int',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @throws NumberOfCpuCoreNotFound
 *
 * @return positive-int
 */',
        'startLine' => 136,
        'endLine' => 144,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Fidry\\CpuCoreCounter',
        'declaringClassName' => 'Fidry\\CpuCoreCounter\\CpuCoreCounter',
        'implementingClassName' => 'Fidry\\CpuCoreCounter\\CpuCoreCounter',
        'currentClassName' => 'Fidry\\CpuCoreCounter\\CpuCoreCounter',
        'aliasName' => NULL,
      ),
      'getCountWithFallback' => 
      array (
        'name' => 'getCountWithFallback',
        'parameters' => 
        array (
          'fallback' => 
          array (
            'name' => 'fallback',
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
            'startLine' => 151,
            'endLine' => 151,
            'startColumn' => 42,
            'endColumn' => 54,
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
            'name' => 'int',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @param positive-int $fallback
 *
 * @return positive-int
 */',
        'startLine' => 151,
        'endLine' => 158,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Fidry\\CpuCoreCounter',
        'declaringClassName' => 'Fidry\\CpuCoreCounter\\CpuCoreCounter',
        'implementingClassName' => 'Fidry\\CpuCoreCounter\\CpuCoreCounter',
        'currentClassName' => 'Fidry\\CpuCoreCounter\\CpuCoreCounter',
        'aliasName' => NULL,
      ),
      'trace' => 
      array (
        'name' => 'trace',
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
        'docComment' => '/**
 * This method is mostly for debugging purposes.
 */',
        'startLine' => 163,
        'endLine' => 186,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Fidry\\CpuCoreCounter',
        'declaringClassName' => 'Fidry\\CpuCoreCounter\\CpuCoreCounter',
        'implementingClassName' => 'Fidry\\CpuCoreCounter\\CpuCoreCounter',
        'currentClassName' => 'Fidry\\CpuCoreCounter\\CpuCoreCounter',
        'aliasName' => NULL,
      ),
      'findCount' => 
      array (
        'name' => 'findCount',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'int',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @throws NumberOfCpuCoreNotFound
 *
 * @return positive-int
 */',
        'startLine' => 193,
        'endLine' => 204,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'Fidry\\CpuCoreCounter',
        'declaringClassName' => 'Fidry\\CpuCoreCounter\\CpuCoreCounter',
        'implementingClassName' => 'Fidry\\CpuCoreCounter\\CpuCoreCounter',
        'currentClassName' => 'Fidry\\CpuCoreCounter\\CpuCoreCounter',
        'aliasName' => NULL,
      ),
      'getFinderAndCores' => 
      array (
        'name' => 'getFinderAndCores',
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
 * @throws NumberOfCpuCoreNotFound
 *
 * @return array{CpuCoreFinder, positive-int}
 */',
        'startLine' => 211,
        'endLine' => 222,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Fidry\\CpuCoreCounter',
        'declaringClassName' => 'Fidry\\CpuCoreCounter\\CpuCoreCounter',
        'implementingClassName' => 'Fidry\\CpuCoreCounter\\CpuCoreCounter',
        'currentClassName' => 'Fidry\\CpuCoreCounter\\CpuCoreCounter',
        'aliasName' => NULL,
      ),
      'getKubernetesLimit' => 
      array (
        'name' => 'getKubernetesLimit',
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
                  'name' => 'int',
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
        'docComment' => '/**
 * @return positive-int|null
 */',
        'startLine' => 227,
        'endLine' => 232,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Fidry\\CpuCoreCounter',
        'declaringClassName' => 'Fidry\\CpuCoreCounter\\CpuCoreCounter',
        'implementingClassName' => 'Fidry\\CpuCoreCounter\\CpuCoreCounter',
        'currentClassName' => 'Fidry\\CpuCoreCounter\\CpuCoreCounter',
        'aliasName' => NULL,
      ),
      'checkCountLimit' => 
      array (
        'name' => 'checkCountLimit',
        'parameters' => 
        array (
          'countLimit' => 
          array (
            'name' => 'countLimit',
            'default' => NULL,
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
                      'name' => 'int',
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
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 234,
            'endLine' => 234,
            'startColumn' => 45,
            'endColumn' => 60,
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
        'startLine' => 234,
        'endLine' => 241,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 20,
        'namespace' => 'Fidry\\CpuCoreCounter',
        'declaringClassName' => 'Fidry\\CpuCoreCounter\\CpuCoreCounter',
        'implementingClassName' => 'Fidry\\CpuCoreCounter\\CpuCoreCounter',
        'currentClassName' => 'Fidry\\CpuCoreCounter\\CpuCoreCounter',
        'aliasName' => NULL,
      ),
      'checkLoadLimit' => 
      array (
        'name' => 'checkLoadLimit',
        'parameters' => 
        array (
          'loadLimit' => 
          array (
            'name' => 'loadLimit',
            'default' => NULL,
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
                      'name' => 'float',
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
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 243,
            'endLine' => 243,
            'startColumn' => 44,
            'endColumn' => 60,
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
        'startLine' => 243,
        'endLine' => 257,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 20,
        'namespace' => 'Fidry\\CpuCoreCounter',
        'declaringClassName' => 'Fidry\\CpuCoreCounter\\CpuCoreCounter',
        'implementingClassName' => 'Fidry\\CpuCoreCounter\\CpuCoreCounter',
        'currentClassName' => 'Fidry\\CpuCoreCounter\\CpuCoreCounter',
        'aliasName' => NULL,
      ),
      'checkSystemLoadAverage' => 
      array (
        'name' => 'checkSystemLoadAverage',
        'parameters' => 
        array (
          'systemLoadAverage' => 
          array (
            'name' => 'systemLoadAverage',
            'default' => NULL,
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
                      'name' => 'float',
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
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 259,
            'endLine' => 259,
            'startColumn' => 52,
            'endColumn' => 76,
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
        'startLine' => 259,
        'endLine' => 269,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 20,
        'namespace' => 'Fidry\\CpuCoreCounter',
        'declaringClassName' => 'Fidry\\CpuCoreCounter\\CpuCoreCounter',
        'implementingClassName' => 'Fidry\\CpuCoreCounter\\CpuCoreCounter',
        'currentClassName' => 'Fidry\\CpuCoreCounter\\CpuCoreCounter',
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