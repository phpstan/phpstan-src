<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/vendor/nette/neon/src/Neon/Neon.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Nette\Neon\Neon
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-1894b3a8d67bb9e3bdb1b9e7e48733a44a8db5bb542edcceb2070eed587d950b',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Nette\\Neon\\Neon',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/vendor/nette/neon/src/Neon/Neon.php',
      ),
    ),
    'namespace' => 'Nette\\Neon',
    'name' => 'Nette\\Neon\\Neon',
    'shortName' => 'Neon',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Simple parser & generator for Nette Object Notation.
 * @see https://ne-on.org
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 17,
    'endLine' => 65,
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
      'BLOCK' => 
      array (
        'declaringClassName' => 'Nette\\Neon\\Neon',
        'implementingClassName' => 'Nette\\Neon\\Neon',
        'name' => 'BLOCK',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\\Nette\\Neon\\Encoder::BLOCK',
          'attributes' => 
          array (
            'startLine' => 19,
            'endLine' => 19,
            'startTokenPos' => 35,
            'startFilePos' => 320,
            'endTokenPos' => 37,
            'endFilePos' => 333,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 19,
        'endLine' => 19,
        'startColumn' => 2,
        'endColumn' => 37,
      ),
      'Chain' => 
      array (
        'declaringClassName' => 'Nette\\Neon\\Neon',
        'implementingClassName' => 'Nette\\Neon\\Neon',
        'name' => 'Chain',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'!!chain\'',
          'attributes' => 
          array (
            'startLine' => 20,
            'endLine' => 20,
            'startTokenPos' => 48,
            'startFilePos' => 358,
            'endTokenPos' => 48,
            'endFilePos' => 366,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 20,
        'endLine' => 20,
        'startColumn' => 2,
        'endColumn' => 32,
      ),
      'CHAIN' => 
      array (
        'declaringClassName' => 'Nette\\Neon\\Neon',
        'implementingClassName' => 'Nette\\Neon\\Neon',
        'name' => 'CHAIN',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => 'self::Chain',
          'attributes' => 
          array (
            'startLine' => 21,
            'endLine' => 21,
            'startTokenPos' => 59,
            'startFilePos' => 391,
            'endTokenPos' => 61,
            'endFilePos' => 401,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 21,
        'endLine' => 21,
        'startColumn' => 2,
        'endColumn' => 34,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'encode' => 
      array (
        'name' => 'encode',
        'parameters' => 
        array (
          'value' => 
          array (
            'name' => 'value',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 27,
            'endLine' => 27,
            'startColumn' => 32,
            'endColumn' => 37,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'blockMode' => 
          array (
            'name' => 'blockMode',
            'default' => 
            array (
              'code' => 'false',
              'attributes' => 
              array (
                'startLine' => 27,
                'endLine' => 27,
                'startTokenPos' => 83,
                'startFilePos' => 510,
                'endTokenPos' => 83,
                'endFilePos' => 514,
              ),
            ),
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'bool',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 27,
            'endLine' => 27,
            'startColumn' => 40,
            'endColumn' => 62,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
          'indentation' => 
          array (
            'name' => 'indentation',
            'default' => 
            array (
              'code' => '"\\t"',
              'attributes' => 
              array (
                'startLine' => 27,
                'endLine' => 27,
                'startTokenPos' => 92,
                'startFilePos' => 539,
                'endTokenPos' => 92,
                'endFilePos' => 542,
              ),
            ),
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'string',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 27,
            'endLine' => 27,
            'startColumn' => 65,
            'endColumn' => 90,
            'parameterIndex' => 2,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Returns value converted to NEON.
 */',
        'startLine' => 27,
        'endLine' => 33,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Nette\\Neon',
        'declaringClassName' => 'Nette\\Neon\\Neon',
        'implementingClassName' => 'Nette\\Neon\\Neon',
        'currentClassName' => 'Nette\\Neon\\Neon',
        'aliasName' => NULL,
      ),
      'decode' => 
      array (
        'name' => 'decode',
        'parameters' => 
        array (
          'input' => 
          array (
            'name' => 'input',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'string',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 40,
            'endLine' => 40,
            'startColumn' => 32,
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
        'docComment' => '/**
 * Converts given NEON to PHP value.
 * @return mixed
 */',
        'startLine' => 40,
        'endLine' => 44,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Nette\\Neon',
        'declaringClassName' => 'Nette\\Neon\\Neon',
        'implementingClassName' => 'Nette\\Neon\\Neon',
        'currentClassName' => 'Nette\\Neon\\Neon',
        'aliasName' => NULL,
      ),
      'decodeFile' => 
      array (
        'name' => 'decodeFile',
        'parameters' => 
        array (
          'file' => 
          array (
            'name' => 'file',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'string',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 51,
            'endLine' => 51,
            'startColumn' => 36,
            'endColumn' => 47,
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
 * Converts given NEON file to PHP value.
 * @return mixed
 */',
        'startLine' => 51,
        'endLine' => 64,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Nette\\Neon',
        'declaringClassName' => 'Nette\\Neon\\Neon',
        'implementingClassName' => 'Nette\\Neon\\Neon',
        'currentClassName' => 'Nette\\Neon\\Neon',
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