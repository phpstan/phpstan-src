<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/src/Rules/Exceptions/MissingCheckedExceptionInThrowsCheck.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\Rules\Exceptions\MissingCheckedExceptionInThrowsCheck
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-527e1c1073611557f9c2b021f6632a270e524a58099c371a4c777fc2e32c30f6',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\Rules\\Exceptions\\MissingCheckedExceptionInThrowsCheck',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/src/Rules/Exceptions/MissingCheckedExceptionInThrowsCheck.php',
      ),
    ),
    'namespace' => 'PHPStan\\Rules\\Exceptions',
    'name' => 'PHPStan\\Rules\\Exceptions\\MissingCheckedExceptionInThrowsCheck',
    'shortName' => 'MissingCheckedExceptionInThrowsCheck',
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
    'startLine' => 17,
    'endLine' => 67,
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
      'exceptionTypeResolver' => 
      array (
        'declaringClassName' => 'PHPStan\\Rules\\Exceptions\\MissingCheckedExceptionInThrowsCheck',
        'implementingClassName' => 'PHPStan\\Rules\\Exceptions\\MissingCheckedExceptionInThrowsCheck',
        'name' => 'exceptionTypeResolver',
        'modifiers' => 4,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPStan\\Rules\\Exceptions\\ExceptionTypeResolver',
            'isIdentifier' => false,
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
                'code' => '\'@exceptionTypeResolver\'',
                'attributes' => 
                array (
                  'startLine' => 22,
                  'endLine' => 22,
                  'startTokenPos' => 96,
                  'startFilePos' => 539,
                  'endTokenPos' => 96,
                  'endFilePos' => 562,
                ),
              ),
            ),
          ),
        ),
        'startLine' => 22,
        'endLine' => 23,
        'startColumn' => 3,
        'endColumn' => 54,
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
          'exceptionTypeResolver' => 
          array (
            'name' => 'exceptionTypeResolver',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\Rules\\Exceptions\\ExceptionTypeResolver',
                'isIdentifier' => false,
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
                    'code' => '\'@exceptionTypeResolver\'',
                    'attributes' => 
                    array (
                      'startLine' => 22,
                      'endLine' => 22,
                      'startTokenPos' => 96,
                      'startFilePos' => 539,
                      'endTokenPos' => 96,
                      'endFilePos' => 562,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 22,
            'endLine' => 23,
            'startColumn' => 3,
            'endColumn' => 54,
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
        'startLine' => 21,
        'endLine' => 26,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Rules\\Exceptions',
        'declaringClassName' => 'PHPStan\\Rules\\Exceptions\\MissingCheckedExceptionInThrowsCheck',
        'implementingClassName' => 'PHPStan\\Rules\\Exceptions\\MissingCheckedExceptionInThrowsCheck',
        'currentClassName' => 'PHPStan\\Rules\\Exceptions\\MissingCheckedExceptionInThrowsCheck',
        'aliasName' => NULL,
      ),
      'check' => 
      array (
        'name' => 'check',
        'parameters' => 
        array (
          'throwType' => 
          array (
            'name' => 'throwType',
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
                      'name' => 'PHPStan\\Type\\Type',
                      'isIdentifier' => false,
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
            'startLine' => 32,
            'endLine' => 32,
            'startColumn' => 24,
            'endColumn' => 39,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'throwPoints' => 
          array (
            'name' => 'throwPoints',
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
            'startLine' => 32,
            'endLine' => 32,
            'startColumn' => 42,
            'endColumn' => 59,
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
        'docComment' => '/**
 * @param ThrowPoint[] $throwPoints
 * @return array<int, array{string, Node\\Expr|Node\\Stmt}>
 */',
        'startLine' => 32,
        'endLine' => 65,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Rules\\Exceptions',
        'declaringClassName' => 'PHPStan\\Rules\\Exceptions\\MissingCheckedExceptionInThrowsCheck',
        'implementingClassName' => 'PHPStan\\Rules\\Exceptions\\MissingCheckedExceptionInThrowsCheck',
        'currentClassName' => 'PHPStan\\Rules\\Exceptions\\MissingCheckedExceptionInThrowsCheck',
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