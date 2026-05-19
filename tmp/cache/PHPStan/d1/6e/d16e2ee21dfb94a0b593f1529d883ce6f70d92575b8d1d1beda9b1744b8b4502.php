<?php declare(strict_types = 1);

// osfsl-/home/runner/work/phpstan-src/phpstan-src/vendor/composer/../react/socket/src/ConnectorInterface.php-PHPStan\BetterReflection\Reflection\ReflectionClass-React\Socket\ConnectorInterface
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-928a4d2812cfaf06d1593ade0a59a02973514594d56ebfee66553e64a80b9fc3-8.4.21-6.70.0.1',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'React\\Socket\\ConnectorInterface',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/vendor/composer/../react/socket/src/ConnectorInterface.php',
      ),
    ),
    'namespace' => 'React\\Socket',
    'name' => 'React\\Socket\\ConnectorInterface',
    'shortName' => 'ConnectorInterface',
    'isInterface' => true,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * The `ConnectorInterface` is responsible for providing an interface for
 * establishing streaming connections, such as a normal TCP/IP connection.
 *
 * This is the main interface defined in this package and it is used throughout
 * React\'s vast ecosystem.
 *
 * Most higher-level components (such as HTTP, database or other networking
 * service clients) accept an instance implementing this interface to create their
 * TCP/IP connection to the underlying networking service.
 * This is usually done via dependency injection, so it\'s fairly simple to actually
 * swap this implementation against any other implementation of this interface.
 *
 * The interface only offers a single `connect()` method.
 *
 * @see ConnectionInterface
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 22,
    'endLine' => 59,
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
      'connect' => 
      array (
        'name' => 'connect',
        'parameters' => 
        array (
          'uri' => 
          array (
            'name' => 'uri',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 58,
            'endLine' => 58,
            'startColumn' => 29,
            'endColumn' => 32,
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
 * Creates a streaming connection to the given remote address
 *
 * If returns a Promise which either fulfills with a stream implementing
 * `ConnectionInterface` on success or rejects with an `Exception` if the
 * connection is not successful.
 *
 * ```php
 * $connector->connect(\'google.com:443\')->then(
 *     function (React\\Socket\\ConnectionInterface $connection) {
 *         // connection successfully established
 *     },
 *     function (Exception $error) {
 *         // failed to connect due to $error
 *     }
 * );
 * ```
 *
 * The returned Promise MUST be implemented in such a way that it can be
 * cancelled when it is still pending. Cancelling a pending promise MUST
 * reject its value with an Exception. It SHOULD clean up any underlying
 * resources and references as applicable.
 *
 * ```php
 * $promise = $connector->connect($uri);
 *
 * $promise->cancel();
 * ```
 *
 * @param string $uri
 * @return \\React\\Promise\\PromiseInterface<ConnectionInterface>
 *     Resolves with a `ConnectionInterface` on success or rejects with an `Exception` on error.
 * @see ConnectionInterface
 */',
        'startLine' => 58,
        'endLine' => 58,
        'startColumn' => 5,
        'endColumn' => 34,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'React\\Socket',
        'declaringClassName' => 'React\\Socket\\ConnectorInterface',
        'implementingClassName' => 'React\\Socket\\ConnectorInterface',
        'currentClassName' => 'React\\Socket\\ConnectorInterface',
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