<?php declare(strict_types = 1);

// osfsl-/home/runner/work/phpstan-src/phpstan-src/vendor/composer/../hoa/stream/./IStream/Lockable.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Hoa\Stream\IStream\Lockable
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-9dd05966fbbcdc4cd1c6d3d8ddb15fdba0c57d9ac5607ed000c027f334e30a51-8.4.21-6.70.0.1',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Hoa\\Stream\\IStream\\Lockable',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/vendor/composer/../hoa/stream/./IStream/Lockable.php',
      ),
    ),
    'namespace' => 'Hoa\\Stream\\IStream',
    'name' => 'Hoa\\Stream\\IStream\\Lockable',
    'shortName' => 'Lockable',
    'isInterface' => true,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Interface \\Hoa\\Stream\\IStream\\Lockable.
 *
 * Interface for lockable input/output.
 *
 * @copyright  Copyright © 2007-2017 Hoa community
 * @license    New BSD License
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 47,
    'endLine' => 87,
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
      'LOCK_SHARED' => 
      array (
        'declaringClassName' => 'Hoa\\Stream\\IStream\\Lockable',
        'implementingClassName' => 'Hoa\\Stream\\IStream\\Lockable',
        'name' => 'LOCK_SHARED',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => 'LOCK_SH',
          'attributes' => 
          array (
            'startLine' => 54,
            'endLine' => 54,
            'startTokenPos' => 29,
            'startFilePos' => 2004,
            'endTokenPos' => 29,
            'endFilePos' => 2010,
          ),
        ),
        'docComment' => '/**
 * Acquire a shared lock (reader).
 *
 * @const int
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 54,
        'endLine' => 54,
        'startColumn' => 5,
        'endColumn' => 35,
      ),
      'LOCK_EXCLUSIVE' => 
      array (
        'declaringClassName' => 'Hoa\\Stream\\IStream\\Lockable',
        'implementingClassName' => 'Hoa\\Stream\\IStream\\Lockable',
        'name' => 'LOCK_EXCLUSIVE',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => 'LOCK_EX',
          'attributes' => 
          array (
            'startLine' => 61,
            'endLine' => 61,
            'startTokenPos' => 40,
            'startFilePos' => 2125,
            'endTokenPos' => 40,
            'endFilePos' => 2131,
          ),
        ),
        'docComment' => '/**
 * Acquire an exclusive lock (writer).
 *
 * @const int
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 61,
        'endLine' => 61,
        'startColumn' => 5,
        'endColumn' => 35,
      ),
      'LOCK_RELEASE' => 
      array (
        'declaringClassName' => 'Hoa\\Stream\\IStream\\Lockable',
        'implementingClassName' => 'Hoa\\Stream\\IStream\\Lockable',
        'name' => 'LOCK_RELEASE',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => 'LOCK_UN',
          'attributes' => 
          array (
            'startLine' => 68,
            'endLine' => 68,
            'startTokenPos' => 51,
            'startFilePos' => 2248,
            'endTokenPos' => 51,
            'endFilePos' => 2254,
          ),
        ),
        'docComment' => '/**
 * Release a lock (shared or exclusive).
 *
 * @const int
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 68,
        'endLine' => 68,
        'startColumn' => 5,
        'endColumn' => 35,
      ),
      'LOCK_NO_BLOCK' => 
      array (
        'declaringClassName' => 'Hoa\\Stream\\IStream\\Lockable',
        'implementingClassName' => 'Hoa\\Stream\\IStream\\Lockable',
        'name' => 'LOCK_NO_BLOCK',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => 'LOCK_NB',
          'attributes' => 
          array (
            'startLine' => 75,
            'endLine' => 75,
            'startTokenPos' => 62,
            'startFilePos' => 2389,
            'endTokenPos' => 62,
            'endFilePos' => 2395,
          ),
        ),
        'docComment' => '/**
 * If we do not want $this->lock() to block while locking.
 *
 * @const int
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 75,
        'endLine' => 75,
        'startColumn' => 5,
        'endColumn' => 35,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
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
            'startLine' => 86,
            'endLine' => 86,
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
 * Should take a look at stream_supports_lock().
 *
 * @param   int     $operation    Operation, use the self::LOCK_* constants.
 * @return  bool
 */',
        'startLine' => 86,
        'endLine' => 86,
        'startColumn' => 5,
        'endColumn' => 37,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Hoa\\Stream\\IStream',
        'declaringClassName' => 'Hoa\\Stream\\IStream\\Lockable',
        'implementingClassName' => 'Hoa\\Stream\\IStream\\Lockable',
        'currentClassName' => 'Hoa\\Stream\\IStream\\Lockable',
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