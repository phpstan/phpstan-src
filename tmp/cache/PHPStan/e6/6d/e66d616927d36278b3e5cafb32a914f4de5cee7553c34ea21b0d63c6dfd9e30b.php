<?php declare(strict_types = 1);

// osfsl-/home/runner/work/phpstan-src/phpstan-src/vendor/composer/../react/socket/src/TcpServer.php-PHPStan\BetterReflection\Reflection\ReflectionClass-React\Socket\TcpServer
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6154b739b4458310d877ca42e37c49e72ef623ec524d238a3b4b22ac339cc7bf-8.4.21-6.70.0.1',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'React\\Socket\\TcpServer',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/vendor/composer/../react/socket/src/TcpServer.php',
      ),
    ),
    'namespace' => 'React\\Socket',
    'name' => 'React\\Socket\\TcpServer',
    'shortName' => 'TcpServer',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * The `TcpServer` class implements the `ServerInterface` and
 * is responsible for accepting plaintext TCP/IP connections.
 *
 * ```php
 * $server = new React\\Socket\\TcpServer(8080);
 * ```
 *
 * Whenever a client connects, it will emit a `connection` event with a connection
 * instance implementing `ConnectionInterface`:
 *
 * ```php
 * $server->on(\'connection\', function (React\\Socket\\ConnectionInterface $connection) {
 *     echo \'Plaintext connection from \' . $connection->getRemoteAddress() . PHP_EOL;
 *     $connection->write(\'hello there!\' . PHP_EOL);
 *     …
 * });
 * ```
 *
 * See also the `ServerInterface` for more details.
 *
 * @see ServerInterface
 * @see ConnectionInterface
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 35,
    'endLine' => 262,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'Evenement\\EventEmitter',
    'implementsClassNames' => 
    array (
      0 => 'React\\Socket\\ServerInterface',
    ),
    'traitClassNames' => 
    array (
    ),
    'immediateConstants' => 
    array (
    ),
    'immediateProperties' => 
    array (
      'master' => 
      array (
        'declaringClassName' => 'React\\Socket\\TcpServer',
        'implementingClassName' => 'React\\Socket\\TcpServer',
        'name' => 'master',
        'modifiers' => 4,
        'type' => NULL,
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 37,
        'endLine' => 37,
        'startColumn' => 5,
        'endColumn' => 20,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'loop' => 
      array (
        'declaringClassName' => 'React\\Socket\\TcpServer',
        'implementingClassName' => 'React\\Socket\\TcpServer',
        'name' => 'loop',
        'modifiers' => 4,
        'type' => NULL,
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 38,
        'endLine' => 38,
        'startColumn' => 5,
        'endColumn' => 18,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'listening' => 
      array (
        'declaringClassName' => 'React\\Socket\\TcpServer',
        'implementingClassName' => 'React\\Socket\\TcpServer',
        'name' => 'listening',
        'modifiers' => 4,
        'type' => NULL,
        'default' => 
        array (
          'code' => 'false',
          'attributes' => 
          array (
            'startLine' => 39,
            'endLine' => 39,
            'startTokenPos' => 66,
            'startFilePos' => 1018,
            'endTokenPos' => 66,
            'endFilePos' => 1022,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 39,
        'endLine' => 39,
        'startColumn' => 5,
        'endColumn' => 31,
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
            'startLine' => 131,
            'endLine' => 131,
            'startColumn' => 33,
            'endColumn' => 36,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'loop' => 
          array (
            'name' => 'loop',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 131,
                'endLine' => 131,
                'startTokenPos' => 84,
                'startFilePos' => 4771,
                'endTokenPos' => 84,
                'endFilePos' => 4774,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 131,
            'endLine' => 131,
            'startColumn' => 39,
            'endColumn' => 50,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
          'context' => 
          array (
            'name' => 'context',
            'default' => 
            array (
              'code' => 'array()',
              'attributes' => 
              array (
                'startLine' => 131,
                'endLine' => 131,
                'startTokenPos' => 93,
                'startFilePos' => 4794,
                'endTokenPos' => 95,
                'endFilePos' => 4800,
              ),
            ),
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
            'startLine' => 131,
            'endLine' => 131,
            'startColumn' => 53,
            'endColumn' => 76,
            'parameterIndex' => 2,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Creates a plaintext TCP/IP socket server and starts listening on the given address
 *
 * This starts accepting new incoming connections on the given address.
 * See also the `connection event` documented in the `ServerInterface`
 * for more details.
 *
 * ```php
 * $server = new React\\Socket\\TcpServer(8080);
 * ```
 *
 * As above, the `$uri` parameter can consist of only a port, in which case the
 * server will default to listening on the localhost address `127.0.0.1`,
 * which means it will not be reachable from outside of this system.
 *
 * In order to use a random port assignment, you can use the port `0`:
 *
 * ```php
 * $server = new React\\Socket\\TcpServer(0);
 * $address = $server->getAddress();
 * ```
 *
 * In order to change the host the socket is listening on, you can provide an IP
 * address through the first parameter provided to the constructor, optionally
 * preceded by the `tcp://` scheme:
 *
 * ```php
 * $server = new React\\Socket\\TcpServer(\'192.168.0.1:8080\');
 * ```
 *
 * If you want to listen on an IPv6 address, you MUST enclose the host in square
 * brackets:
 *
 * ```php
 * $server = new React\\Socket\\TcpServer(\'[::1]:8080\');
 * ```
 *
 * If the given URI is invalid, does not contain a port, any other scheme or if it
 * contains a hostname, it will throw an `InvalidArgumentException`:
 *
 * ```php
 * // throws InvalidArgumentException due to missing port
 * $server = new React\\Socket\\TcpServer(\'127.0.0.1\');
 * ```
 *
 * If the given URI appears to be valid, but listening on it fails (such as if port
 * is already in use or port below 1024 may require root access etc.), it will
 * throw a `RuntimeException`:
 *
 * ```php
 * $first = new React\\Socket\\TcpServer(8080);
 *
 * // throws RuntimeException because port is already in use
 * $second = new React\\Socket\\TcpServer(8080);
 * ```
 *
 * Note that these error conditions may vary depending on your system and/or
 * configuration.
 * See the exception message and code for more details about the actual error
 * condition.
 *
 * This class takes an optional `LoopInterface|null $loop` parameter that can be used to
 * pass the event loop instance to use for this object. You can use a `null` value
 * here in order to use the [default loop](https://github.com/reactphp/event-loop#loop).
 * This value SHOULD NOT be given unless you\'re sure you want to explicitly use a
 * given event loop instance.
 *
 * Optionally, you can specify [socket context options](https://www.php.net/manual/en/context.socket.php)
 * for the underlying stream socket resource like this:
 *
 * ```php
 * $server = new React\\Socket\\TcpServer(\'[::1]:8080\', null, array(
 *     \'backlog\' => 200,
 *     \'so_reuseport\' => true,
 *     \'ipv6_v6only\' => true
 * ));
 * ```
 *
 * Note that available [socket context options](https://www.php.net/manual/en/context.socket.php),
 * their defaults and effects of changing these may vary depending on your system
 * and/or PHP version.
 * Passing unknown context options has no effect.
 * The `backlog` context option defaults to `511` unless given explicitly.
 *
 * @param string|int     $uri
 * @param ?LoopInterface $loop
 * @param array          $context
 * @throws InvalidArgumentException if the listening address is invalid
 * @throws RuntimeException if listening on this address fails (already in use etc.)
 */',
        'startLine' => 131,
        'endLine' => 196,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'React\\Socket',
        'declaringClassName' => 'React\\Socket\\TcpServer',
        'implementingClassName' => 'React\\Socket\\TcpServer',
        'currentClassName' => 'React\\Socket\\TcpServer',
        'aliasName' => NULL,
      ),
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
        'docComment' => NULL,
        'startLine' => 198,
        'endLine' => 213,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'React\\Socket',
        'declaringClassName' => 'React\\Socket\\TcpServer',
        'implementingClassName' => 'React\\Socket\\TcpServer',
        'currentClassName' => 'React\\Socket\\TcpServer',
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
        'docComment' => NULL,
        'startLine' => 215,
        'endLine' => 223,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'React\\Socket',
        'declaringClassName' => 'React\\Socket\\TcpServer',
        'implementingClassName' => 'React\\Socket\\TcpServer',
        'currentClassName' => 'React\\Socket\\TcpServer',
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
        'docComment' => NULL,
        'startLine' => 225,
        'endLine' => 242,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'React\\Socket',
        'declaringClassName' => 'React\\Socket\\TcpServer',
        'implementingClassName' => 'React\\Socket\\TcpServer',
        'currentClassName' => 'React\\Socket\\TcpServer',
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
        'docComment' => NULL,
        'startLine' => 244,
        'endLine' => 253,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'React\\Socket',
        'declaringClassName' => 'React\\Socket\\TcpServer',
        'implementingClassName' => 'React\\Socket\\TcpServer',
        'currentClassName' => 'React\\Socket\\TcpServer',
        'aliasName' => NULL,
      ),
      'handleConnection' => 
      array (
        'name' => 'handleConnection',
        'parameters' => 
        array (
          'socket' => 
          array (
            'name' => 'socket',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 256,
            'endLine' => 256,
            'startColumn' => 38,
            'endColumn' => 44,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/** @internal */',
        'startLine' => 256,
        'endLine' => 261,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'React\\Socket',
        'declaringClassName' => 'React\\Socket\\TcpServer',
        'implementingClassName' => 'React\\Socket\\TcpServer',
        'currentClassName' => 'React\\Socket\\TcpServer',
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