<?php declare(strict_types = 1);

// osfsl-/home/runner/work/phpstan-src/phpstan-src/vendor/composer/../react/stream/src/DuplexStreamInterface.php-PHPStan\BetterReflection\Reflection\ReflectionClass-React\Stream\DuplexStreamInterface
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-b5b5ba3a81127d79f0c08ed7e64801389fe457fcaaba18465d3e664121ed0248-8.4.21-6.70.0.1',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'React\\Stream\\DuplexStreamInterface',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/vendor/composer/../react/stream/src/DuplexStreamInterface.php',
      ),
    ),
    'namespace' => 'React\\Stream',
    'name' => 'React\\Stream\\DuplexStreamInterface',
    'shortName' => 'DuplexStreamInterface',
    'isInterface' => true,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * The `DuplexStreamInterface` is responsible for providing an interface for
 * duplex streams (both readable and writable).
 *
 * It builds on top of the existing interfaces for readable and writable streams
 * and follows the exact same method and event semantics.
 * If you\'re new to this concept, you should look into the
 * `ReadableStreamInterface` and `WritableStreamInterface` first.
 *
 * Besides defining a few methods, this interface also implements the
 * `EventEmitterInterface` which allows you to react to the same events defined
 * on the `ReadbleStreamInterface` and `WritableStreamInterface`.
 *
 * The event callback functions MUST be a valid `callable` that obeys strict
 * parameter definitions and MUST accept event parameters exactly as documented.
 * The event callback functions MUST NOT throw an `Exception`.
 * The return value of the event callback functions will be ignored and has no
 * effect, so for performance reasons you\'re recommended to not return any
 * excessive data structures.
 *
 * Every implementation of this interface MUST follow these event semantics in
 * order to be considered a well-behaving stream.
 *
 * > Note that higher-level implementations of this interface may choose to
 *   define additional events with dedicated semantics not defined as part of
 *   this low-level stream specification. Conformance with these event semantics
 *   is out of scope for this interface, so you may also have to refer to the
 *   documentation of such a higher-level implementation.
 *
 * @see ReadableStreamInterface
 * @see WritableStreamInterface
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 37,
    'endLine' => 39,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
      0 => 'React\\Stream\\ReadableStreamInterface',
      1 => 'React\\Stream\\WritableStreamInterface',
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