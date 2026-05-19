<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/src/Rules/Exceptions/TooWideThrowTypeCheck.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\Rules\Exceptions\TooWideThrowTypeCheck
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-e3c3b9f4d7a0fef888b2b5c17c347f041145f26f9bfa3b5ab8ad033e5d12bb5f',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\Rules\\Exceptions\\TooWideThrowTypeCheck',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/src/Rules/Exceptions/TooWideThrowTypeCheck.php',
      ),
    ),
    'namespace' => 'PHPStan\\Rules\\Exceptions',
    'name' => 'PHPStan\\Rules\\Exceptions\\TooWideThrowTypeCheck',
    'shortName' => 'TooWideThrowTypeCheck',
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
    'startLine' => 15,
    'endLine' => 56,
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
      'implicitThrows' => 
      array (
        'declaringClassName' => 'PHPStan\\Rules\\Exceptions\\TooWideThrowTypeCheck',
        'implementingClassName' => 'PHPStan\\Rules\\Exceptions\\TooWideThrowTypeCheck',
        'name' => 'implicitThrows',
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
                'code' => '\'%exceptions.implicitThrows%\'',
                'attributes' => 
                array (
                  'startLine' => 20,
                  'endLine' => 20,
                  'startTokenPos' => 88,
                  'startFilePos' => 491,
                  'endTokenPos' => 88,
                  'endFilePos' => 519,
                ),
              ),
            ),
          ),
        ),
        'startLine' => 20,
        'endLine' => 21,
        'startColumn' => 3,
        'endColumn' => 30,
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
          'implicitThrows' => 
          array (
            'name' => 'implicitThrows',
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
                    'code' => '\'%exceptions.implicitThrows%\'',
                    'attributes' => 
                    array (
                      'startLine' => 20,
                      'endLine' => 20,
                      'startTokenPos' => 88,
                      'startFilePos' => 491,
                      'endTokenPos' => 88,
                      'endFilePos' => 519,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 20,
            'endLine' => 21,
            'startColumn' => 3,
            'endColumn' => 30,
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
        'startLine' => 19,
        'endLine' => 24,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Rules\\Exceptions',
        'declaringClassName' => 'PHPStan\\Rules\\Exceptions\\TooWideThrowTypeCheck',
        'implementingClassName' => 'PHPStan\\Rules\\Exceptions\\TooWideThrowTypeCheck',
        'currentClassName' => 'PHPStan\\Rules\\Exceptions\\TooWideThrowTypeCheck',
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
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\Type\\Type',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 30,
            'endLine' => 30,
            'startColumn' => 24,
            'endColumn' => 38,
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
            'startLine' => 30,
            'endLine' => 30,
            'startColumn' => 41,
            'endColumn' => 58,
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
 * @return string[]
 */',
        'startLine' => 30,
        'endLine' => 54,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Rules\\Exceptions',
        'declaringClassName' => 'PHPStan\\Rules\\Exceptions\\TooWideThrowTypeCheck',
        'implementingClassName' => 'PHPStan\\Rules\\Exceptions\\TooWideThrowTypeCheck',
        'currentClassName' => 'PHPStan\\Rules\\Exceptions\\TooWideThrowTypeCheck',
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