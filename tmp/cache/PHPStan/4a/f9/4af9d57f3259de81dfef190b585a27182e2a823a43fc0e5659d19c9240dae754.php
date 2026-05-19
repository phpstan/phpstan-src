<?php declare(strict_types = 1);

// osfsl-/home/runner/work/phpstan-src/phpstan-src/vendor/composer/../hoa/stream/./IStream/Bufferable.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Hoa\Stream\IStream\Bufferable
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-871bd2381a55524b047eab836a4c297a4d9d1e03793a170aff05f56a95d4755b-8.4.21-6.70.0.1',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Hoa\\Stream\\IStream\\Bufferable',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/vendor/composer/../hoa/stream/./IStream/Bufferable.php',
      ),
    ),
    'namespace' => 'Hoa\\Stream\\IStream',
    'name' => 'Hoa\\Stream\\IStream\\Bufferable',
    'shortName' => 'Bufferable',
    'isInterface' => true,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Interface \\Hoa\\Stream\\IStream\\Bufferable.
 *
 * Interface for bufferable streams. It\'s complementary to native buffer support
 * of Hoa\\Stream (please, see *StreamBuffer*() methods). Classes implementing
 * this interface are able to create nested buffers, flush them etc.
 *
 * @copyright  Copyright © 2007-2017 Hoa community
 * @license    New BSD License
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 49,
    'endLine' => 88,
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
                'startLine' => 59,
                'endLine' => 59,
                'startTokenPos' => 33,
                'startFilePos' => 2337,
                'endTokenPos' => 33,
                'endFilePos' => 2340,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 59,
            'endLine' => 59,
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
                'startLine' => 59,
                'endLine' => 59,
                'startTokenPos' => 40,
                'startFilePos' => 2351,
                'endTokenPos' => 40,
                'endFilePos' => 2354,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 59,
            'endLine' => 59,
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
        'startLine' => 59,
        'endLine' => 59,
        'startColumn' => 5,
        'endColumn' => 62,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Hoa\\Stream\\IStream',
        'declaringClassName' => 'Hoa\\Stream\\IStream\\Bufferable',
        'implementingClassName' => 'Hoa\\Stream\\IStream\\Bufferable',
        'currentClassName' => 'Hoa\\Stream\\IStream\\Bufferable',
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
 * Flush the buffer.
 *
 * @return  void
 */',
        'startLine' => 66,
        'endLine' => 66,
        'startColumn' => 5,
        'endColumn' => 28,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Hoa\\Stream\\IStream',
        'declaringClassName' => 'Hoa\\Stream\\IStream\\Bufferable',
        'implementingClassName' => 'Hoa\\Stream\\IStream\\Bufferable',
        'currentClassName' => 'Hoa\\Stream\\IStream\\Bufferable',
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
        'startLine' => 73,
        'endLine' => 73,
        'startColumn' => 5,
        'endColumn' => 35,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Hoa\\Stream\\IStream',
        'declaringClassName' => 'Hoa\\Stream\\IStream\\Bufferable',
        'implementingClassName' => 'Hoa\\Stream\\IStream\\Bufferable',
        'currentClassName' => 'Hoa\\Stream\\IStream\\Bufferable',
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
        'startLine' => 80,
        'endLine' => 80,
        'startColumn' => 5,
        'endColumn' => 37,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Hoa\\Stream\\IStream',
        'declaringClassName' => 'Hoa\\Stream\\IStream\\Bufferable',
        'implementingClassName' => 'Hoa\\Stream\\IStream\\Bufferable',
        'currentClassName' => 'Hoa\\Stream\\IStream\\Bufferable',
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
        'startLine' => 87,
        'endLine' => 87,
        'startColumn' => 5,
        'endColumn' => 36,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Hoa\\Stream\\IStream',
        'declaringClassName' => 'Hoa\\Stream\\IStream\\Bufferable',
        'implementingClassName' => 'Hoa\\Stream\\IStream\\Bufferable',
        'currentClassName' => 'Hoa\\Stream\\IStream\\Bufferable',
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