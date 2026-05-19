<?php declare(strict_types = 1);

// osfsl-/home/runner/work/phpstan-src/phpstan-src/vendor/composer/../hoa/stream/./Stream.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Hoa\Stream\Stream
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-a8b05e583c3b01d2597bbe158e61d748182dd738847c3596152df8e4f93f64e5-8.4.21-6.70.0.1',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Hoa\\Stream\\Stream',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/vendor/composer/../hoa/stream/./Stream.php',
      ),
    ),
    'namespace' => 'Hoa\\Stream',
    'name' => 'Hoa\\Stream\\Stream',
    'shortName' => 'Stream',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 64,
    'docComment' => '/**
 * Class \\Hoa\\Stream.
 *
 * Static register for all streams (files, sockets etc.).
 *
 * @copyright  Copyright © 2007-2017 Hoa community
 * @license    New BSD License
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 51,
    'endLine' => 651,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
      0 => 'Hoa\\Stream\\IStream\\Stream',
      1 => 'Hoa\\Event\\Listenable',
    ),
    'traitClassNames' => 
    array (
      0 => 'Hoa\\Event\\Listens',
    ),
    'immediateConstants' => 
    array (
      'NAME' => 
      array (
        'declaringClassName' => 'Hoa\\Stream\\Stream',
        'implementingClassName' => 'Hoa\\Stream\\Stream',
        'name' => 'NAME',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '0',
          'attributes' => 
          array (
            'startLine' => 60,
            'endLine' => 60,
            'startTokenPos' => 54,
            'startFilePos' => 2110,
            'endTokenPos' => 54,
            'endFilePos' => 2110,
          ),
        ),
        'docComment' => '/**
 * Name index in the stream bucket.
 *
 * @const int
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 60,
        'endLine' => 60,
        'startColumn' => 5,
        'endColumn' => 34,
      ),
      'HANDLER' => 
      array (
        'declaringClassName' => 'Hoa\\Stream\\Stream',
        'implementingClassName' => 'Hoa\\Stream\\Stream',
        'name' => 'HANDLER',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '1',
          'attributes' => 
          array (
            'startLine' => 67,
            'endLine' => 67,
            'startTokenPos' => 65,
            'startFilePos' => 2230,
            'endTokenPos' => 65,
            'endFilePos' => 2230,
          ),
        ),
        'docComment' => '/**
 * Handler index in the stream bucket.
 *
 * @const int
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 67,
        'endLine' => 67,
        'startColumn' => 5,
        'endColumn' => 34,
      ),
      'RESOURCE' => 
      array (
        'declaringClassName' => 'Hoa\\Stream\\Stream',
        'implementingClassName' => 'Hoa\\Stream\\Stream',
        'name' => 'RESOURCE',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '2',
          'attributes' => 
          array (
            'startLine' => 74,
            'endLine' => 74,
            'startTokenPos' => 76,
            'startFilePos' => 2351,
            'endTokenPos' => 76,
            'endFilePos' => 2351,
          ),
        ),
        'docComment' => '/**
 * Resource index in the stream bucket.
 *
 * @const int
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 74,
        'endLine' => 74,
        'startColumn' => 5,
        'endColumn' => 34,
      ),
      'CONTEXT' => 
      array (
        'declaringClassName' => 'Hoa\\Stream\\Stream',
        'implementingClassName' => 'Hoa\\Stream\\Stream',
        'name' => 'CONTEXT',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '3',
          'attributes' => 
          array (
            'startLine' => 81,
            'endLine' => 81,
            'startTokenPos' => 87,
            'startFilePos' => 2471,
            'endTokenPos' => 87,
            'endFilePos' => 2471,
          ),
        ),
        'docComment' => '/**
 * Context index in the stream bucket.
 *
 * @const int
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 81,
        'endLine' => 81,
        'startColumn' => 5,
        'endColumn' => 34,
      ),
      'DEFAULT_BUFFER_SIZE' => 
      array (
        'declaringClassName' => 'Hoa\\Stream\\Stream',
        'implementingClassName' => 'Hoa\\Stream\\Stream',
        'name' => 'DEFAULT_BUFFER_SIZE',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '8192',
          'attributes' => 
          array (
            'startLine' => 88,
            'endLine' => 88,
            'startTokenPos' => 98,
            'startFilePos' => 2576,
            'endTokenPos' => 98,
            'endFilePos' => 2579,
          ),
        ),
        'docComment' => '/**
 * Default buffer size.
 *
 * @const int
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 88,
        'endLine' => 88,
        'startColumn' => 5,
        'endColumn' => 37,
      ),
    ),
    'immediateProperties' => 
    array (
      '_bucket' => 
      array (
        'declaringClassName' => 'Hoa\\Stream\\Stream',
        'implementingClassName' => 'Hoa\\Stream\\Stream',
        'name' => '_bucket',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[]',
          'attributes' => 
          array (
            'startLine' => 95,
            'endLine' => 95,
            'startTokenPos' => 109,
            'startFilePos' => 2688,
            'endTokenPos' => 110,
            'endFilePos' => 2689,
          ),
        ),
        'docComment' => '/**
 * Current stream bucket.
 *
 * @var array
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 95,
        'endLine' => 95,
        'startColumn' => 5,
        'endColumn' => 37,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      '_register' => 
      array (
        'declaringClassName' => 'Hoa\\Stream\\Stream',
        'implementingClassName' => 'Hoa\\Stream\\Stream',
        'name' => '_register',
        'modifiers' => 20,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[]',
          'attributes' => 
          array (
            'startLine' => 102,
            'endLine' => 102,
            'startTokenPos' => 123,
            'startFilePos' => 2799,
            'endTokenPos' => 124,
            'endFilePos' => 2800,
          ),
        ),
        'docComment' => '/**
 * Static stream register.
 *
 * @var array
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 102,
        'endLine' => 102,
        'startColumn' => 5,
        'endColumn' => 37,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      '_bufferSize' => 
      array (
        'declaringClassName' => 'Hoa\\Stream\\Stream',
        'implementingClassName' => 'Hoa\\Stream\\Stream',
        'name' => '_bufferSize',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => 'self::DEFAULT_BUFFER_SIZE',
          'attributes' => 
          array (
            'startLine' => 109,
            'endLine' => 109,
            'startTokenPos' => 135,
            'startFilePos' => 2915,
            'endTokenPos' => 137,
            'endFilePos' => 2939,
          ),
        ),
        'docComment' => '/**
 * Buffer size (default is 8Ko).
 *
 * @var bool
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 109,
        'endLine' => 109,
        'startColumn' => 5,
        'endColumn' => 60,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      '_streamName' => 
      array (
        'declaringClassName' => 'Hoa\\Stream\\Stream',
        'implementingClassName' => 'Hoa\\Stream\\Stream',
        'name' => '_streamName',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => 'null',
          'attributes' => 
          array (
            'startLine' => 116,
            'endLine' => 116,
            'startTokenPos' => 148,
            'startFilePos' => 3081,
            'endTokenPos' => 148,
            'endFilePos' => 3084,
          ),
        ),
        'docComment' => '/**
 * Original stream name, given to the stream constructor.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 116,
        'endLine' => 116,
        'startColumn' => 5,
        'endColumn' => 39,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      '_context' => 
      array (
        'declaringClassName' => 'Hoa\\Stream\\Stream',
        'implementingClassName' => 'Hoa\\Stream\\Stream',
        'name' => '_context',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => 'null',
          'attributes' => 
          array (
            'startLine' => 123,
            'endLine' => 123,
            'startTokenPos' => 159,
            'startFilePos' => 3185,
            'endTokenPos' => 159,
            'endFilePos' => 3188,
          ),
        ),
        'docComment' => '/**
 * Context name.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 123,
        'endLine' => 123,
        'startColumn' => 5,
        'endColumn' => 39,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      '_hasBeenDeferred' => 
      array (
        'declaringClassName' => 'Hoa\\Stream\\Stream',
        'implementingClassName' => 'Hoa\\Stream\\Stream',
        'name' => '_hasBeenDeferred',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => 'false',
          'attributes' => 
          array (
            'startLine' => 130,
            'endLine' => 130,
            'startTokenPos' => 170,
            'startFilePos' => 3312,
            'endTokenPos' => 170,
            'endFilePos' => 3316,
          ),
        ),
        'docComment' => '/**
 * Whether the opening has been deferred.
 *
 * @var bool
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 130,
        'endLine' => 130,
        'startColumn' => 5,
        'endColumn' => 40,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      '_borrowing' => 
      array (
        'declaringClassName' => 'Hoa\\Stream\\Stream',
        'implementingClassName' => 'Hoa\\Stream\\Stream',
        'name' => '_borrowing',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => 'false',
          'attributes' => 
          array (
            'startLine' => 137,
            'endLine' => 137,
            'startTokenPos' => 181,
            'startFilePos' => 3459,
            'endTokenPos' => 181,
            'endFilePos' => 3463,
          ),
        ),
        'docComment' => '/**
 * Whether this stream is already opened by another handler.
 *
 * @var bool
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 137,
        'endLine' => 137,
        'startColumn' => 5,
        'endColumn' => 40,
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
          'streamName' => 
          array (
            'name' => 'streamName',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 151,
            'endLine' => 151,
            'startColumn' => 33,
            'endColumn' => 43,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'context' => 
          array (
            'name' => 'context',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 151,
                'endLine' => 151,
                'startTokenPos' => 199,
                'startFilePos' => 3978,
                'endTokenPos' => 199,
                'endFilePos' => 3981,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 151,
            'endLine' => 151,
            'startColumn' => 46,
            'endColumn' => 60,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
          'wait' => 
          array (
            'name' => 'wait',
            'default' => 
            array (
              'code' => 'false',
              'attributes' => 
              array (
                'startLine' => 151,
                'endLine' => 151,
                'startTokenPos' => 206,
                'startFilePos' => 3992,
                'endTokenPos' => 206,
                'endFilePos' => 3996,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 151,
            'endLine' => 151,
            'startColumn' => 63,
            'endColumn' => 75,
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
 * Set the current stream.
 * If not exists in the register, try to call the
 * `$this->_open()` method. Please, see the `self::_getStream()` method.
 *
 * @param   string  $streamName    Stream name (e.g. path or URL).
 * @param   string  $context       Context ID (please, see the
 *                                 `Hoa\\Stream\\Context` class).
 * @param   bool    $wait          Differ opening or not.
 */',
        'startLine' => 151,
        'endLine' => 181,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Hoa\\Stream',
        'declaringClassName' => 'Hoa\\Stream\\Stream',
        'implementingClassName' => 'Hoa\\Stream\\Stream',
        'currentClassName' => 'Hoa\\Stream\\Stream',
        'aliasName' => NULL,
      ),
      '_getStream' => 
      array (
        'name' => '_getStream',
        'parameters' => 
        array (
          'streamName' => 
          array (
            'name' => 'streamName',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 196,
            'endLine' => 196,
            'startColumn' => 9,
            'endColumn' => 19,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'handler' => 
          array (
            'name' => 'handler',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Hoa\\Stream\\Stream',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 197,
            'endLine' => 197,
            'startColumn' => 9,
            'endColumn' => 23,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'context' => 
          array (
            'name' => 'context',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 198,
                'endLine' => 198,
                'startTokenPos' => 342,
                'startFilePos' => 5294,
                'endTokenPos' => 342,
                'endFilePos' => 5297,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 198,
            'endLine' => 198,
            'startColumn' => 9,
            'endColumn' => 23,
            'parameterIndex' => 2,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => true,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get a stream in the register.
 * If the stream does not exist, try to open it by calling the
 * $handler->_open() method.
 *
 * @param   string       $streamName    Stream name.
 * @param   \\Hoa\\Stream  $handler       Stream handler.
 * @param   string       $context       Context ID (please, see the
 *                                      \\Hoa\\Stream\\Context class).
 * @return  array
 * @throws  \\Hoa\\Stream\\Exception
 */',
        'startLine' => 195,
        'endLine' => 241,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 20,
        'namespace' => 'Hoa\\Stream',
        'declaringClassName' => 'Hoa\\Stream\\Stream',
        'implementingClassName' => 'Hoa\\Stream\\Stream',
        'currentClassName' => 'Hoa\\Stream\\Stream',
        'aliasName' => NULL,
      ),
      '_open' => 
      array (
        'name' => '_open',
        'parameters' => 
        array (
          'streamName' => 
          array (
            'name' => 'streamName',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 253,
            'endLine' => 253,
            'startColumn' => 40,
            'endColumn' => 50,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'context' => 
          array (
            'name' => 'context',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 253,
                'endLine' => 253,
                'startTokenPos' => 638,
                'startFilePos' => 7129,
                'endTokenPos' => 638,
                'endFilePos' => 7132,
              ),
            ),
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionUnionType',
              'data' => 
              array (
                'types' => 
                array (
                  0 => 
                  array (
                    'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                    'data' => 
                    array (
                      'name' => 'Hoa\\Stream\\Context',
                      'isIdentifier' => false,
                    ),
                  ),
                  1 => 
                  array (
                    'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                    'data' => 
                    array (
                      'name' => 'null',
                      'isIdentifier' => true,
                    ),
                  ),
                ),
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 253,
            'endLine' => 253,
            'startColumn' => 53,
            'endColumn' => 76,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => true,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Open the stream and return the associated resource.
 * Note: This method is protected, but do not forget that it could be
 * overloaded into a public context.
 *
 * @param   string               $streamName    Stream name (e.g. path or URL).
 * @param   \\Hoa\\Stream\\Context  $context       Context.
 * @return  resource
 * @throws  \\Hoa\\Exception\\Exception
 */',
        'startLine' => 253,
        'endLine' => 253,
        'startColumn' => 5,
        'endColumn' => 78,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 66,
        'namespace' => 'Hoa\\Stream',
        'declaringClassName' => 'Hoa\\Stream\\Stream',
        'implementingClassName' => 'Hoa\\Stream\\Stream',
        'currentClassName' => 'Hoa\\Stream\\Stream',
        'aliasName' => NULL,
      ),
      '_close' => 
      array (
        'name' => '_close',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Close the current stream.
 * Note: this method is protected, but do not forget that it could be
 * overloaded into a public context.
 *
 * @return  bool
 */',
        'startLine' => 262,
        'endLine' => 262,
        'startColumn' => 5,
        'endColumn' => 41,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 66,
        'namespace' => 'Hoa\\Stream',
        'declaringClassName' => 'Hoa\\Stream\\Stream',
        'implementingClassName' => 'Hoa\\Stream\\Stream',
        'currentClassName' => 'Hoa\\Stream\\Stream',
        'aliasName' => NULL,
      ),
      'open' => 
      array (
        'name' => 'open',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Open the stream.
 *
 * @return  \\Hoa\\Stream
 * @throws  \\Hoa\\Stream\\Exception
 */',
        'startLine' => 270,
        'endLine' => 301,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 33,
        'namespace' => 'Hoa\\Stream',
        'declaringClassName' => 'Hoa\\Stream\\Stream',
        'implementingClassName' => 'Hoa\\Stream\\Stream',
        'currentClassName' => 'Hoa\\Stream\\Stream',
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
 * Close the current stream.
 *
 * @return  void
 */',
        'startLine' => 308,
        'endLine' => 337,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 33,
        'namespace' => 'Hoa\\Stream',
        'declaringClassName' => 'Hoa\\Stream\\Stream',
        'implementingClassName' => 'Hoa\\Stream\\Stream',
        'currentClassName' => 'Hoa\\Stream\\Stream',
        'aliasName' => NULL,
      ),
      'getStreamName' => 
      array (
        'name' => 'getStreamName',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the current stream name.
 *
 * @return  string
 */',
        'startLine' => 344,
        'endLine' => 351,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Hoa\\Stream',
        'declaringClassName' => 'Hoa\\Stream\\Stream',
        'implementingClassName' => 'Hoa\\Stream\\Stream',
        'currentClassName' => 'Hoa\\Stream\\Stream',
        'aliasName' => NULL,
      ),
      'getStream' => 
      array (
        'name' => 'getStream',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the current stream.
 *
 * @return  resource
 */',
        'startLine' => 358,
        'endLine' => 365,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Hoa\\Stream',
        'declaringClassName' => 'Hoa\\Stream\\Stream',
        'implementingClassName' => 'Hoa\\Stream\\Stream',
        'currentClassName' => 'Hoa\\Stream\\Stream',
        'aliasName' => NULL,
      ),
      'getStreamContext' => 
      array (
        'name' => 'getStreamContext',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the current stream context.
 *
 * @return  \\Hoa\\Stream\\Context
 */',
        'startLine' => 372,
        'endLine' => 379,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Hoa\\Stream',
        'declaringClassName' => 'Hoa\\Stream\\Stream',
        'implementingClassName' => 'Hoa\\Stream\\Stream',
        'currentClassName' => 'Hoa\\Stream\\Stream',
        'aliasName' => NULL,
      ),
      'getStreamHandler' => 
      array (
        'name' => 'getStreamHandler',
        'parameters' => 
        array (
          'streamName' => 
          array (
            'name' => 'streamName',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 387,
            'endLine' => 387,
            'startColumn' => 45,
            'endColumn' => 55,
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
 * Get stream handler according to its name.
 *
 * @param   string  $streamName    Stream name.
 * @return  \\Hoa\\Stream
 */',
        'startLine' => 387,
        'endLine' => 396,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Hoa\\Stream',
        'declaringClassName' => 'Hoa\\Stream\\Stream',
        'implementingClassName' => 'Hoa\\Stream\\Stream',
        'currentClassName' => 'Hoa\\Stream\\Stream',
        'aliasName' => NULL,
      ),
      '_setStream' => 
      array (
        'name' => '_setStream',
        'parameters' => 
        array (
          'stream' => 
          array (
            'name' => 'stream',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 407,
            'endLine' => 407,
            'startColumn' => 32,
            'endColumn' => 38,
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
 * Set the current stream. Useful to manage a stack of streams (e.g. socket
 * and select). Notice that it could be unsafe to use this method without
 * taking time to think about it two minutes. Resource of type “Unknown” is
 * considered as valid.
 *
 * @return  resource
 * @throws  \\Hoa\\Stream\\Exception
 */',
        'startLine' => 407,
        'endLine' => 424,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Hoa\\Stream',
        'declaringClassName' => 'Hoa\\Stream\\Stream',
        'implementingClassName' => 'Hoa\\Stream\\Stream',
        'currentClassName' => 'Hoa\\Stream\\Stream',
        'aliasName' => NULL,
      ),
      'isOpened' => 
      array (
        'name' => 'isOpened',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Check if the stream is opened.
 *
 * @return  bool
 */',
        'startLine' => 431,
        'endLine' => 434,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Hoa\\Stream',
        'declaringClassName' => 'Hoa\\Stream\\Stream',
        'implementingClassName' => 'Hoa\\Stream\\Stream',
        'currentClassName' => 'Hoa\\Stream\\Stream',
        'aliasName' => NULL,
      ),
      'setStreamTimeout' => 
      array (
        'name' => 'setStreamTimeout',
        'parameters' => 
        array (
          'seconds' => 
          array (
            'name' => 'seconds',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 443,
            'endLine' => 443,
            'startColumn' => 38,
            'endColumn' => 45,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'microseconds' => 
          array (
            'name' => 'microseconds',
            'default' => 
            array (
              'code' => '0',
              'attributes' => 
              array (
                'startLine' => 443,
                'endLine' => 443,
                'startTokenPos' => 1405,
                'startFilePos' => 11839,
                'endTokenPos' => 1405,
                'endFilePos' => 11839,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 443,
            'endLine' => 443,
            'startColumn' => 48,
            'endColumn' => 64,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Set the timeout period.
 *
 * @param   int     $seconds         Timeout period in seconds.
 * @param   int     $microseconds    Timeout period in microseconds.
 * @return  bool
 */',
        'startLine' => 443,
        'endLine' => 446,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Hoa\\Stream',
        'declaringClassName' => 'Hoa\\Stream\\Stream',
        'implementingClassName' => 'Hoa\\Stream\\Stream',
        'currentClassName' => 'Hoa\\Stream\\Stream',
        'aliasName' => NULL,
      ),
      'hasBeenDeferred' => 
      array (
        'name' => 'hasBeenDeferred',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Whether the opening of the stream has been deferred
 */',
        'startLine' => 451,
        'endLine' => 454,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Hoa\\Stream',
        'declaringClassName' => 'Hoa\\Stream\\Stream',
        'implementingClassName' => 'Hoa\\Stream\\Stream',
        'currentClassName' => 'Hoa\\Stream\\Stream',
        'aliasName' => NULL,
      ),
      'hasTimedOut' => 
      array (
        'name' => 'hasTimedOut',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Check whether the connection has timed out or not.
 * This is basically a shortcut of `getStreamMetaData` + the `timed_out`
 * index, but the resulting code is more readable.
 *
 * @return bool
 */',
        'startLine' => 463,
        'endLine' => 468,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Hoa\\Stream',
        'declaringClassName' => 'Hoa\\Stream\\Stream',
        'implementingClassName' => 'Hoa\\Stream\\Stream',
        'currentClassName' => 'Hoa\\Stream\\Stream',
        'aliasName' => NULL,
      ),
      'setStreamBlocking' => 
      array (
        'name' => 'setStreamBlocking',
        'parameters' => 
        array (
          'mode' => 
          array (
            'name' => 'mode',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 476,
            'endLine' => 476,
            'startColumn' => 39,
            'endColumn' => 43,
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
 * Set blocking/non-blocking mode.
 *
 * @param   bool    $mode    Blocking mode.
 * @return  bool
 */',
        'startLine' => 476,
        'endLine' => 479,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Hoa\\Stream',
        'declaringClassName' => 'Hoa\\Stream\\Stream',
        'implementingClassName' => 'Hoa\\Stream\\Stream',
        'currentClassName' => 'Hoa\\Stream\\Stream',
        'aliasName' => NULL,
      ),
      'setStreamBuffer' => 
      array (
        'name' => 'setStreamBuffer',
        'parameters' => 
        array (
          'buffer' => 
          array (
            'name' => 'buffer',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 495,
            'endLine' => 495,
            'startColumn' => 37,
            'endColumn' => 43,
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
 * Set stream buffer.
 * Output using fwrite() (or similar function) is normally buffered at 8 Ko.
 * This means that if there are two processes wanting to write to the same
 * output stream, each is paused after 8 Ko of data to allow the other to
 * write.
 *
 * @param   int     $buffer    Number of bytes to buffer. If zero, write
 *                             operations are unbuffered. This ensures that
 *                             all writes are completed before other
 *                             processes are allowed to write to that output
 *                             stream.
 * @return  bool
 */',
        'startLine' => 495,
        'endLine' => 505,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Hoa\\Stream',
        'declaringClassName' => 'Hoa\\Stream\\Stream',
        'implementingClassName' => 'Hoa\\Stream\\Stream',
        'currentClassName' => 'Hoa\\Stream\\Stream',
        'aliasName' => NULL,
      ),
      'disableStreamBuffer' => 
      array (
        'name' => 'disableStreamBuffer',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Disable stream buffering.
 * Alias of $this->setBuffer(0).
 *
 * @return  bool
 */',
        'startLine' => 513,
        'endLine' => 516,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Hoa\\Stream',
        'declaringClassName' => 'Hoa\\Stream\\Stream',
        'implementingClassName' => 'Hoa\\Stream\\Stream',
        'currentClassName' => 'Hoa\\Stream\\Stream',
        'aliasName' => NULL,
      ),
      'getStreamBufferSize' => 
      array (
        'name' => 'getStreamBufferSize',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get stream buffer size.
 *
 * @return  int
 */',
        'startLine' => 523,
        'endLine' => 526,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Hoa\\Stream',
        'declaringClassName' => 'Hoa\\Stream\\Stream',
        'implementingClassName' => 'Hoa\\Stream\\Stream',
        'currentClassName' => 'Hoa\\Stream\\Stream',
        'aliasName' => NULL,
      ),
      'getStreamWrapperName' => 
      array (
        'name' => 'getStreamWrapperName',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get stream wrapper name.
 *
 * @return  string
 */',
        'startLine' => 533,
        'endLine' => 540,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Hoa\\Stream',
        'declaringClassName' => 'Hoa\\Stream\\Stream',
        'implementingClassName' => 'Hoa\\Stream\\Stream',
        'currentClassName' => 'Hoa\\Stream\\Stream',
        'aliasName' => NULL,
      ),
      'getStreamMetaData' => 
      array (
        'name' => 'getStreamMetaData',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get stream meta data.
 *
 * @return  array
 */',
        'startLine' => 547,
        'endLine' => 550,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Hoa\\Stream',
        'declaringClassName' => 'Hoa\\Stream\\Stream',
        'implementingClassName' => 'Hoa\\Stream\\Stream',
        'currentClassName' => 'Hoa\\Stream\\Stream',
        'aliasName' => NULL,
      ),
      'isBorrowing' => 
      array (
        'name' => 'isBorrowing',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Whether this stream is already opened by another handler.
 *
 * @return  bool
 */',
        'startLine' => 557,
        'endLine' => 560,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Hoa\\Stream',
        'declaringClassName' => 'Hoa\\Stream\\Stream',
        'implementingClassName' => 'Hoa\\Stream\\Stream',
        'currentClassName' => 'Hoa\\Stream\\Stream',
        'aliasName' => NULL,
      ),
      '_notify' => 
      array (
        'name' => '_notify',
        'parameters' => 
        array (
          'ncode' => 
          array (
            'name' => 'ncode',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 577,
            'endLine' => 577,
            'startColumn' => 9,
            'endColumn' => 14,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'severity' => 
          array (
            'name' => 'severity',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 578,
            'endLine' => 578,
            'startColumn' => 9,
            'endColumn' => 17,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
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
            'startLine' => 579,
            'endLine' => 579,
            'startColumn' => 9,
            'endColumn' => 16,
            'parameterIndex' => 2,
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
            'startLine' => 580,
            'endLine' => 580,
            'startColumn' => 9,
            'endColumn' => 13,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
          'transferred' => 
          array (
            'name' => 'transferred',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 581,
            'endLine' => 581,
            'startColumn' => 9,
            'endColumn' => 20,
            'parameterIndex' => 4,
            'isOptional' => false,
          ),
          'max' => 
          array (
            'name' => 'max',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 582,
            'endLine' => 582,
            'startColumn' => 9,
            'endColumn' => 12,
            'parameterIndex' => 5,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Notification callback.
 *
 * @param   int     $ncode          Notification code. Please, see
 *                                  STREAM_NOTIFY_* constants.
 * @param   int     $severity       Severity. Please, see
 *                                  STREAM_NOTIFY_SEVERITY_* constants.
 * @param   string  $message        Message.
 * @param   int     $code           Message code.
 * @param   int     $transferred    If applicable, the number of transferred
 *                                  bytes.
 * @param   int     $max            If applicable, the number of max bytes.
 * @return  void
 */',
        'startLine' => 576,
        'endLine' => 606,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Hoa\\Stream',
        'declaringClassName' => 'Hoa\\Stream\\Stream',
        'implementingClassName' => 'Hoa\\Stream\\Stream',
        'currentClassName' => 'Hoa\\Stream\\Stream',
        'aliasName' => NULL,
      ),
      '_Hoa_Stream' => 
      array (
        'name' => '_Hoa_Stream',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Call the $handler->close() method on each stream in the static stream
 * register.
 * This method does not check the return value of $handler->close(). Thus,
 * if a stream is persistent, the $handler->close() should do anything. It
 * is a very generic method.
 *
 * @return  void
 */',
        'startLine' => 617,
        'endLine' => 624,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 49,
        'namespace' => 'Hoa\\Stream',
        'declaringClassName' => 'Hoa\\Stream\\Stream',
        'implementingClassName' => 'Hoa\\Stream\\Stream',
        'currentClassName' => 'Hoa\\Stream\\Stream',
        'aliasName' => NULL,
      ),
      '__toString' => 
      array (
        'name' => '__toString',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Transform object to string.
 *
 * @return  string
 */',
        'startLine' => 631,
        'endLine' => 634,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Hoa\\Stream',
        'declaringClassName' => 'Hoa\\Stream\\Stream',
        'implementingClassName' => 'Hoa\\Stream\\Stream',
        'currentClassName' => 'Hoa\\Stream\\Stream',
        'aliasName' => NULL,
      ),
      '__destruct' => 
      array (
        'name' => '__destruct',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Close the stream when destructing.
 *
 * @return  void
 */',
        'startLine' => 641,
        'endLine' => 650,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Hoa\\Stream',
        'declaringClassName' => 'Hoa\\Stream\\Stream',
        'implementingClassName' => 'Hoa\\Stream\\Stream',
        'currentClassName' => 'Hoa\\Stream\\Stream',
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