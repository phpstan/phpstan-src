<?php declare(strict_types = 1);

// osfsl-/home/runner/work/phpstan-src/phpstan-src/vendor/composer/../hoa/stream/./IStream/In.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Hoa\Stream\IStream\In
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-61627296ec24fba69fe7b884db3a4207da2090e58d951056e5588ab31bfb5e1c-8.4.21-6.70.0.1',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Hoa\\Stream\\IStream\\In',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/vendor/composer/../hoa/stream/./IStream/In.php',
      ),
    ),
    'namespace' => 'Hoa\\Stream\\IStream',
    'name' => 'Hoa\\Stream\\IStream\\In',
    'shortName' => 'In',
    'isInterface' => true,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Interface \\Hoa\\Stream\\IStream\\In.
 *
 * Interface for input.
 *
 * @copyright  Copyright © 2007-2017 Hoa community
 * @license    New BSD License
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 47,
    'endLine' => 135,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
      0 => 'Hoa\\Stream\\IStream\\Stream',
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
 * Test for end-of-stream.
 *
 * @return  bool
 */',
        'startLine' => 54,
        'endLine' => 54,
        'startColumn' => 5,
        'endColumn' => 26,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Hoa\\Stream\\IStream',
        'declaringClassName' => 'Hoa\\Stream\\IStream\\In',
        'implementingClassName' => 'Hoa\\Stream\\IStream\\In',
        'currentClassName' => 'Hoa\\Stream\\IStream\\In',
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
            'startLine' => 62,
            'endLine' => 62,
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
 */',
        'startLine' => 62,
        'endLine' => 62,
        'startColumn' => 5,
        'endColumn' => 34,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Hoa\\Stream\\IStream',
        'declaringClassName' => 'Hoa\\Stream\\IStream\\In',
        'implementingClassName' => 'Hoa\\Stream\\IStream\\In',
        'currentClassName' => 'Hoa\\Stream\\IStream\\In',
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
            'startLine' => 70,
            'endLine' => 70,
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
        'startLine' => 70,
        'endLine' => 70,
        'startColumn' => 5,
        'endColumn' => 40,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Hoa\\Stream\\IStream',
        'declaringClassName' => 'Hoa\\Stream\\IStream\\In',
        'implementingClassName' => 'Hoa\\Stream\\IStream\\In',
        'currentClassName' => 'Hoa\\Stream\\IStream\\In',
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
 * It could be equivalent to $this->read(1).
 *
 * @return  string
 */',
        'startLine' => 78,
        'endLine' => 78,
        'startColumn' => 5,
        'endColumn' => 36,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Hoa\\Stream\\IStream',
        'declaringClassName' => 'Hoa\\Stream\\IStream\\In',
        'implementingClassName' => 'Hoa\\Stream\\IStream\\In',
        'currentClassName' => 'Hoa\\Stream\\IStream\\In',
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
        'startLine' => 85,
        'endLine' => 85,
        'startColumn' => 5,
        'endColumn' => 34,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Hoa\\Stream\\IStream',
        'declaringClassName' => 'Hoa\\Stream\\IStream\\In',
        'implementingClassName' => 'Hoa\\Stream\\IStream\\In',
        'currentClassName' => 'Hoa\\Stream\\IStream\\In',
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
                'startLine' => 93,
                'endLine' => 93,
                'startTokenPos' => 90,
                'startFilePos' => 2698,
                'endTokenPos' => 90,
                'endFilePos' => 2698,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 93,
            'endLine' => 93,
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
        'startLine' => 93,
        'endLine' => 93,
        'startColumn' => 5,
        'endColumn' => 45,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Hoa\\Stream\\IStream',
        'declaringClassName' => 'Hoa\\Stream\\IStream\\In',
        'implementingClassName' => 'Hoa\\Stream\\IStream\\In',
        'currentClassName' => 'Hoa\\Stream\\IStream\\In',
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
                'startLine' => 101,
                'endLine' => 101,
                'startTokenPos' => 106,
                'startFilePos' => 2852,
                'endTokenPos' => 106,
                'endFilePos' => 2852,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 101,
            'endLine' => 101,
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
        'startLine' => 101,
        'endLine' => 101,
        'startColumn' => 5,
        'endColumn' => 43,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Hoa\\Stream\\IStream',
        'declaringClassName' => 'Hoa\\Stream\\IStream\\In',
        'implementingClassName' => 'Hoa\\Stream\\IStream\\In',
        'currentClassName' => 'Hoa\\Stream\\IStream\\In',
        'aliasName' => NULL,
      ),
      'readArray' => 
      array (
        'name' => 'readArray',
        'parameters' => 
        array (
          'argument' => 
          array (
            'name' => 'argument',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 111,
                'endLine' => 111,
                'startTokenPos' => 122,
                'startFilePos' => 3196,
                'endTokenPos' => 122,
                'endFilePos' => 3199,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 111,
            'endLine' => 111,
            'startColumn' => 31,
            'endColumn' => 46,
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
 * In most cases, it could be an alias to the $this->scanf() method.
 *
 * @param   mixed   $argument    Argument (because the behavior is very
 *                               different according to the implementation).
 * @return  array
 */',
        'startLine' => 111,
        'endLine' => 111,
        'startColumn' => 5,
        'endColumn' => 48,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Hoa\\Stream\\IStream',
        'declaringClassName' => 'Hoa\\Stream\\IStream\\In',
        'implementingClassName' => 'Hoa\\Stream\\IStream\\In',
        'currentClassName' => 'Hoa\\Stream\\IStream\\In',
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
        'startLine' => 118,
        'endLine' => 118,
        'startColumn' => 5,
        'endColumn' => 31,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Hoa\\Stream\\IStream',
        'declaringClassName' => 'Hoa\\Stream\\IStream\\In',
        'implementingClassName' => 'Hoa\\Stream\\IStream\\In',
        'currentClassName' => 'Hoa\\Stream\\IStream\\In',
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
                'startLine' => 126,
                'endLine' => 126,
                'startTokenPos' => 149,
                'startFilePos' => 3475,
                'endTokenPos' => 149,
                'endFilePos' => 3475,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 126,
            'endLine' => 126,
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
        'startLine' => 126,
        'endLine' => 126,
        'startColumn' => 5,
        'endColumn' => 41,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Hoa\\Stream\\IStream',
        'declaringClassName' => 'Hoa\\Stream\\IStream\\In',
        'implementingClassName' => 'Hoa\\Stream\\IStream\\In',
        'currentClassName' => 'Hoa\\Stream\\IStream\\In',
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
            'startLine' => 134,
            'endLine' => 134,
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
        'startLine' => 134,
        'endLine' => 134,
        'startColumn' => 5,
        'endColumn' => 35,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Hoa\\Stream\\IStream',
        'declaringClassName' => 'Hoa\\Stream\\IStream\\In',
        'implementingClassName' => 'Hoa\\Stream\\IStream\\In',
        'currentClassName' => 'Hoa\\Stream\\IStream\\In',
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