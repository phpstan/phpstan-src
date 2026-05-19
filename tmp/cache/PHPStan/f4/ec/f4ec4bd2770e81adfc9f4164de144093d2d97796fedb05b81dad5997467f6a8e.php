<?php declare(strict_types = 1);

// osfsl-/home/runner/work/phpstan-src/phpstan-src/vendor/composer/../react/socket/src/ServerInterface.php-PHPStan\BetterReflection\Reflection\ReflectionClass-React\Socket\ServerInterface
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-1f36351870e38da63c7bdc49b46595cb39efa3cd4ba74404bf94c868dda7ac59-8.4.21-6.70.0.1',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'React\\Socket\\ServerInterface',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/vendor/composer/../react/socket/src/ServerInterface.php',
      ),
    ),
    'namespace' => 'React\\Socket',
    'name' => 'React\\Socket\\ServerInterface',
    'shortName' => 'ServerInterface',
    'isInterface' => true,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * The `ServerInterface` is responsible for providing an interface for accepting
 * incoming streaming connections, such as a normal TCP/IP connection.
 *
 * Most higher-level components (such as a HTTP server) accept an instance
 * implementing this interface to accept incoming streaming connections.
 * This is usually done via dependency injection, so it\'s fairly simple to actually
 * swap this implementation against any other implementation of this interface.
 * This means that you SHOULD typehint against this interface instead of a concrete
 * implementation of this interface.
 *
 * Besides defining a few methods, this interface also implements the
 * `EventEmitterInterface` which allows you to react to certain events:
 *
 * connection event:
 *     The `connection` event will be emitted whenever a new connection has been
 *     established, i.e. a new client connects to this server socket:
 *
 *     ```php
 *     $socket->on(\'connection\', function (React\\Socket\\ConnectionInterface $connection) {
 *         echo \'new connection\' . PHP_EOL;
 *     });
 *     ```
 *
 *     See also the `ConnectionInterface` for more details about handling the
 *     incoming connection.
 *
 * error event:
 *     The `error` event will be emitted whenever there\'s an error accepting a new
 *     connection from a client.
 *
 *     ```php
 *     $socket->on(\'error\', function (Exception $e) {
 *         echo \'error: \' . $e->getMessage() . PHP_EOL;
 *     });
 *     ```
 *
 *     Note that this is not a fatal error event, i.e. the server keeps listening for
 *     new connections even after this event.
 *
 * @see ConnectionInterface
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 49,
    'endLine' => 151,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
      0 => 'Evenement\\EventEmitterInterface',
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
      'getAddress' => 
      array (
        'name' => 'getAddress',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Returns the full address (URI) this server is currently listening on
 *
 * ```php
 * $address = $socket->getAddress();
 * echo \'Server listening on \' . $address . PHP_EOL;
 * ```
 *
 * If the address can not be determined or is unknown at this time (such as
 * after the socket has been closed), it MAY return a `NULL` value instead.
 *
 * Otherwise, it will return the full address (URI) as a string value, such
 * as `tcp://127.0.0.1:8080`, `tcp://[::1]:80` or `tls://127.0.0.1:443`.
 * Note that individual URI components are application specific and depend
 * on the underlying transport protocol.
 *
 * If this is a TCP/IP based server and you only want the local port, you may
 * use something like this:
 *
 * ```php
 * $address = $socket->getAddress();
 * $port = parse_url($address, PHP_URL_PORT);
 * echo \'Server listening on port \' . $port . PHP_EOL;
 * ```
 *
 * @return ?string the full listening address (URI) or NULL if it is unknown (not applicable to this server socket or already closed)
 */',
        'startLine' => 78,
        'endLine' => 78,
        'startColumn' => 5,
        'endColumn' => 33,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'React\\Socket',
        'declaringClassName' => 'React\\Socket\\ServerInterface',
        'implementingClassName' => 'React\\Socket\\ServerInterface',
        'currentClassName' => 'React\\Socket\\ServerInterface',
        'aliasName' => NULL,
      ),
      'pause' => 
      array (
        'name' => 'pause',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Pauses accepting new incoming connections.
 *
 * Removes the socket resource from the EventLoop and thus stop accepting
 * new connections. Note that the listening socket stays active and is not
 * closed.
 *
 * This means that new incoming connections will stay pending in the
 * operating system backlog until its configurable backlog is filled.
 * Once the backlog is filled, the operating system may reject further
 * incoming connections until the backlog is drained again by resuming
 * to accept new connections.
 *
 * Once the server is paused, no futher `connection` events SHOULD
 * be emitted.
 *
 * ```php
 * $socket->pause();
 *
 * $socket->on(\'connection\', assertShouldNeverCalled());
 * ```
 *
 * This method is advisory-only, though generally not recommended, the
 * server MAY continue emitting `connection` events.
 *
 * Unless otherwise noted, a successfully opened server SHOULD NOT start
 * in paused state.
 *
 * You can continue processing events by calling `resume()` again.
 *
 * Note that both methods can be called any number of times, in particular
 * calling `pause()` more than once SHOULD NOT have any effect.
 * Similarly, calling this after `close()` is a NO-OP.
 *
 * @see self::resume()
 * @return void
 */',
        'startLine' => 117,
        'endLine' => 117,
        'startColumn' => 5,
        'endColumn' => 28,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'React\\Socket',
        'declaringClassName' => 'React\\Socket\\ServerInterface',
        'implementingClassName' => 'React\\Socket\\ServerInterface',
        'currentClassName' => 'React\\Socket\\ServerInterface',
        'aliasName' => NULL,
      ),
      'resume' => 
      array (
        'name' => 'resume',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Resumes accepting new incoming connections.
 *
 * Re-attach the socket resource to the EventLoop after a previous `pause()`.
 *
 * ```php
 * $socket->pause();
 *
 * Loop::addTimer(1.0, function () use ($socket) {
 *     $socket->resume();
 * });
 * ```
 *
 * Note that both methods can be called any number of times, in particular
 * calling `resume()` without a prior `pause()` SHOULD NOT have any effect.
 * Similarly, calling this after `close()` is a NO-OP.
 *
 * @see self::pause()
 * @return void
 */',
        'startLine' => 139,
        'endLine' => 139,
        'startColumn' => 5,
        'endColumn' => 29,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'React\\Socket',
        'declaringClassName' => 'React\\Socket\\ServerInterface',
        'implementingClassName' => 'React\\Socket\\ServerInterface',
        'currentClassName' => 'React\\Socket\\ServerInterface',
        'aliasName' => NULL,
      ),
      'close' => 
      array (
        'name' => 'close',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Shuts down this listening socket
 *
 * This will stop listening for new incoming connections on this socket.
 *
 * Calling this method more than once on the same instance is a NO-OP.
 *
 * @return void
 */',
        'startLine' => 150,
        'endLine' => 150,
        'startColumn' => 5,
        'endColumn' => 28,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'React\\Socket',
        'declaringClassName' => 'React\\Socket\\ServerInterface',
        'implementingClassName' => 'React\\Socket\\ServerInterface',
        'currentClassName' => 'React\\Socket\\ServerInterface',
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