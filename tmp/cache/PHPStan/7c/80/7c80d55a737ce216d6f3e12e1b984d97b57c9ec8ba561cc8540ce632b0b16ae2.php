<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/src/Rules/Exceptions/ExceptionTypeResolver.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\Rules\Exceptions\ExceptionTypeResolver
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-22259de35ac187eec032466cb5d07cd9cbb363c97eab82e9cc58bbc86bc832ef',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\Rules\\Exceptions\\ExceptionTypeResolver',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/src/Rules/Exceptions/ExceptionTypeResolver.php',
      ),
    ),
    'namespace' => 'PHPStan\\Rules\\Exceptions',
    'name' => 'PHPStan\\Rules\\Exceptions\\ExceptionTypeResolver',
    'shortName' => 'ExceptionTypeResolver',
    'isInterface' => true,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * @api
 *
 * This interface allows you to write custom logic that can dynamically decide
 * whether an exception is checked or unchecked type.
 *
 * Because the interface accepts a Scope, you can ask about the place in the code where
 * it\'s being decided - a file, a namespace or a class name.
 *
 * There can only be a single ExceptionTypeResolver per project, and you can register it
 * in your configuration file like this:
 *
 * ```
 *  services:
 *      exceptionTypeResolver!:
 *          class: PHPStan\\Rules\\Exceptions\\ExceptionTypeResolver
 *  ```
 *
 * You can also take advantage of the `PHPStan\\Rules\\Exceptions\\DefaultExceptionTypeResolver`
 * by injecting it into the constructor of your ExceptionTypeResolver
 * and delegate the logic of the classes and places you don\'t care about.
 *
 * DefaultExceptionTypeResolver decides the type of the exception based on configuration
 * parameters like `exceptions.uncheckedExceptionClasses` etc.
 *
 * Learn more: https://phpstan.org/blog/bring-your-exceptions-under-control
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 34,
    'endLine' => 39,
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
      'isCheckedException' => 
      array (
        'name' => 'isCheckedException',
        'parameters' => 
        array (
          'className' => 
          array (
            'name' => 'className',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'string',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 37,
            'endLine' => 37,
            'startColumn' => 37,
            'endColumn' => 53,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
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
            'startLine' => 37,
            'endLine' => 37,
            'startColumn' => 56,
            'endColumn' => 67,
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
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 37,
        'endLine' => 37,
        'startColumn' => 2,
        'endColumn' => 75,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Rules\\Exceptions',
        'declaringClassName' => 'PHPStan\\Rules\\Exceptions\\ExceptionTypeResolver',
        'implementingClassName' => 'PHPStan\\Rules\\Exceptions\\ExceptionTypeResolver',
        'currentClassName' => 'PHPStan\\Rules\\Exceptions\\ExceptionTypeResolver',
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