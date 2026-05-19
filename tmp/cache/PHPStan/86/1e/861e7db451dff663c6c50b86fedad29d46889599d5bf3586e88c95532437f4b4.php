<?php declare(strict_types = 1);

// osfsl-/home/runner/work/phpstan-src/phpstan-src/vendor/composer/../hoa/file/./Read.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Hoa\File\Read
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-cae72930e8113201ef220b65610b505ccbb9bb1f30ecad1a53ec704e430d51d5-8.4.21-6.70.0.1',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Hoa\\File\\Read',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/vendor/composer/../hoa/file/./Read.php',
      ),
    ),
    'namespace' => 'Hoa\\File',
    'name' => 'Hoa\\File\\Read',
    'shortName' => 'Read',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Class \\Hoa\\File\\Read.
 *
 * File handler.
 *
 * @copyright  Copyright © 2007-2017 Hoa community
 * @license    New BSD License
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 49,
    'endLine' => 236,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'Hoa\\File\\File',
    'implementsClassNames' => 
    array (
      0 => 'Hoa\\Stream\\IStream\\In',
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
            'startLine' => 61,
            'endLine' => 61,
            'startColumn' => 9,
            'endColumn' => 19,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'mode' => 
          array (
            'name' => 'mode',
            'default' => 
            array (
              'code' => 'parent::MODE_READ',
              'attributes' => 
              array (
                'startLine' => 62,
                'endLine' => 62,
                'startTokenPos' => 46,
                'startFilePos' => 2328,
                'endTokenPos' => 48,
                'endFilePos' => 2344,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 62,
            'endLine' => 62,
            'startColumn' => 9,
            'endColumn' => 36,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
          'context' => 
          array (
            'name' => 'context',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 63,
                'endLine' => 63,
                'startTokenPos' => 55,
                'startFilePos' => 2366,
                'endTokenPos' => 55,
                'endFilePos' => 2369,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 63,
            'endLine' => 63,
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
                'startLine' => 64,
                'endLine' => 64,
                'startTokenPos' => 62,
                'startFilePos' => 2391,
                'endTokenPos' => 62,
                'endFilePos' => 2395,
              ),
            ),
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
 * @param   string  $streamName    Stream name.
 * @param   string  $mode          Open mode, see the self::MODE_* constants.
 * @param   string  $context       Context ID (please, see the
 *                                 \\Hoa\\Stream\\Context class).
 * @param   bool    $wait          Differ opening or not.
 */',
        'startLine' => 60,
        'endLine' => 69,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Hoa\\File',
        'declaringClassName' => 'Hoa\\File\\Read',
        'implementingClassName' => 'Hoa\\File\\Read',
        'currentClassName' => 'Hoa\\File\\Read',
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
            'startLine' => 80,
            'endLine' => 80,
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
                'startLine' => 80,
                'endLine' => 80,
                'startTokenPos' => 109,
                'startFilePos' => 2906,
                'endTokenPos' => 109,
                'endFilePos' => 2909,
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
            'startLine' => 80,
            'endLine' => 80,
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
        'startLine' => 80,
        'endLine' => 108,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Hoa\\File',
        'declaringClassName' => 'Hoa\\File\\Read',
        'implementingClassName' => 'Hoa\\File\\Read',
        'currentClassName' => 'Hoa\\File\\Read',
        'aliasName' => NULL,
      ),
      'eof' => 
      array (
        'name' => 'eof',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Test for end-of-file.
 *
 * @return  bool
 */',
        'startLine' => 115,
        'endLine' => 118,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Hoa\\File',
        'declaringClassName' => 'Hoa\\File\\Read',
        'implementingClassName' => 'Hoa\\File\\Read',
        'currentClassName' => 'Hoa\\File\\Read',
        'aliasName' => NULL,
      ),
      'read' => 
      array (
        'name' => 'read',
        'parameters' => 
        array (
          'length' => 
          array (
            'name' => 'length',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 127,
            'endLine' => 127,
            'startColumn' => 26,
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
 * Read n characters.
 *
 * @param   int     $length    Length.
 * @return  string
 * @throws  \\Hoa\\File\\Exception
 */',
        'startLine' => 127,
        'endLine' => 138,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Hoa\\File',
        'declaringClassName' => 'Hoa\\File\\Read',
        'implementingClassName' => 'Hoa\\File\\Read',
        'currentClassName' => 'Hoa\\File\\Read',
        'aliasName' => NULL,
      ),
      'readString' => 
      array (
        'name' => 'readString',
        'parameters' => 
        array (
          'length' => 
          array (
            'name' => 'length',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 146,
            'endLine' => 146,
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
 * Alias of $this->read().
 *
 * @param   int     $length    Length.
 * @return  string
 */',
        'startLine' => 146,
        'endLine' => 149,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Hoa\\File',
        'declaringClassName' => 'Hoa\\File\\Read',
        'implementingClassName' => 'Hoa\\File\\Read',
        'currentClassName' => 'Hoa\\File\\Read',
        'aliasName' => NULL,
      ),
      'readCharacter' => 
      array (
        'name' => 'readCharacter',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Read a character.
 *
 * @return  string
 */',
        'startLine' => 156,
        'endLine' => 159,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Hoa\\File',
        'declaringClassName' => 'Hoa\\File\\Read',
        'implementingClassName' => 'Hoa\\File\\Read',
        'currentClassName' => 'Hoa\\File\\Read',
        'aliasName' => NULL,
      ),
      'readBoolean' => 
      array (
        'name' => 'readBoolean',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Read a boolean.
 *
 * @return  bool
 */',
        'startLine' => 166,
        'endLine' => 169,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Hoa\\File',
        'declaringClassName' => 'Hoa\\File\\Read',
        'implementingClassName' => 'Hoa\\File\\Read',
        'currentClassName' => 'Hoa\\File\\Read',
        'aliasName' => NULL,
      ),
      'readInteger' => 
      array (
        'name' => 'readInteger',
        'parameters' => 
        array (
          'length' => 
          array (
            'name' => 'length',
            'default' => 
            array (
              'code' => '1',
              'attributes' => 
              array (
                'startLine' => 177,
                'endLine' => 177,
                'startTokenPos' => 462,
                'startFilePos' => 4965,
                'endTokenPos' => 462,
                'endFilePos' => 4965,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 177,
            'endLine' => 177,
            'startColumn' => 33,
            'endColumn' => 43,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Read an integer.
 *
 * @param   int     $length    Length.
 * @return  int
 */',
        'startLine' => 177,
        'endLine' => 180,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Hoa\\File',
        'declaringClassName' => 'Hoa\\File\\Read',
        'implementingClassName' => 'Hoa\\File\\Read',
        'currentClassName' => 'Hoa\\File\\Read',
        'aliasName' => NULL,
      ),
      'readFloat' => 
      array (
        'name' => 'readFloat',
        'parameters' => 
        array (
          'length' => 
          array (
            'name' => 'length',
            'default' => 
            array (
              'code' => '1',
              'attributes' => 
              array (
                'startLine' => 188,
                'endLine' => 188,
                'startTokenPos' => 493,
                'startFilePos' => 5173,
                'endTokenPos' => 493,
                'endFilePos' => 5173,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 188,
            'endLine' => 188,
            'startColumn' => 31,
            'endColumn' => 41,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Read a float.
 *
 * @param   int     $length    Length.
 * @return  float
 */',
        'startLine' => 188,
        'endLine' => 191,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Hoa\\File',
        'declaringClassName' => 'Hoa\\File\\Read',
        'implementingClassName' => 'Hoa\\File\\Read',
        'currentClassName' => 'Hoa\\File\\Read',
        'aliasName' => NULL,
      ),
      'readArray' => 
      array (
        'name' => 'readArray',
        'parameters' => 
        array (
          'format' => 
          array (
            'name' => 'format',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 200,
                'endLine' => 200,
                'startTokenPos' => 524,
                'startFilePos' => 5450,
                'endTokenPos' => 524,
                'endFilePos' => 5453,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 200,
            'endLine' => 200,
            'startColumn' => 31,
            'endColumn' => 44,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Read an array.
 * Alias of the $this->scanf() method.
 *
 * @param   string  $format    Format (see printf\'s formats).
 * @return  array
 */',
        'startLine' => 200,
        'endLine' => 203,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Hoa\\File',
        'declaringClassName' => 'Hoa\\File\\Read',
        'implementingClassName' => 'Hoa\\File\\Read',
        'currentClassName' => 'Hoa\\File\\Read',
        'aliasName' => NULL,
      ),
      'readLine' => 
      array (
        'name' => 'readLine',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Read a line.
 *
 * @return  string
 */',
        'startLine' => 210,
        'endLine' => 213,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Hoa\\File',
        'declaringClassName' => 'Hoa\\File\\Read',
        'implementingClassName' => 'Hoa\\File\\Read',
        'currentClassName' => 'Hoa\\File\\Read',
        'aliasName' => NULL,
      ),
      'readAll' => 
      array (
        'name' => 'readAll',
        'parameters' => 
        array (
          'offset' => 
          array (
            'name' => 'offset',
            'default' => 
            array (
              'code' => '0',
              'attributes' => 
              array (
                'startLine' => 221,
                'endLine' => 221,
                'startTokenPos' => 579,
                'startFilePos' => 5831,
                'endTokenPos' => 579,
                'endFilePos' => 5831,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 221,
            'endLine' => 221,
            'startColumn' => 29,
            'endColumn' => 39,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Read all, i.e. read as much as possible.
 *
 * @param   int  $offset    Offset.
 * @return  string
 */',
        'startLine' => 221,
        'endLine' => 224,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Hoa\\File',
        'declaringClassName' => 'Hoa\\File\\Read',
        'implementingClassName' => 'Hoa\\File\\Read',
        'currentClassName' => 'Hoa\\File\\Read',
        'aliasName' => NULL,
      ),
      'scanf' => 
      array (
        'name' => 'scanf',
        'parameters' => 
        array (
          'format' => 
          array (
            'name' => 'format',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 232,
            'endLine' => 232,
            'startColumn' => 27,
            'endColumn' => 33,
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
 * Parse input from a stream according to a format.
 *
 * @param   string  $format    Format (see printf\'s formats).
 * @return  array
 */',
        'startLine' => 232,
        'endLine' => 235,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Hoa\\File',
        'declaringClassName' => 'Hoa\\File\\Read',
        'implementingClassName' => 'Hoa\\File\\Read',
        'currentClassName' => 'Hoa\\File\\Read',
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