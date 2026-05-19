<?php declare(strict_types = 1);

// osfsl-/home/runner/work/phpstan-src/phpstan-src/vendor/composer/../hoa/stream/./IStream/Pointable.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Hoa\Stream\IStream\Pointable
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-8ac3c3a272c47944e6100394d70ac30d151b080180240697b52fd2f28aff01c1-8.4.21-6.70.0.1',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Hoa\\Stream\\IStream\\Pointable',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/vendor/composer/../hoa/stream/./IStream/Pointable.php',
      ),
    ),
    'namespace' => 'Hoa\\Stream\\IStream',
    'name' => 'Hoa\\Stream\\IStream\\Pointable',
    'shortName' => 'Pointable',
    'isInterface' => true,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Interface \\Hoa\\Stream\\IStream\\Pointable.
 *
 * Interface for pointable input/output.
 *
 * @copyright  Copyright © 2007-2017 Hoa community
 * @license    New BSD License
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 47,
    'endLine' => 94,
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
      'SEEK_SET' => 
      array (
        'declaringClassName' => 'Hoa\\Stream\\IStream\\Pointable',
        'implementingClassName' => 'Hoa\\Stream\\IStream\\Pointable',
        'name' => 'SEEK_SET',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => 'SEEK_SET',
          'attributes' => 
          array (
            'startLine' => 54,
            'endLine' => 54,
            'startTokenPos' => 29,
            'startFilePos' => 2010,
            'endTokenPos' => 29,
            'endFilePos' => 2017,
          ),
        ),
        'docComment' => '/**
 * Set position equal to $offset bytes.
 *
 * @const int
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 54,
        'endLine' => 54,
        'startColumn' => 5,
        'endColumn' => 34,
      ),
      'SEEK_CURRENT' => 
      array (
        'declaringClassName' => 'Hoa\\Stream\\IStream\\Pointable',
        'implementingClassName' => 'Hoa\\Stream\\IStream\\Pointable',
        'name' => 'SEEK_CURRENT',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => 'SEEK_CUR',
          'attributes' => 
          array (
            'startLine' => 61,
            'endLine' => 61,
            'startTokenPos' => 40,
            'startFilePos' => 2141,
            'endTokenPos' => 40,
            'endFilePos' => 2148,
          ),
        ),
        'docComment' => '/**
 * Set position to current location plus $offset.
 *
 * @const int
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 61,
        'endLine' => 61,
        'startColumn' => 5,
        'endColumn' => 34,
      ),
      'SEEK_END' => 
      array (
        'declaringClassName' => 'Hoa\\Stream\\IStream\\Pointable',
        'implementingClassName' => 'Hoa\\Stream\\IStream\\Pointable',
        'name' => 'SEEK_END',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => 'SEEK_END',
          'attributes' => 
          array (
            'startLine' => 68,
            'endLine' => 68,
            'startTokenPos' => 51,
            'startFilePos' => 2267,
            'endTokenPos' => 51,
            'endFilePos' => 2274,
          ),
        ),
        'docComment' => '/**
 * Set position to end-of-file plus $offset.
 *
 * @const int
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 68,
        'endLine' => 68,
        'startColumn' => 5,
        'endColumn' => 34,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
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
        'startLine' => 77,
        'endLine' => 77,
        'startColumn' => 5,
        'endColumn' => 29,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Hoa\\Stream\\IStream',
        'declaringClassName' => 'Hoa\\Stream\\IStream\\Pointable',
        'implementingClassName' => 'Hoa\\Stream\\IStream\\Pointable',
        'currentClassName' => 'Hoa\\Stream\\IStream\\Pointable',
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
            'startLine' => 86,
            'endLine' => 86,
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
              'code' => 'self::SEEK_SET',
              'attributes' => 
              array (
                'startLine' => 86,
                'endLine' => 86,
                'startTokenPos' => 80,
                'startFilePos' => 2678,
                'endTokenPos' => 82,
                'endFilePos' => 2691,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 86,
            'endLine' => 86,
            'startColumn' => 35,
            'endColumn' => 58,
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
 * @param   int     $whence    Whence, use the self::SEEK_* constants.
 * @return  int
 */',
        'startLine' => 86,
        'endLine' => 86,
        'startColumn' => 5,
        'endColumn' => 60,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Hoa\\Stream\\IStream',
        'declaringClassName' => 'Hoa\\Stream\\IStream\\Pointable',
        'implementingClassName' => 'Hoa\\Stream\\IStream\\Pointable',
        'currentClassName' => 'Hoa\\Stream\\IStream\\Pointable',
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
        'startLine' => 93,
        'endLine' => 93,
        'startColumn' => 5,
        'endColumn' => 27,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Hoa\\Stream\\IStream',
        'declaringClassName' => 'Hoa\\Stream\\IStream\\Pointable',
        'implementingClassName' => 'Hoa\\Stream\\IStream\\Pointable',
        'currentClassName' => 'Hoa\\Stream\\IStream\\Pointable',
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