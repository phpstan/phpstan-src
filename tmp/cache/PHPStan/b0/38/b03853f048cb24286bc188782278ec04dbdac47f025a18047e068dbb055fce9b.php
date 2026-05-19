<?php declare(strict_types = 1);

// osfsl-/home/runner/work/phpstan-src/phpstan-src/vendor/composer/../hoa/exception/./Exception.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Hoa\Exception\Exception
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-b83123d1d1e13874b0575d32fffaa3bbbf0babfc808e87282c6af69d8b1cca75-8.4.21-6.70.0.1',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Hoa\\Exception\\Exception',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/vendor/composer/../hoa/exception/./Exception.php',
      ),
    ),
    'namespace' => 'Hoa\\Exception',
    'name' => 'Hoa\\Exception\\Exception',
    'shortName' => 'Exception',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Class \\Hoa\\Exception\\Exception.
 *
 * Each exception must extend \\Hoa\\Exception\\Exception.
 *
 * @copyright  Copyright © 2007-2017 Hoa community
 * @license    New BSD License
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 50,
    'endLine' => 95,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'Hoa\\Exception\\Idle',
    'implementsClassNames' => 
    array (
      0 => 'Hoa\\Event\\Source',
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
            'startLine' => 64,
            'endLine' => 64,
            'startColumn' => 9,
            'endColumn' => 16,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'code' => 
          array (
            'name' => 'code',
            'default' => 
            array (
              'code' => '0',
              'attributes' => 
              array (
                'startLine' => 65,
                'endLine' => 65,
                'startTokenPos' => 51,
                'startFilePos' => 2546,
                'endTokenPos' => 51,
                'endFilePos' => 2546,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 65,
            'endLine' => 65,
            'startColumn' => 9,
            'endColumn' => 22,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
          'arguments' => 
          array (
            'name' => 'arguments',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 66,
                'endLine' => 66,
                'startTokenPos' => 58,
                'startFilePos' => 2570,
                'endTokenPos' => 59,
                'endFilePos' => 2571,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 66,
            'endLine' => 66,
            'startColumn' => 9,
            'endColumn' => 23,
            'parameterIndex' => 2,
            'isOptional' => true,
          ),
          'previous' => 
          array (
            'name' => 'previous',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 67,
                'endLine' => 67,
                'startTokenPos' => 66,
                'startFilePos' => 2595,
                'endTokenPos' => 66,
                'endFilePos' => 2598,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 67,
            'endLine' => 67,
            'startColumn' => 9,
            'endColumn' => 25,
            'parameterIndex' => 3,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Create an exception.
 * An exception is built with a formatted message, a code (an ID), and an
 * array that contains the list of formatted string for the message. If
 * chaining, we can add a previous exception.
 *
 * @param   string      $message      Formatted message.
 * @param   int         $code         Code (the ID).
 * @param   array       $arguments    Arguments to format message.
 * @param   \\Throwable  $previous     Previous exception in chaining.
 */',
        'startLine' => 63,
        'endLine' => 78,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Hoa\\Exception',
        'declaringClassName' => 'Hoa\\Exception\\Exception',
        'implementingClassName' => 'Hoa\\Exception\\Exception',
        'currentClassName' => 'Hoa\\Exception\\Exception',
        'aliasName' => NULL,
      ),
      'send' => 
      array (
        'name' => 'send',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Send the exception on hoa://Event/Exception.
 *
 * @return  void
 */',
        'startLine' => 85,
        'endLine' => 94,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Hoa\\Exception',
        'declaringClassName' => 'Hoa\\Exception\\Exception',
        'implementingClassName' => 'Hoa\\Exception\\Exception',
        'currentClassName' => 'Hoa\\Exception\\Exception',
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