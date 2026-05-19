<?php declare(strict_types = 1);

// osfsl-/home/runner/work/phpstan-src/phpstan-src/vendor/composer/../hoa/event/./Listens.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Hoa\Event\Listens
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-8db66341224bec8515a4609465d7c0b954a44d685d50ccb06c82bb7d3ebc0d0e-8.4.21-6.70.0.1',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Hoa\\Event\\Listens',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/vendor/composer/../hoa/event/./Listens.php',
      ),
    ),
    'namespace' => 'Hoa\\Event',
    'name' => 'Hoa\\Event\\Listens',
    'shortName' => 'Listens',
    'isInterface' => false,
    'isTrait' => true,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Trait \\Hoa\\Event\\Listens.
 *
 * Implementation of a listener.
 *
 * @copyright  Copyright © 2007-2017 Hoa community
 * @license    New BSD License
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 47,
    'endLine' => 106,
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
      '_listener' => 
      array (
        'declaringClassName' => 'Hoa\\Event\\Listens',
        'implementingClassName' => 'Hoa\\Event\\Listens',
        'name' => '_listener',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => 'null',
          'attributes' => 
          array (
            'startLine' => 54,
            'endLine' => 54,
            'startTokenPos' => 25,
            'startFilePos' => 1955,
            'endTokenPos' => 25,
            'endFilePos' => 1958,
          ),
        ),
        'docComment' => '/**
 * Listener instance.
 *
 * @var \\Hoa\\Event\\Listener
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 54,
        'endLine' => 54,
        'startColumn' => 5,
        'endColumn' => 32,
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
      'on' => 
      array (
        'name' => 'on',
        'parameters' => 
        array (
          'listenerId' => 
          array (
            'name' => 'listenerId',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 65,
            'endLine' => 65,
            'startColumn' => 24,
            'endColumn' => 34,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'callable' => 
          array (
            'name' => 'callable',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 65,
            'endLine' => 65,
            'startColumn' => 37,
            'endColumn' => 45,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Attach a callable to a listenable component.
 *
 * @param   string  $listenerId    Listener ID.
 * @param   mixed   $callable      Callable.
 * @return  \\Hoa\\Event\\Listenable
 */',
        'startLine' => 65,
        'endLine' => 81,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Hoa\\Event',
        'declaringClassName' => 'Hoa\\Event\\Listens',
        'implementingClassName' => 'Hoa\\Event\\Listens',
        'currentClassName' => 'Hoa\\Event\\Listens',
        'aliasName' => NULL,
      ),
      'setListener' => 
      array (
        'name' => 'setListener',
        'parameters' => 
        array (
          'listener' => 
          array (
            'name' => 'listener',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Hoa\\Event\\Listener',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 89,
            'endLine' => 89,
            'startColumn' => 36,
            'endColumn' => 53,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Set listener.
 *
 * @param  \\Hoa\\Event\\Listener  $listener    Listener.
 * @return \\Hoa\\Event\\Listener
 */',
        'startLine' => 89,
        'endLine' => 95,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Hoa\\Event',
        'declaringClassName' => 'Hoa\\Event\\Listens',
        'implementingClassName' => 'Hoa\\Event\\Listens',
        'currentClassName' => 'Hoa\\Event\\Listens',
        'aliasName' => NULL,
      ),
      'getListener' => 
      array (
        'name' => 'getListener',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get listener.
 *
 * @return \\Hoa\\Event\\Listener
 */',
        'startLine' => 102,
        'endLine' => 105,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Hoa\\Event',
        'declaringClassName' => 'Hoa\\Event\\Listens',
        'implementingClassName' => 'Hoa\\Event\\Listens',
        'currentClassName' => 'Hoa\\Event\\Listens',
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