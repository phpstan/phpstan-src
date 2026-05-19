<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/src/Reflection/Callables/SimpleThrowPoint.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\Reflection\Callables\SimpleThrowPoint
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-2a196932d33e2bbfd4b4aa2580a3eb345719aceb4d4b17f1175b633a85f08009',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\Reflection\\Callables\\SimpleThrowPoint',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/src/Reflection/Callables/SimpleThrowPoint.php',
      ),
    ),
    'namespace' => 'PHPStan\\Reflection\\Callables',
    'name' => 'PHPStan\\Reflection\\Callables\\SimpleThrowPoint',
    'shortName' => 'SimpleThrowPoint',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Represents a point where a callable may throw an exception.
 *
 * Used by CallableParametersAcceptor::getThrowPoints() to describe what exceptions
 * a closure or callable value may throw. This is a simplified version of the full
 * ThrowPoint used in the analyser — it carries just the exception type, whether the
 * throw was explicitly declared (@throws), and whether it could be any Throwable.
 *
 * Explicit throw points come from @throws annotations. Implicit throw points represent
 * the possibility that any function call could throw.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 20,
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
      'type' => 
      array (
        'declaringClassName' => 'PHPStan\\Reflection\\Callables\\SimpleThrowPoint',
        'implementingClassName' => 'PHPStan\\Reflection\\Callables\\SimpleThrowPoint',
        'name' => 'type',
        'modifiers' => 4,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPStan\\Type\\Type',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 24,
        'endLine' => 24,
        'startColumn' => 3,
        'endColumn' => 20,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'explicit' => 
      array (
        'declaringClassName' => 'PHPStan\\Reflection\\Callables\\SimpleThrowPoint',
        'implementingClassName' => 'PHPStan\\Reflection\\Callables\\SimpleThrowPoint',
        'name' => 'explicit',
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
        ),
        'startLine' => 25,
        'endLine' => 25,
        'startColumn' => 3,
        'endColumn' => 24,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'canContainAnyThrowable' => 
      array (
        'declaringClassName' => 'PHPStan\\Reflection\\Callables\\SimpleThrowPoint',
        'implementingClassName' => 'PHPStan\\Reflection\\Callables\\SimpleThrowPoint',
        'name' => 'canContainAnyThrowable',
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
        ),
        'startLine' => 26,
        'endLine' => 26,
        'startColumn' => 3,
        'endColumn' => 38,
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
          'type' => 
          array (
            'name' => 'type',
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
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 24,
            'endLine' => 24,
            'startColumn' => 3,
            'endColumn' => 20,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'explicit' => 
          array (
            'name' => 'explicit',
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
            ),
            'startLine' => 25,
            'endLine' => 25,
            'startColumn' => 3,
            'endColumn' => 24,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'canContainAnyThrowable' => 
          array (
            'name' => 'canContainAnyThrowable',
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
            ),
            'startLine' => 26,
            'endLine' => 26,
            'startColumn' => 3,
            'endColumn' => 38,
            'parameterIndex' => 2,
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
        'endLine' => 29,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'PHPStan\\Reflection\\Callables',
        'declaringClassName' => 'PHPStan\\Reflection\\Callables\\SimpleThrowPoint',
        'implementingClassName' => 'PHPStan\\Reflection\\Callables\\SimpleThrowPoint',
        'currentClassName' => 'PHPStan\\Reflection\\Callables\\SimpleThrowPoint',
        'aliasName' => NULL,
      ),
      'createExplicit' => 
      array (
        'name' => 'createExplicit',
        'parameters' => 
        array (
          'type' => 
          array (
            'name' => 'type',
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
            'startLine' => 31,
            'endLine' => 31,
            'startColumn' => 40,
            'endColumn' => 49,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'canContainAnyThrowable' => 
          array (
            'name' => 'canContainAnyThrowable',
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
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 31,
            'endLine' => 31,
            'startColumn' => 52,
            'endColumn' => 79,
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
            'name' => 'self',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 31,
        'endLine' => 34,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'PHPStan\\Reflection\\Callables',
        'declaringClassName' => 'PHPStan\\Reflection\\Callables\\SimpleThrowPoint',
        'implementingClassName' => 'PHPStan\\Reflection\\Callables\\SimpleThrowPoint',
        'currentClassName' => 'PHPStan\\Reflection\\Callables\\SimpleThrowPoint',
        'aliasName' => NULL,
      ),
      'createImplicit' => 
      array (
        'name' => 'createImplicit',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'self',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 36,
        'endLine' => 39,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'PHPStan\\Reflection\\Callables',
        'declaringClassName' => 'PHPStan\\Reflection\\Callables\\SimpleThrowPoint',
        'implementingClassName' => 'PHPStan\\Reflection\\Callables\\SimpleThrowPoint',
        'currentClassName' => 'PHPStan\\Reflection\\Callables\\SimpleThrowPoint',
        'aliasName' => NULL,
      ),
      'getType' => 
      array (
        'name' => 'getType',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPStan\\Type\\Type',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 41,
        'endLine' => 44,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection\\Callables',
        'declaringClassName' => 'PHPStan\\Reflection\\Callables\\SimpleThrowPoint',
        'implementingClassName' => 'PHPStan\\Reflection\\Callables\\SimpleThrowPoint',
        'currentClassName' => 'PHPStan\\Reflection\\Callables\\SimpleThrowPoint',
        'aliasName' => NULL,
      ),
      'isExplicit' => 
      array (
        'name' => 'isExplicit',
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
        'startLine' => 46,
        'endLine' => 49,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection\\Callables',
        'declaringClassName' => 'PHPStan\\Reflection\\Callables\\SimpleThrowPoint',
        'implementingClassName' => 'PHPStan\\Reflection\\Callables\\SimpleThrowPoint',
        'currentClassName' => 'PHPStan\\Reflection\\Callables\\SimpleThrowPoint',
        'aliasName' => NULL,
      ),
      'canContainAnyThrowable' => 
      array (
        'name' => 'canContainAnyThrowable',
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
        'startLine' => 51,
        'endLine' => 54,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection\\Callables',
        'declaringClassName' => 'PHPStan\\Reflection\\Callables\\SimpleThrowPoint',
        'implementingClassName' => 'PHPStan\\Reflection\\Callables\\SimpleThrowPoint',
        'currentClassName' => 'PHPStan\\Reflection\\Callables\\SimpleThrowPoint',
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