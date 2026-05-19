<?php declare(strict_types = 1);

// osfsl-/home/runner/work/phpstan-src/phpstan-src/vendor/composer/../hoa/file/./File.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Hoa\File\File
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-bd261038c3dd674b592021e2129a6dda22a98260e715d53b941d0405a38d72c0-8.4.21-6.70.0.1',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Hoa\\File\\File',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/vendor/composer/../hoa/file/./File.php',
      ),
    ),
    'namespace' => 'Hoa\\File',
    'name' => 'Hoa\\File\\File',
    'shortName' => 'File',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 64,
    'docComment' => '/**
 * Class \\Hoa\\File.
 *
 * File handler.
 *
 * @copyright  Copyright © 2007-2017 Hoa community
 * @license    New BSD License
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 50,
    'endLine' => 369,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'Hoa\\File\\Generic',
    'implementsClassNames' => 
    array (
      0 => 'Hoa\\Stream\\IStream\\Bufferable',
      1 => 'Hoa\\Stream\\IStream\\Lockable',
      2 => 'Hoa\\Stream\\IStream\\Pointable',
    ),
    'traitClassNames' => 
    array (
    ),
    'immediateConstants' => 
    array (
      'MODE_READ' => 
      array (
        'declaringClassName' => 'Hoa\\File\\File',
        'implementingClassName' => 'Hoa\\File\\File',
        'name' => 'MODE_READ',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'rb\'',
          'attributes' => 
          array (
            'startLine' => 62,
            'endLine' => 62,
            'startTokenPos' => 51,
            'startFilePos' => 2181,
            'endTokenPos' => 51,
            'endFilePos' => 2184,
          ),
        ),
        'docComment' => '/**
 * Open for reading only; place the file pointer at the beginning of the
 * file.
 *
 * @const string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 62,
        'endLine' => 62,
        'startColumn' => 5,
        'endColumn' => 42,
      ),
      'MODE_READ_WRITE' => 
      array (
        'declaringClassName' => 'Hoa\\File\\File',
        'implementingClassName' => 'Hoa\\File\\File',
        'name' => 'MODE_READ_WRITE',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'r+b\'',
          'attributes' => 
          array (
            'startLine' => 70,
            'endLine' => 70,
            'startTokenPos' => 62,
            'startFilePos' => 2366,
            'endTokenPos' => 62,
            'endFilePos' => 2370,
          ),
        ),
        'docComment' => '/**
 * Open for reading and writing; place the file pointer at the beginning of
 * the file.
 *
 * @const string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 70,
        'endLine' => 70,
        'startColumn' => 5,
        'endColumn' => 43,
      ),
      'MODE_TRUNCATE_WRITE' => 
      array (
        'declaringClassName' => 'Hoa\\File\\File',
        'implementingClassName' => 'Hoa\\File\\File',
        'name' => 'MODE_TRUNCATE_WRITE',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'wb\'',
          'attributes' => 
          array (
            'startLine' => 79,
            'endLine' => 79,
            'startTokenPos' => 73,
            'startFilePos' => 2639,
            'endTokenPos' => 73,
            'endFilePos' => 2642,
          ),
        ),
        'docComment' => '/**
 * Open for writing only; place the file pointer at the beginning of the
 * file and truncate the file to zero length. If the file does not exist,
 * attempt to create it.
 *
 * @const string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 79,
        'endLine' => 79,
        'startColumn' => 5,
        'endColumn' => 42,
      ),
      'MODE_TRUNCATE_READ_WRITE' => 
      array (
        'declaringClassName' => 'Hoa\\File\\File',
        'implementingClassName' => 'Hoa\\File\\File',
        'name' => 'MODE_TRUNCATE_READ_WRITE',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'w+b\'',
          'attributes' => 
          array (
            'startLine' => 88,
            'endLine' => 88,
            'startTokenPos' => 84,
            'startFilePos' => 2918,
            'endTokenPos' => 84,
            'endFilePos' => 2922,
          ),
        ),
        'docComment' => '/**
 * Open for reading and writing; place the file pointer at the beginning of
 * the file and truncate the file to zero length. If the file does not
 * exist, attempt to create it.
 *
 * @const string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 88,
        'endLine' => 88,
        'startColumn' => 5,
        'endColumn' => 43,
      ),
      'MODE_APPEND_WRITE' => 
      array (
        'declaringClassName' => 'Hoa\\File\\File',
        'implementingClassName' => 'Hoa\\File\\File',
        'name' => 'MODE_APPEND_WRITE',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'ab\'',
          'attributes' => 
          array (
            'startLine' => 96,
            'endLine' => 96,
            'startTokenPos' => 95,
            'startFilePos' => 3141,
            'endTokenPos' => 95,
            'endFilePos' => 3144,
          ),
        ),
        'docComment' => '/**
 * Open for writing only; place the file pointer at the end of the file. If
 * the file does not exist, attempt to create it.
 *
 * @const string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 96,
        'endLine' => 96,
        'startColumn' => 5,
        'endColumn' => 42,
      ),
      'MODE_APPEND_READ_WRITE' => 
      array (
        'declaringClassName' => 'Hoa\\File\\File',
        'implementingClassName' => 'Hoa\\File\\File',
        'name' => 'MODE_APPEND_READ_WRITE',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'a+b\'',
          'attributes' => 
          array (
            'startLine' => 104,
            'endLine' => 104,
            'startTokenPos' => 106,
            'startFilePos' => 3370,
            'endTokenPos' => 106,
            'endFilePos' => 3374,
          ),
        ),
        'docComment' => '/**
 * Open for reading and writing; place the file pointer at the end of the
 * file. If the file does not exist, attempt to create it.
 *
 * @const string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 104,
        'endLine' => 104,
        'startColumn' => 5,
        'endColumn' => 43,
      ),
      'MODE_CREATE_WRITE' => 
      array (
        'declaringClassName' => 'Hoa\\File\\File',
        'implementingClassName' => 'Hoa\\File\\File',
        'name' => 'MODE_CREATE_WRITE',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'xb\'',
          'attributes' => 
          array (
            'startLine' => 115,
            'endLine' => 115,
            'startTokenPos' => 117,
            'startFilePos' => 3844,
            'endTokenPos' => 117,
            'endFilePos' => 3847,
          ),
        ),
        'docComment' => '/**
 * Create and open for writing only; place the file pointer at the beginning
 * of the file. If the file already exits, the fopen() call with fail by
 * returning false and generating an error of level E_WARNING. If the file
 * does not exist, attempt to create it. This is equivalent to specifying
 * O_EXCL | O_CREAT flags for the underlying open(2) system call.
 *
 * @const string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 115,
        'endLine' => 115,
        'startColumn' => 5,
        'endColumn' => 42,
      ),
      'MODE_CREATE_READ_WRITE' => 
      array (
        'declaringClassName' => 'Hoa\\File\\File',
        'implementingClassName' => 'Hoa\\File\\File',
        'name' => 'MODE_CREATE_READ_WRITE',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'x+b\'',
          'attributes' => 
          array (
            'startLine' => 126,
            'endLine' => 126,
            'startTokenPos' => 128,
            'startFilePos' => 4325,
            'endTokenPos' => 128,
            'endFilePos' => 4329,
          ),
        ),
        'docComment' => '/**
 * Create and open for reading and writing; place the file pointer at the
 * beginning of the file. If the file already exists, the fopen() call with
 * fail by returning false and generating an error of level E_WARNING. If
 * the file does not exist, attempt to create it. This is equivalent to
 * specifying O_EXCL | O_CREAT flags for the underlying open(2) system call.
 *
 * @const string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 126,
        'endLine' => 126,
        'startColumn' => 5,
        'endColumn' => 43,
      ),
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
            'startLine' => 142,
            'endLine' => 142,
            'startColumn' => 9,
            'endColumn' => 19,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
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
            'startLine' => 143,
            'endLine' => 143,
            'startColumn' => 9,
            'endColumn' => 13,
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
                'startLine' => 144,
                'endLine' => 144,
                'startTokenPos' => 150,
                'startFilePos' => 4892,
                'endTokenPos' => 150,
                'endFilePos' => 4895,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 144,
            'endLine' => 144,
            'startColumn' => 9,
            'endColumn' => 23,
            'parameterIndex' => 2,
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
                'startLine' => 145,
                'endLine' => 145,
                'startTokenPos' => 157,
                'startFilePos' => 4917,
                'endTokenPos' => 157,
                'endFilePos' => 4921,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 145,
            'endLine' => 145,
            'startColumn' => 9,
            'endColumn' => 24,
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
 * Open a file.
 *
 * @param   string  $streamName    Stream name (or file descriptor).
 * @param   string  $mode          Open mode, see the self::MODE_*
 *                                 constants.
 * @param   string  $context       Context ID (please, see the
 *                                 \\Hoa\\Stream\\Context class).
 * @param   bool    $wait          Differ opening or not.
 * @throws  \\Hoa\\File\\Exception
 */',
        'startLine' => 141,
        'endLine' => 184,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Hoa\\File',
        'declaringClassName' => 'Hoa\\File\\File',
        'implementingClassName' => 'Hoa\\File\\File',
        'currentClassName' => 'Hoa\\File\\File',
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
            'startLine' => 195,
            'endLine' => 195,
            'startColumn' => 31,
            'endColumn' => 41,
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
                'startLine' => 195,
                'endLine' => 195,
                'startTokenPos' => 342,
                'startFilePos' => 6350,
                'endTokenPos' => 342,
                'endFilePos' => 6353,
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
            'startLine' => 195,
            'endLine' => 195,
            'startColumn' => 44,
            'endColumn' => 74,
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
 *
 * @param   string               $streamName    Stream name (e.g. path or URL).
 * @param   \\Hoa\\Stream\\Context  $context       Context.
 * @return  resource
 * @throws  \\Hoa\\File\\Exception\\FileDoesNotExist
 * @throws  \\Hoa\\File\\Exception
 */',
        'startLine' => 195,
        'endLine' => 234,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Hoa\\File',
        'declaringClassName' => 'Hoa\\File\\File',
        'implementingClassName' => 'Hoa\\File\\File',
        'currentClassName' => 'Hoa\\File\\File',
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
 *
 * @return  bool
 */',
        'startLine' => 241,
        'endLine' => 244,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Hoa\\File',
        'declaringClassName' => 'Hoa\\File\\File',
        'implementingClassName' => 'Hoa\\File\\File',
        'currentClassName' => 'Hoa\\File\\File',
        'aliasName' => NULL,
      ),
      'newBuffer' => 
      array (
        'name' => 'newBuffer',
        'parameters' => 
        array (
          'callable' => 
          array (
            'name' => 'callable',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 254,
                'endLine' => 254,
                'startTokenPos' => 590,
                'startFilePos' => 7769,
                'endTokenPos' => 590,
                'endFilePos' => 7772,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 254,
            'endLine' => 254,
            'startColumn' => 31,
            'endColumn' => 46,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
          'size' => 
          array (
            'name' => 'size',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 254,
                'endLine' => 254,
                'startTokenPos' => 597,
                'startFilePos' => 7783,
                'endTokenPos' => 597,
                'endFilePos' => 7786,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 254,
            'endLine' => 254,
            'startColumn' => 49,
            'endColumn' => 60,
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
 * Start a new buffer.
 * The callable acts like a light filter.
 *
 * @param   mixed   $callable    Callable.
 * @param   int     $size        Size.
 * @return  int
 */',
        'startLine' => 254,
        'endLine' => 261,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Hoa\\File',
        'declaringClassName' => 'Hoa\\File\\File',
        'implementingClassName' => 'Hoa\\File\\File',
        'currentClassName' => 'Hoa\\File\\File',
        'aliasName' => NULL,
      ),
      'flush' => 
      array (
        'name' => 'flush',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Flush the output to a stream.
 *
 * @return  bool
 */',
        'startLine' => 268,
        'endLine' => 271,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Hoa\\File',
        'declaringClassName' => 'Hoa\\File\\File',
        'implementingClassName' => 'Hoa\\File\\File',
        'currentClassName' => 'Hoa\\File\\File',
        'aliasName' => NULL,
      ),
      'deleteBuffer' => 
      array (
        'name' => 'deleteBuffer',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Delete buffer.
 *
 * @return  bool
 */',
        'startLine' => 278,
        'endLine' => 281,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Hoa\\File',
        'declaringClassName' => 'Hoa\\File\\File',
        'implementingClassName' => 'Hoa\\File\\File',
        'currentClassName' => 'Hoa\\File\\File',
        'aliasName' => NULL,
      ),
      'getBufferLevel' => 
      array (
        'name' => 'getBufferLevel',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get bufffer level.
 *
 * @return  int
 */',
        'startLine' => 288,
        'endLine' => 291,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Hoa\\File',
        'declaringClassName' => 'Hoa\\File\\File',
        'implementingClassName' => 'Hoa\\File\\File',
        'currentClassName' => 'Hoa\\File\\File',
        'aliasName' => NULL,
      ),
      'getBufferSize' => 
      array (
        'name' => 'getBufferSize',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get buffer size.
 *
 * @return  int
 */',
        'startLine' => 298,
        'endLine' => 301,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Hoa\\File',
        'declaringClassName' => 'Hoa\\File\\File',
        'implementingClassName' => 'Hoa\\File\\File',
        'currentClassName' => 'Hoa\\File\\File',
        'aliasName' => NULL,
      ),
      'lock' => 
      array (
        'name' => 'lock',
        'parameters' => 
        array (
          'operation' => 
          array (
            'name' => 'operation',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 310,
            'endLine' => 310,
            'startColumn' => 26,
            'endColumn' => 35,
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
 * Portable advisory locking.
 *
 * @param   int     $operation    Operation, use the
 *                                \\Hoa\\Stream\\IStream\\Lockable::LOCK_* constants.
 * @return  bool
 */',
        'startLine' => 310,
        'endLine' => 313,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Hoa\\File',
        'declaringClassName' => 'Hoa\\File\\File',
        'implementingClassName' => 'Hoa\\File\\File',
        'currentClassName' => 'Hoa\\File\\File',
        'aliasName' => NULL,
      ),
      'rewind' => 
      array (
        'name' => 'rewind',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Rewind the position of a stream pointer.
 *
 * @return  bool
 */',
        'startLine' => 320,
        'endLine' => 323,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Hoa\\File',
        'declaringClassName' => 'Hoa\\File\\File',
        'implementingClassName' => 'Hoa\\File\\File',
        'currentClassName' => 'Hoa\\File\\File',
        'aliasName' => NULL,
      ),
      'seek' => 
      array (
        'name' => 'seek',
        'parameters' => 
        array (
          'offset' => 
          array (
            'name' => 'offset',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 333,
            'endLine' => 333,
            'startColumn' => 26,
            'endColumn' => 32,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'whence' => 
          array (
            'name' => 'whence',
            'default' => 
            array (
              'code' => '\\Hoa\\Stream\\IStream\\Pointable::SEEK_SET',
              'attributes' => 
              array (
                'startLine' => 333,
                'endLine' => 333,
                'startTokenPos' => 781,
                'startFilePos' => 9366,
                'endTokenPos' => 783,
                'endFilePos' => 9399,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 333,
            'endLine' => 333,
            'startColumn' => 35,
            'endColumn' => 78,
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
 * Seek on a stream pointer.
 *
 * @param   int     $offset    Offset (negative value should be supported).
 * @param   int     $whence    Whence, use the
 *                             \\Hoa\\Stream\\IStream\\Pointable::SEEK_* constants.
 * @return  int
 */',
        'startLine' => 333,
        'endLine' => 336,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Hoa\\File',
        'declaringClassName' => 'Hoa\\File\\File',
        'implementingClassName' => 'Hoa\\File\\File',
        'currentClassName' => 'Hoa\\File\\File',
        'aliasName' => NULL,
      ),
      'tell' => 
      array (
        'name' => 'tell',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the current position of the stream pointer.
 *
 * @return  int
 */',
        'startLine' => 343,
        'endLine' => 352,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Hoa\\File',
        'declaringClassName' => 'Hoa\\File\\File',
        'implementingClassName' => 'Hoa\\File\\File',
        'currentClassName' => 'Hoa\\File\\File',
        'aliasName' => NULL,
      ),
      'create' => 
      array (
        'name' => 'create',
        'parameters' => 
        array (
          'name' => 
          array (
            'name' => 'name',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 361,
            'endLine' => 361,
            'startColumn' => 35,
            'endColumn' => 39,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'dummy' => 
          array (
            'name' => 'dummy',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 361,
            'endLine' => 361,
            'startColumn' => 42,
            'endColumn' => 47,
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
 * Create a file.
 *
 * @param   string  $name     File name.
 * @param   mixed   $dummy    To be compatible with childs.
 * @return  bool
 */',
        'startLine' => 361,
        'endLine' => 368,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Hoa\\File',
        'declaringClassName' => 'Hoa\\File\\File',
        'implementingClassName' => 'Hoa\\File\\File',
        'currentClassName' => 'Hoa\\File\\File',
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