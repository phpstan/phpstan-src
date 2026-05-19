<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/src/Reflection/Callables/CallableParametersAcceptor.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\Reflection\Callables\CallableParametersAcceptor
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-0a633a40ae32a1743fd8863d0b9a4662ff79cabb8633c4d8e3d05ca2d79d0144',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\Reflection\\Callables\\CallableParametersAcceptor',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/src/Reflection/Callables/CallableParametersAcceptor.php',
      ),
    ),
    'namespace' => 'PHPStan\\Reflection\\Callables',
    'name' => 'PHPStan\\Reflection\\Callables\\CallableParametersAcceptor',
    'shortName' => 'CallableParametersAcceptor',
    'isInterface' => true,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * A ParametersAcceptor for callable types (closures, first-class callables).
 *
 * Extends ParametersAcceptor with information about side effects, exceptions,
 * and other runtime behavior of callable values. This is what PHPStan knows
 * about a closure or callable when it\'s passed as a parameter or stored in a variable.
 *
 * Implemented by ClosureType and used as the return type of
 * Type::getCallableParametersAcceptors().
 *
 * Provides:
 * - Throw points (what exceptions the callable may throw)
 * - Impure points (what side effects the callable may have)
 * - Purity information
 * - Variables captured from outer scope (used variables)
 * - Expressions that are invalidated by calling this callable
 *
 * @api
 * @api-do-not-implement
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 30,
    'endLine' => 63,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
      0 => 'PHPStan\\Reflection\\ParametersAcceptor',
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
      'getThrowPoints' => 
      array (
        'name' => 'getThrowPoints',
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
        'docComment' => '/** @return SimpleThrowPoint[] */',
        'startLine' => 34,
        'endLine' => 34,
        'startColumn' => 2,
        'endColumn' => 41,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection\\Callables',
        'declaringClassName' => 'PHPStan\\Reflection\\Callables\\CallableParametersAcceptor',
        'implementingClassName' => 'PHPStan\\Reflection\\Callables\\CallableParametersAcceptor',
        'currentClassName' => 'PHPStan\\Reflection\\Callables\\CallableParametersAcceptor',
        'aliasName' => NULL,
      ),
      'isPure' => 
      array (
        'name' => 'isPure',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPStan\\TrinaryLogic',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 36,
        'endLine' => 36,
        'startColumn' => 2,
        'endColumn' => 40,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection\\Callables',
        'declaringClassName' => 'PHPStan\\Reflection\\Callables\\CallableParametersAcceptor',
        'implementingClassName' => 'PHPStan\\Reflection\\Callables\\CallableParametersAcceptor',
        'currentClassName' => 'PHPStan\\Reflection\\Callables\\CallableParametersAcceptor',
        'aliasName' => NULL,
      ),
      'acceptsNamedArguments' => 
      array (
        'name' => 'acceptsNamedArguments',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPStan\\TrinaryLogic',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 38,
        'endLine' => 38,
        'startColumn' => 2,
        'endColumn' => 55,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection\\Callables',
        'declaringClassName' => 'PHPStan\\Reflection\\Callables\\CallableParametersAcceptor',
        'implementingClassName' => 'PHPStan\\Reflection\\Callables\\CallableParametersAcceptor',
        'currentClassName' => 'PHPStan\\Reflection\\Callables\\CallableParametersAcceptor',
        'aliasName' => NULL,
      ),
      'getImpurePoints' => 
      array (
        'name' => 'getImpurePoints',
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
        'docComment' => '/** @return SimpleImpurePoint[] */',
        'startLine' => 41,
        'endLine' => 41,
        'startColumn' => 2,
        'endColumn' => 42,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection\\Callables',
        'declaringClassName' => 'PHPStan\\Reflection\\Callables\\CallableParametersAcceptor',
        'implementingClassName' => 'PHPStan\\Reflection\\Callables\\CallableParametersAcceptor',
        'currentClassName' => 'PHPStan\\Reflection\\Callables\\CallableParametersAcceptor',
        'aliasName' => NULL,
      ),
      'getInvalidateExpressions' => 
      array (
        'name' => 'getInvalidateExpressions',
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
 * Tracks when calling a closure invalidates cached type information
 * for variables it captures by reference.
 *
 * @return InvalidateExprNode[]
 */',
        'startLine' => 49,
        'endLine' => 49,
        'startColumn' => 2,
        'endColumn' => 51,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection\\Callables',
        'declaringClassName' => 'PHPStan\\Reflection\\Callables\\CallableParametersAcceptor',
        'implementingClassName' => 'PHPStan\\Reflection\\Callables\\CallableParametersAcceptor',
        'currentClassName' => 'PHPStan\\Reflection\\Callables\\CallableParametersAcceptor',
        'aliasName' => NULL,
      ),
      'getUsedVariables' => 
      array (
        'name' => 'getUsedVariables',
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
        'docComment' => '/** @return string[] */',
        'startLine' => 52,
        'endLine' => 52,
        'startColumn' => 2,
        'endColumn' => 43,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection\\Callables',
        'declaringClassName' => 'PHPStan\\Reflection\\Callables\\CallableParametersAcceptor',
        'implementingClassName' => 'PHPStan\\Reflection\\Callables\\CallableParametersAcceptor',
        'currentClassName' => 'PHPStan\\Reflection\\Callables\\CallableParametersAcceptor',
        'aliasName' => NULL,
      ),
      'mustUseReturnValue' => 
      array (
        'name' => 'mustUseReturnValue',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPStan\\TrinaryLogic',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Whether the callable is marked with the `#[\\NoDiscard]` attribute.
 * On PHP 8.5+ if the return value is unused at runtime, a warning is emitted.
 * PHPStan reports this during analysis regardless of PHP version.
 */',
        'startLine' => 59,
        'endLine' => 59,
        'startColumn' => 2,
        'endColumn' => 52,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection\\Callables',
        'declaringClassName' => 'PHPStan\\Reflection\\Callables\\CallableParametersAcceptor',
        'implementingClassName' => 'PHPStan\\Reflection\\Callables\\CallableParametersAcceptor',
        'currentClassName' => 'PHPStan\\Reflection\\Callables\\CallableParametersAcceptor',
        'aliasName' => NULL,
      ),
      'getAsserts' => 
      array (
        'name' => 'getAsserts',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPStan\\Reflection\\Assertions',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 61,
        'endLine' => 61,
        'startColumn' => 2,
        'endColumn' => 42,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection\\Callables',
        'declaringClassName' => 'PHPStan\\Reflection\\Callables\\CallableParametersAcceptor',
        'implementingClassName' => 'PHPStan\\Reflection\\Callables\\CallableParametersAcceptor',
        'currentClassName' => 'PHPStan\\Reflection\\Callables\\CallableParametersAcceptor',
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