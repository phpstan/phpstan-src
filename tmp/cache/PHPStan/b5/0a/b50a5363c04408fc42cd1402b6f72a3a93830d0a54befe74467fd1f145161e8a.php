<?php declare(strict_types = 1);

// osfsl-/home/runner/work/phpstan-src/phpstan-src/vendor/composer/../symfony/console/Input/InputOption.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Symfony\Component\Console\Input\InputOption
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-3df401ca3ff87a17e8b2c351919a8682c459f1f151b9af46e4e7a8cdc4621999-8.4.21-6.70.0.1',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Symfony\\Component\\Console\\Input\\InputOption',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/vendor/composer/../symfony/console/Input/InputOption.php',
      ),
    ),
    'namespace' => 'Symfony\\Component\\Console\\Input',
    'name' => 'Symfony\\Component\\Console\\Input\\InputOption',
    'shortName' => 'InputOption',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Represents a command line option.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 22,
    'endLine' => 231,
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
      'VALUE_NONE' => 
      array (
        'declaringClassName' => 'Symfony\\Component\\Console\\Input\\InputOption',
        'implementingClassName' => 'Symfony\\Component\\Console\\Input\\InputOption',
        'name' => 'VALUE_NONE',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '1',
          'attributes' => 
          array (
            'startLine' => 27,
            'endLine' => 27,
            'startTokenPos' => 37,
            'startFilePos' => 666,
            'endTokenPos' => 37,
            'endFilePos' => 666,
          ),
        ),
        'docComment' => '/**
 * Do not accept input for the option (e.g. --yell). This is the default behavior of options.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 27,
        'endLine' => 27,
        'startColumn' => 5,
        'endColumn' => 32,
      ),
      'VALUE_REQUIRED' => 
      array (
        'declaringClassName' => 'Symfony\\Component\\Console\\Input\\InputOption',
        'implementingClassName' => 'Symfony\\Component\\Console\\Input\\InputOption',
        'name' => 'VALUE_REQUIRED',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '2',
          'attributes' => 
          array (
            'startLine' => 32,
            'endLine' => 32,
            'startTokenPos' => 50,
            'startFilePos' => 804,
            'endTokenPos' => 50,
            'endFilePos' => 804,
          ),
        ),
        'docComment' => '/**
 * A value must be passed when the option is used (e.g. --iterations=5 or -i5).
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 32,
        'endLine' => 32,
        'startColumn' => 5,
        'endColumn' => 36,
      ),
      'VALUE_OPTIONAL' => 
      array (
        'declaringClassName' => 'Symfony\\Component\\Console\\Input\\InputOption',
        'implementingClassName' => 'Symfony\\Component\\Console\\Input\\InputOption',
        'name' => 'VALUE_OPTIONAL',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '4',
          'attributes' => 
          array (
            'startLine' => 37,
            'endLine' => 37,
            'startTokenPos' => 63,
            'startFilePos' => 934,
            'endTokenPos' => 63,
            'endFilePos' => 934,
          ),
        ),
        'docComment' => '/**
 * The option may or may not have a value (e.g. --yell or --yell=loud).
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 37,
        'endLine' => 37,
        'startColumn' => 5,
        'endColumn' => 36,
      ),
      'VALUE_IS_ARRAY' => 
      array (
        'declaringClassName' => 'Symfony\\Component\\Console\\Input\\InputOption',
        'implementingClassName' => 'Symfony\\Component\\Console\\Input\\InputOption',
        'name' => 'VALUE_IS_ARRAY',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '8',
          'attributes' => 
          array (
            'startLine' => 42,
            'endLine' => 42,
            'startTokenPos' => 76,
            'startFilePos' => 1060,
            'endTokenPos' => 76,
            'endFilePos' => 1060,
          ),
        ),
        'docComment' => '/**
 * The option accepts multiple values (e.g. --dir=/foo --dir=/bar).
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 42,
        'endLine' => 42,
        'startColumn' => 5,
        'endColumn' => 36,
      ),
      'VALUE_NEGATABLE' => 
      array (
        'declaringClassName' => 'Symfony\\Component\\Console\\Input\\InputOption',
        'implementingClassName' => 'Symfony\\Component\\Console\\Input\\InputOption',
        'name' => 'VALUE_NEGATABLE',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '16',
          'attributes' => 
          array (
            'startLine' => 47,
            'endLine' => 47,
            'startTokenPos' => 89,
            'startFilePos' => 1204,
            'endTokenPos' => 89,
            'endFilePos' => 1205,
          ),
        ),
        'docComment' => '/**
 * The option may have either positive or negative value (e.g. --ansi or --no-ansi).
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 47,
        'endLine' => 47,
        'startColumn' => 5,
        'endColumn' => 38,
      ),
    ),
    'immediateProperties' => 
    array (
      'name' => 
      array (
        'declaringClassName' => 'Symfony\\Component\\Console\\Input\\InputOption',
        'implementingClassName' => 'Symfony\\Component\\Console\\Input\\InputOption',
        'name' => 'name',
        'modifiers' => 4,
        'type' => NULL,
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 49,
        'endLine' => 49,
        'startColumn' => 5,
        'endColumn' => 18,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'shortcut' => 
      array (
        'declaringClassName' => 'Symfony\\Component\\Console\\Input\\InputOption',
        'implementingClassName' => 'Symfony\\Component\\Console\\Input\\InputOption',
        'name' => 'shortcut',
        'modifiers' => 4,
        'type' => NULL,
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 50,
        'endLine' => 50,
        'startColumn' => 5,
        'endColumn' => 22,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'mode' => 
      array (
        'declaringClassName' => 'Symfony\\Component\\Console\\Input\\InputOption',
        'implementingClassName' => 'Symfony\\Component\\Console\\Input\\InputOption',
        'name' => 'mode',
        'modifiers' => 4,
        'type' => NULL,
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 51,
        'endLine' => 51,
        'startColumn' => 5,
        'endColumn' => 18,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'default' => 
      array (
        'declaringClassName' => 'Symfony\\Component\\Console\\Input\\InputOption',
        'implementingClassName' => 'Symfony\\Component\\Console\\Input\\InputOption',
        'name' => 'default',
        'modifiers' => 4,
        'type' => NULL,
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 52,
        'endLine' => 52,
        'startColumn' => 5,
        'endColumn' => 21,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'description' => 
      array (
        'declaringClassName' => 'Symfony\\Component\\Console\\Input\\InputOption',
        'implementingClassName' => 'Symfony\\Component\\Console\\Input\\InputOption',
        'name' => 'description',
        'modifiers' => 4,
        'type' => NULL,
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 53,
        'endLine' => 53,
        'startColumn' => 5,
        'endColumn' => 25,
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
          'name' => 
          array (
            'name' => 'name',
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
            'startLine' => 62,
            'endLine' => 62,
            'startColumn' => 33,
            'endColumn' => 44,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'shortcut' => 
          array (
            'name' => 'shortcut',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 62,
                'endLine' => 62,
                'startTokenPos' => 134,
                'startFilePos' => 1843,
                'endTokenPos' => 134,
                'endFilePos' => 1846,
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
            'startColumn' => 47,
            'endColumn' => 62,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
          'mode' => 
          array (
            'name' => 'mode',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 62,
                'endLine' => 62,
                'startTokenPos' => 144,
                'startFilePos' => 1862,
                'endTokenPos' => 144,
                'endFilePos' => 1865,
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
                      'name' => 'int',
                      'isIdentifier' => true,
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
            'startLine' => 62,
            'endLine' => 62,
            'startColumn' => 65,
            'endColumn' => 81,
            'parameterIndex' => 2,
            'isOptional' => true,
          ),
          'description' => 
          array (
            'name' => 'description',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 62,
                'endLine' => 62,
                'startTokenPos' => 153,
                'startFilePos' => 1890,
                'endTokenPos' => 153,
                'endFilePos' => 1891,
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
            'startLine' => 62,
            'endLine' => 62,
            'startColumn' => 84,
            'endColumn' => 107,
            'parameterIndex' => 3,
            'isOptional' => true,
          ),
          'default' => 
          array (
            'name' => 'default',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 62,
                'endLine' => 62,
                'startTokenPos' => 160,
                'startFilePos' => 1905,
                'endTokenPos' => 160,
                'endFilePos' => 1908,
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
            'startColumn' => 110,
            'endColumn' => 124,
            'parameterIndex' => 4,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @param string|array|null                $shortcut The shortcuts, can be null, a string of shortcuts delimited by | or an array of shortcuts
 * @param int|null                         $mode     The option mode: One of the VALUE_* constants
 * @param string|bool|int|float|array|null $default  The default value (must be null for self::VALUE_NONE)
 *
 * @throws InvalidArgumentException If option mode is invalid or incompatible
 */',
        'startLine' => 62,
        'endLine' => 108,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Symfony\\Component\\Console\\Input',
        'declaringClassName' => 'Symfony\\Component\\Console\\Input\\InputOption',
        'implementingClassName' => 'Symfony\\Component\\Console\\Input\\InputOption',
        'currentClassName' => 'Symfony\\Component\\Console\\Input\\InputOption',
        'aliasName' => NULL,
      ),
      'getShortcut' => 
      array (
        'name' => 'getShortcut',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Returns the option shortcut.
 *
 * @return string|null
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
        'namespace' => 'Symfony\\Component\\Console\\Input',
        'declaringClassName' => 'Symfony\\Component\\Console\\Input\\InputOption',
        'implementingClassName' => 'Symfony\\Component\\Console\\Input\\InputOption',
        'currentClassName' => 'Symfony\\Component\\Console\\Input\\InputOption',
        'aliasName' => NULL,
      ),
      'getName' => 
      array (
        'name' => 'getName',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Returns the option name.
 *
 * @return string
 */',
        'startLine' => 125,
        'endLine' => 128,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Symfony\\Component\\Console\\Input',
        'declaringClassName' => 'Symfony\\Component\\Console\\Input\\InputOption',
        'implementingClassName' => 'Symfony\\Component\\Console\\Input\\InputOption',
        'currentClassName' => 'Symfony\\Component\\Console\\Input\\InputOption',
        'aliasName' => NULL,
      ),
      'acceptValue' => 
      array (
        'name' => 'acceptValue',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Returns true if the option accepts a value.
 *
 * @return bool true if value mode is not self::VALUE_NONE, false otherwise
 */',
        'startLine' => 135,
        'endLine' => 138,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Symfony\\Component\\Console\\Input',
        'declaringClassName' => 'Symfony\\Component\\Console\\Input\\InputOption',
        'implementingClassName' => 'Symfony\\Component\\Console\\Input\\InputOption',
        'currentClassName' => 'Symfony\\Component\\Console\\Input\\InputOption',
        'aliasName' => NULL,
      ),
      'isValueRequired' => 
      array (
        'name' => 'isValueRequired',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Returns true if the option requires a value.
 *
 * @return bool true if value mode is self::VALUE_REQUIRED, false otherwise
 */',
        'startLine' => 145,
        'endLine' => 148,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Symfony\\Component\\Console\\Input',
        'declaringClassName' => 'Symfony\\Component\\Console\\Input\\InputOption',
        'implementingClassName' => 'Symfony\\Component\\Console\\Input\\InputOption',
        'currentClassName' => 'Symfony\\Component\\Console\\Input\\InputOption',
        'aliasName' => NULL,
      ),
      'isValueOptional' => 
      array (
        'name' => 'isValueOptional',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Returns true if the option takes an optional value.
 *
 * @return bool true if value mode is self::VALUE_OPTIONAL, false otherwise
 */',
        'startLine' => 155,
        'endLine' => 158,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Symfony\\Component\\Console\\Input',
        'declaringClassName' => 'Symfony\\Component\\Console\\Input\\InputOption',
        'implementingClassName' => 'Symfony\\Component\\Console\\Input\\InputOption',
        'currentClassName' => 'Symfony\\Component\\Console\\Input\\InputOption',
        'aliasName' => NULL,
      ),
      'isArray' => 
      array (
        'name' => 'isArray',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Returns true if the option can take multiple values.
 *
 * @return bool true if mode is self::VALUE_IS_ARRAY, false otherwise
 */',
        'startLine' => 165,
        'endLine' => 168,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Symfony\\Component\\Console\\Input',
        'declaringClassName' => 'Symfony\\Component\\Console\\Input\\InputOption',
        'implementingClassName' => 'Symfony\\Component\\Console\\Input\\InputOption',
        'currentClassName' => 'Symfony\\Component\\Console\\Input\\InputOption',
        'aliasName' => NULL,
      ),
      'isNegatable' => 
      array (
        'name' => 'isNegatable',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 170,
        'endLine' => 173,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Symfony\\Component\\Console\\Input',
        'declaringClassName' => 'Symfony\\Component\\Console\\Input\\InputOption',
        'implementingClassName' => 'Symfony\\Component\\Console\\Input\\InputOption',
        'currentClassName' => 'Symfony\\Component\\Console\\Input\\InputOption',
        'aliasName' => NULL,
      ),
      'setDefault' => 
      array (
        'name' => 'setDefault',
        'parameters' => 
        array (
          'default' => 
          array (
            'name' => 'default',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 178,
                'endLine' => 178,
                'startTokenPos' => 770,
                'startFilePos' => 5254,
                'endTokenPos' => 770,
                'endFilePos' => 5257,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 178,
            'endLine' => 178,
            'startColumn' => 32,
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
 * @param string|bool|int|float|array|null $default
 */',
        'startLine' => 178,
        'endLine' => 193,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Symfony\\Component\\Console\\Input',
        'declaringClassName' => 'Symfony\\Component\\Console\\Input\\InputOption',
        'implementingClassName' => 'Symfony\\Component\\Console\\Input\\InputOption',
        'currentClassName' => 'Symfony\\Component\\Console\\Input\\InputOption',
        'aliasName' => NULL,
      ),
      'getDefault' => 
      array (
        'name' => 'getDefault',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Returns the default value.
 *
 * @return string|bool|int|float|array|null
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
        'namespace' => 'Symfony\\Component\\Console\\Input',
        'declaringClassName' => 'Symfony\\Component\\Console\\Input\\InputOption',
        'implementingClassName' => 'Symfony\\Component\\Console\\Input\\InputOption',
        'currentClassName' => 'Symfony\\Component\\Console\\Input\\InputOption',
        'aliasName' => NULL,
      ),
      'getDescription' => 
      array (
        'name' => 'getDescription',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Returns the description text.
 *
 * @return string
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
        'namespace' => 'Symfony\\Component\\Console\\Input',
        'declaringClassName' => 'Symfony\\Component\\Console\\Input\\InputOption',
        'implementingClassName' => 'Symfony\\Component\\Console\\Input\\InputOption',
        'currentClassName' => 'Symfony\\Component\\Console\\Input\\InputOption',
        'aliasName' => NULL,
      ),
      'equals' => 
      array (
        'name' => 'equals',
        'parameters' => 
        array (
          'option' => 
          array (
            'name' => 'option',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'self',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 220,
            'endLine' => 220,
            'startColumn' => 28,
            'endColumn' => 39,
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
 * Checks whether the given option equals this one.
 *
 * @return bool
 */',
        'startLine' => 220,
        'endLine' => 230,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Symfony\\Component\\Console\\Input',
        'declaringClassName' => 'Symfony\\Component\\Console\\Input\\InputOption',
        'implementingClassName' => 'Symfony\\Component\\Console\\Input\\InputOption',
        'currentClassName' => 'Symfony\\Component\\Console\\Input\\InputOption',
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