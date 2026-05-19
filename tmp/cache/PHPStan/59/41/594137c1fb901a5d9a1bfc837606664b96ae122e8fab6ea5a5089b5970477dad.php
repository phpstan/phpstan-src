<?php declare(strict_types = 1);

// osfsl-/home/runner/work/phpstan-src/phpstan-src/vendor/composer/../hoa/compiler/./Exception/UnrecognizedToken.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Hoa\Compiler\Exception\UnrecognizedToken
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-5bf2cdef4ee9884b3070be453dc388e8de8049e7488bd155e3bc750ba7a49d3c-8.4.21-6.70.0.1',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Hoa\\Compiler\\Exception\\UnrecognizedToken',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/vendor/composer/../hoa/compiler/./Exception/UnrecognizedToken.php',
      ),
    ),
    'namespace' => 'Hoa\\Compiler\\Exception',
    'name' => 'Hoa\\Compiler\\Exception\\UnrecognizedToken',
    'shortName' => 'UnrecognizedToken',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Class \\Hoa\\Compiler\\Exception\\UnrecognizedToken.
 *
 * Extending the \\Hoa\\Compiler\\Exception class.
 *
 * @copyright  Copyright © 2007-2017 Hoa community
 * @license    New BSD License
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 47,
    'endLine' => 86,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'Hoa\\Compiler\\Exception\\Exception',
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
      'column' => 
      array (
        'declaringClassName' => 'Hoa\\Compiler\\Exception\\UnrecognizedToken',
        'implementingClassName' => 'Hoa\\Compiler\\Exception\\UnrecognizedToken',
        'name' => 'column',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '0',
          'attributes' => 
          array (
            'startLine' => 54,
            'endLine' => 54,
            'startTokenPos' => 29,
            'startFilePos' => 2004,
            'endTokenPos' => 29,
            'endFilePos' => 2004,
          ),
        ),
        'docComment' => '/**
 * Column.
 *
 * @var int
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 54,
        'endLine' => 54,
        'startColumn' => 5,
        'endColumn' => 26,
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
          'message' => 
          array (
            'name' => 'message',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 67,
            'endLine' => 67,
            'startColumn' => 33,
            'endColumn' => 40,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'code' => 
          array (
            'name' => 'code',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 67,
            'endLine' => 67,
            'startColumn' => 43,
            'endColumn' => 47,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'arg' => 
          array (
            'name' => 'arg',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 67,
            'endLine' => 67,
            'startColumn' => 50,
            'endColumn' => 53,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'line' => 
          array (
            'name' => 'line',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 67,
            'endLine' => 67,
            'startColumn' => 56,
            'endColumn' => 60,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
          'column' => 
          array (
            'name' => 'column',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 67,
            'endLine' => 67,
            'startColumn' => 63,
            'endColumn' => 69,
            'parameterIndex' => 4,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Override line and add column support.
 *
 * @param   string  $message    Formatted message.
 * @param   int     $code       Code (the ID).
 * @param   array   $arg        RaiseError string arguments.
 * @param   int     $line       Line.
 * @param   int     $column     Column.
 */',
        'startLine' => 67,
        'endLine' => 75,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Hoa\\Compiler\\Exception',
        'declaringClassName' => 'Hoa\\Compiler\\Exception\\UnrecognizedToken',
        'implementingClassName' => 'Hoa\\Compiler\\Exception\\UnrecognizedToken',
        'currentClassName' => 'Hoa\\Compiler\\Exception\\UnrecognizedToken',
        'aliasName' => NULL,
      ),
      'getColumn' => 
      array (
        'name' => 'getColumn',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get column.
 *
 * @return  int
 */',
        'startLine' => 82,
        'endLine' => 85,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Hoa\\Compiler\\Exception',
        'declaringClassName' => 'Hoa\\Compiler\\Exception\\UnrecognizedToken',
        'implementingClassName' => 'Hoa\\Compiler\\Exception\\UnrecognizedToken',
        'currentClassName' => 'Hoa\\Compiler\\Exception\\UnrecognizedToken',
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