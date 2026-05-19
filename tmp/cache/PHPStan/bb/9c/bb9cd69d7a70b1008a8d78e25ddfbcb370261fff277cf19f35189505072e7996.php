<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/vendor/nette/utils/src/Utils/Validators.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Nette\Utils\Validators
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-c8f75d36bee54a69481750e7a5f737af6a1432eac21c301d482d6326c4b16893',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Nette\\Utils\\Validators',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/vendor/nette/utils/src/Utils/Validators.php',
      ),
    ),
    'namespace' => 'Nette\\Utils',
    'name' => 'Nette\\Utils\\Validators',
    'shortName' => 'Validators',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Validation utilities.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 18,
    'endLine' => 425,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
    ),
    'traitClassNames' => 
    array (
      0 => 'Nette\\StaticClass',
    ),
    'immediateConstants' => 
    array (
      'BuiltinTypes' => 
      array (
        'declaringClassName' => 'Nette\\Utils\\Validators',
        'implementingClassName' => 'Nette\\Utils\\Validators',
        'name' => 'BuiltinTypes',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'string\' => 1, \'int\' => 1, \'float\' => 1, \'bool\' => 1, \'array\' => 1, \'object\' => 1, \'callable\' => 1, \'iterable\' => 1, \'void\' => 1, \'null\' => 1, \'mixed\' => 1, \'false\' => 1, \'never\' => 1, \'true\' => 1]',
          'attributes' => 
          array (
            'startLine' => 22,
            'endLine' => 26,
            'startTokenPos' => 43,
            'startFilePos' => 309,
            'endTokenPos' => 143,
            'endFilePos' => 516,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 22,
        'endLine' => 26,
        'startColumn' => 2,
        'endColumn' => 3,
      ),
    ),
    'immediateProperties' => 
    array (
      'validators' => 
      array (
        'declaringClassName' => 'Nette\\Utils\\Validators',
        'implementingClassName' => 'Nette\\Utils\\Validators',
        'name' => 'validators',
        'modifiers' => 18,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[
    // PHP types
    \'array\' => \'is_array\',
    \'bool\' => \'is_bool\',
    \'boolean\' => \'is_bool\',
    \'float\' => \'is_float\',
    \'int\' => \'is_int\',
    \'integer\' => \'is_int\',
    \'null\' => \'is_null\',
    \'object\' => \'is_object\',
    \'resource\' => \'is_resource\',
    \'scalar\' => \'is_scalar\',
    \'string\' => \'is_string\',
    // pseudo-types
    \'callable\' => [self::class, \'isCallable\'],
    \'iterable\' => \'is_iterable\',
    \'list\' => [\\Nette\\Utils\\Arrays::class, \'isList\'],
    \'mixed\' => [self::class, \'isMixed\'],
    \'none\' => [self::class, \'isNone\'],
    \'number\' => [self::class, \'isNumber\'],
    \'numeric\' => [self::class, \'isNumeric\'],
    \'numericint\' => [self::class, \'isNumericInt\'],
    // string patterns
    \'alnum\' => \'ctype_alnum\',
    \'alpha\' => \'ctype_alpha\',
    \'digit\' => \'ctype_digit\',
    \'lower\' => \'ctype_lower\',
    \'pattern\' => null,
    \'space\' => \'ctype_space\',
    \'unicode\' => [self::class, \'isUnicode\'],
    \'upper\' => \'ctype_upper\',
    \'xdigit\' => \'ctype_xdigit\',
    // syntax validation
    \'email\' => [self::class, \'isEmail\'],
    \'identifier\' => [self::class, \'isPhpIdentifier\'],
    \'uri\' => [self::class, \'isUri\'],
    \'url\' => [self::class, \'isUrl\'],
    // environment validation
    \'class\' => \'class_exists\',
    \'interface\' => \'interface_exists\',
    \'directory\' => \'is_dir\',
    \'file\' => \'is_file\',
    \'type\' => [self::class, \'isType\'],
]',
          'attributes' => 
          array (
            'startLine' => 29,
            'endLine' => 76,
            'startTokenPos' => 156,
            'startFilePos' => 589,
            'endTokenPos' => 518,
            'endFilePos' => 1881,
          ),
        ),
        'docComment' => '/** @var array<string,?callable> */',
        'attributes' => 
        array (
        ),
        'startLine' => 29,
        'endLine' => 76,
        'startColumn' => 2,
        'endColumn' => 3,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'counters' => 
      array (
        'declaringClassName' => 'Nette\\Utils\\Validators',
        'implementingClassName' => 'Nette\\Utils\\Validators',
        'name' => 'counters',
        'modifiers' => 18,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[\'string\' => \'strlen\', \'unicode\' => [\\Nette\\Utils\\Strings::class, \'length\'], \'array\' => \'count\', \'list\' => \'count\', \'alnum\' => \'strlen\', \'alpha\' => \'strlen\', \'digit\' => \'strlen\', \'lower\' => \'strlen\', \'space\' => \'strlen\', \'upper\' => \'strlen\', \'xdigit\' => \'strlen\']',
          'attributes' => 
          array (
            'startLine' => 79,
            'endLine' => 91,
            'startTokenPos' => 531,
            'startFilePos' => 1951,
            'endTokenPos' => 617,
            'endFilePos' => 2226,
          ),
        ),
        'docComment' => '/** @var array<string,callable> */',
        'attributes' => 
        array (
        ),
        'startLine' => 79,
        'endLine' => 91,
        'startColumn' => 2,
        'endColumn' => 3,
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
      'assert' => 
      array (
        'name' => 'assert',
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
            'startLine' => 99,
            'endLine' => 99,
            'startColumn' => 32,
            'endColumn' => 37,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'expected' => 
          array (
            'name' => 'expected',
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
            'startLine' => 99,
            'endLine' => 99,
            'startColumn' => 40,
            'endColumn' => 55,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'label' => 
          array (
            'name' => 'label',
            'default' => 
            array (
              'code' => '\'variable\'',
              'attributes' => 
              array (
                'startLine' => 99,
                'endLine' => 99,
                'startTokenPos' => 644,
                'startFilePos' => 2439,
                'endTokenPos' => 644,
                'endFilePos' => 2448,
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
            'startLine' => 99,
            'endLine' => 99,
            'startColumn' => 58,
            'endColumn' => 83,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Verifies that the value is of expected types separated by pipe.
 * @param  mixed  $value
 * @throws AssertionException
 */',
        'startLine' => 99,
        'endLine' => 113,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Nette\\Utils',
        'declaringClassName' => 'Nette\\Utils\\Validators',
        'implementingClassName' => 'Nette\\Utils\\Validators',
        'currentClassName' => 'Nette\\Utils\\Validators',
        'aliasName' => NULL,
      ),
      'assertField' => 
      array (
        'name' => 'assertField',
        'parameters' => 
        array (
          'array' => 
          array (
            'name' => 'array',
            'default' => NULL,
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
            'startLine' => 123,
            'endLine' => 123,
            'startColumn' => 3,
            'endColumn' => 14,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'key' => 
          array (
            'name' => 'key',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 124,
            'endLine' => 124,
            'startColumn' => 3,
            'endColumn' => 6,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'expected' => 
          array (
            'name' => 'expected',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 125,
                'endLine' => 125,
                'startTokenPos' => 881,
                'startFilePos' => 3301,
                'endTokenPos' => 881,
                'endFilePos' => 3304,
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
                      'name' => 'string',
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
            'startLine' => 125,
            'endLine' => 125,
            'startColumn' => 3,
            'endColumn' => 26,
            'parameterIndex' => 2,
            'isOptional' => true,
          ),
          'label' => 
          array (
            'name' => 'label',
            'default' => 
            array (
              'code' => '"item \'%\' in array"',
              'attributes' => 
              array (
                'startLine' => 126,
                'endLine' => 126,
                'startTokenPos' => 890,
                'startFilePos' => 3325,
                'endTokenPos' => 890,
                'endFilePos' => 3343,
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
            'startLine' => 126,
            'endLine' => 126,
            'startColumn' => 3,
            'endColumn' => 37,
            'parameterIndex' => 3,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Verifies that element $key in array is of expected types separated by pipe.
 * @param  mixed[]  $array
 * @param  int|string  $key
 * @throws AssertionException
 */',
        'startLine' => 122,
        'endLine' => 135,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Nette\\Utils',
        'declaringClassName' => 'Nette\\Utils\\Validators',
        'implementingClassName' => 'Nette\\Utils\\Validators',
        'currentClassName' => 'Nette\\Utils\\Validators',
        'aliasName' => NULL,
      ),
      'is' => 
      array (
        'name' => 'is',
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
            'startLine' => 142,
            'endLine' => 142,
            'startColumn' => 28,
            'endColumn' => 33,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'expected' => 
          array (
            'name' => 'expected',
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
            'startLine' => 142,
            'endLine' => 142,
            'startColumn' => 36,
            'endColumn' => 51,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
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
        'docComment' => '/**
 * Verifies that the value is of expected types separated by pipe.
 * @param  mixed  $value
 */',
        'startLine' => 142,
        'endLine' => 197,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Nette\\Utils',
        'declaringClassName' => 'Nette\\Utils\\Validators',
        'implementingClassName' => 'Nette\\Utils\\Validators',
        'currentClassName' => 'Nette\\Utils\\Validators',
        'aliasName' => NULL,
      ),
      'everyIs' => 
      array (
        'name' => 'everyIs',
        'parameters' => 
        array (
          'values' => 
          array (
            'name' => 'values',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'iterable',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 204,
            'endLine' => 204,
            'startColumn' => 33,
            'endColumn' => 48,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'expected' => 
          array (
            'name' => 'expected',
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
            'startLine' => 204,
            'endLine' => 204,
            'startColumn' => 51,
            'endColumn' => 66,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
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
        'docComment' => '/**
 * Finds whether all values are of expected types separated by pipe.
 * @param  mixed[]  $values
 */',
        'startLine' => 204,
        'endLine' => 213,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Nette\\Utils',
        'declaringClassName' => 'Nette\\Utils\\Validators',
        'implementingClassName' => 'Nette\\Utils\\Validators',
        'currentClassName' => 'Nette\\Utils\\Validators',
        'aliasName' => NULL,
      ),
      'isNumber' => 
      array (
        'name' => 'isNumber',
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
            'startLine' => 220,
            'endLine' => 220,
            'startColumn' => 34,
            'endColumn' => 39,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
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
        'docComment' => '/**
 * Checks if the value is an integer or a float.
 * @param  mixed  $value
 */',
        'startLine' => 220,
        'endLine' => 223,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Nette\\Utils',
        'declaringClassName' => 'Nette\\Utils\\Validators',
        'implementingClassName' => 'Nette\\Utils\\Validators',
        'currentClassName' => 'Nette\\Utils\\Validators',
        'aliasName' => NULL,
      ),
      'isNumericInt' => 
      array (
        'name' => 'isNumericInt',
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
            'startLine' => 230,
            'endLine' => 230,
            'startColumn' => 38,
            'endColumn' => 43,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
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
        'docComment' => '/**
 * Checks if the value is an integer or a integer written in a string.
 * @param  mixed  $value
 */',
        'startLine' => 230,
        'endLine' => 233,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Nette\\Utils',
        'declaringClassName' => 'Nette\\Utils\\Validators',
        'implementingClassName' => 'Nette\\Utils\\Validators',
        'currentClassName' => 'Nette\\Utils\\Validators',
        'aliasName' => NULL,
      ),
      'isNumeric' => 
      array (
        'name' => 'isNumeric',
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
            'startLine' => 240,
            'endLine' => 240,
            'startColumn' => 35,
            'endColumn' => 40,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
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
        'docComment' => '/**
 * Checks if the value is a number or a number written in a string.
 * @param  mixed  $value
 */',
        'startLine' => 240,
        'endLine' => 243,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Nette\\Utils',
        'declaringClassName' => 'Nette\\Utils\\Validators',
        'implementingClassName' => 'Nette\\Utils\\Validators',
        'currentClassName' => 'Nette\\Utils\\Validators',
        'aliasName' => NULL,
      ),
      'isCallable' => 
      array (
        'name' => 'isCallable',
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
            'startLine' => 250,
            'endLine' => 250,
            'startColumn' => 36,
            'endColumn' => 41,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
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
        'docComment' => '/**
 * Checks if the value is a syntactically correct callback.
 * @param  mixed  $value
 */',
        'startLine' => 250,
        'endLine' => 253,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Nette\\Utils',
        'declaringClassName' => 'Nette\\Utils\\Validators',
        'implementingClassName' => 'Nette\\Utils\\Validators',
        'currentClassName' => 'Nette\\Utils\\Validators',
        'aliasName' => NULL,
      ),
      'isUnicode' => 
      array (
        'name' => 'isUnicode',
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
            'startLine' => 260,
            'endLine' => 260,
            'startColumn' => 35,
            'endColumn' => 40,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
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
        'docComment' => '/**
 * Checks if the value is a valid UTF-8 string.
 * @param  mixed  $value
 */',
        'startLine' => 260,
        'endLine' => 263,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Nette\\Utils',
        'declaringClassName' => 'Nette\\Utils\\Validators',
        'implementingClassName' => 'Nette\\Utils\\Validators',
        'currentClassName' => 'Nette\\Utils\\Validators',
        'aliasName' => NULL,
      ),
      'isNone' => 
      array (
        'name' => 'isNone',
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
            'startLine' => 270,
            'endLine' => 270,
            'startColumn' => 32,
            'endColumn' => 37,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
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
        'docComment' => '/**
 * Checks if the value is 0, \'\', false or null.
 * @param  mixed  $value
 */',
        'startLine' => 270,
        'endLine' => 273,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Nette\\Utils',
        'declaringClassName' => 'Nette\\Utils\\Validators',
        'implementingClassName' => 'Nette\\Utils\\Validators',
        'currentClassName' => 'Nette\\Utils\\Validators',
        'aliasName' => NULL,
      ),
      'isMixed' => 
      array (
        'name' => 'isMixed',
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
        'docComment' => '/** @internal */',
        'startLine' => 277,
        'endLine' => 280,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Nette\\Utils',
        'declaringClassName' => 'Nette\\Utils\\Validators',
        'implementingClassName' => 'Nette\\Utils\\Validators',
        'currentClassName' => 'Nette\\Utils\\Validators',
        'aliasName' => NULL,
      ),
      'isList' => 
      array (
        'name' => 'isList',
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
            'startLine' => 289,
            'endLine' => 289,
            'startColumn' => 32,
            'endColumn' => 37,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
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
        'docComment' => '/**
 * Checks if a variable is a zero-based integer indexed array.
 * @param  mixed  $value
 * @deprecated  use Nette\\Utils\\Arrays::isList
 * @return ($value is list ? true : false)
 */',
        'startLine' => 289,
        'endLine' => 292,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Nette\\Utils',
        'declaringClassName' => 'Nette\\Utils\\Validators',
        'implementingClassName' => 'Nette\\Utils\\Validators',
        'currentClassName' => 'Nette\\Utils\\Validators',
        'aliasName' => NULL,
      ),
      'isInRange' => 
      array (
        'name' => 'isInRange',
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
            'startLine' => 300,
            'endLine' => 300,
            'startColumn' => 35,
            'endColumn' => 40,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'range' => 
          array (
            'name' => 'range',
            'default' => NULL,
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
            'startLine' => 300,
            'endLine' => 300,
            'startColumn' => 43,
            'endColumn' => 54,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
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
        'docComment' => '/**
 * Checks if the value is in the given range [min, max], where the upper or lower limit can be omitted (null).
 * Numbers, strings and DateTime objects can be compared.
 * @param  mixed  $value
 */',
        'startLine' => 300,
        'endLine' => 320,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Nette\\Utils',
        'declaringClassName' => 'Nette\\Utils\\Validators',
        'implementingClassName' => 'Nette\\Utils\\Validators',
        'currentClassName' => 'Nette\\Utils\\Validators',
        'aliasName' => NULL,
      ),
      'isEmail' => 
      array (
        'name' => 'isEmail',
        'parameters' => 
        array (
          'value' => 
          array (
            'name' => 'value',
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
            'startLine' => 326,
            'endLine' => 326,
            'startColumn' => 33,
            'endColumn' => 45,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
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
        'docComment' => '/**
 * Checks if the value is a valid email address. It does not verify that the domain actually exists, only the syntax is verified.
 */',
        'startLine' => 326,
        'endLine' => 339,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Nette\\Utils',
        'declaringClassName' => 'Nette\\Utils\\Validators',
        'implementingClassName' => 'Nette\\Utils\\Validators',
        'currentClassName' => 'Nette\\Utils\\Validators',
        'aliasName' => NULL,
      ),
      'isUrl' => 
      array (
        'name' => 'isUrl',
        'parameters' => 
        array (
          'value' => 
          array (
            'name' => 'value',
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
            'startLine' => 345,
            'endLine' => 345,
            'startColumn' => 31,
            'endColumn' => 43,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
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
        'docComment' => '/**
 * Checks if the value is a valid URL address.
 */',
        'startLine' => 345,
        'endLine' => 363,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Nette\\Utils',
        'declaringClassName' => 'Nette\\Utils\\Validators',
        'implementingClassName' => 'Nette\\Utils\\Validators',
        'currentClassName' => 'Nette\\Utils\\Validators',
        'aliasName' => NULL,
      ),
      'isUri' => 
      array (
        'name' => 'isUri',
        'parameters' => 
        array (
          'value' => 
          array (
            'name' => 'value',
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
            'startLine' => 369,
            'endLine' => 369,
            'startColumn' => 31,
            'endColumn' => 43,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
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
        'docComment' => '/**
 * Checks if the value is a valid URI address, that is, actually a string beginning with a syntactically valid schema.
 */',
        'startLine' => 369,
        'endLine' => 372,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Nette\\Utils',
        'declaringClassName' => 'Nette\\Utils\\Validators',
        'implementingClassName' => 'Nette\\Utils\\Validators',
        'currentClassName' => 'Nette\\Utils\\Validators',
        'aliasName' => NULL,
      ),
      'isType' => 
      array (
        'name' => 'isType',
        'parameters' => 
        array (
          'type' => 
          array (
            'name' => 'type',
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
            'startLine' => 378,
            'endLine' => 378,
            'startColumn' => 32,
            'endColumn' => 43,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
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
        'docComment' => '/**
 * Checks whether the input is a class, interface or trait.
 */',
        'startLine' => 378,
        'endLine' => 381,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Nette\\Utils',
        'declaringClassName' => 'Nette\\Utils\\Validators',
        'implementingClassName' => 'Nette\\Utils\\Validators',
        'currentClassName' => 'Nette\\Utils\\Validators',
        'aliasName' => NULL,
      ),
      'isPhpIdentifier' => 
      array (
        'name' => 'isPhpIdentifier',
        'parameters' => 
        array (
          'value' => 
          array (
            'name' => 'value',
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
            'startLine' => 387,
            'endLine' => 387,
            'startColumn' => 41,
            'endColumn' => 53,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
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
        'docComment' => '/**
 * Checks whether the input is a valid PHP identifier.
 */',
        'startLine' => 387,
        'endLine' => 390,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Nette\\Utils',
        'declaringClassName' => 'Nette\\Utils\\Validators',
        'implementingClassName' => 'Nette\\Utils\\Validators',
        'currentClassName' => 'Nette\\Utils\\Validators',
        'aliasName' => NULL,
      ),
      'isBuiltinType' => 
      array (
        'name' => 'isBuiltinType',
        'parameters' => 
        array (
          'type' => 
          array (
            'name' => 'type',
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
            'startLine' => 396,
            'endLine' => 396,
            'startColumn' => 39,
            'endColumn' => 50,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
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
        'docComment' => '/**
 * Determines if type is PHP built-in type. Otherwise, it is the class name.
 */',
        'startLine' => 396,
        'endLine' => 399,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Nette\\Utils',
        'declaringClassName' => 'Nette\\Utils\\Validators',
        'implementingClassName' => 'Nette\\Utils\\Validators',
        'currentClassName' => 'Nette\\Utils\\Validators',
        'aliasName' => NULL,
      ),
      'isClassKeyword' => 
      array (
        'name' => 'isClassKeyword',
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
            'startLine' => 405,
            'endLine' => 405,
            'startColumn' => 40,
            'endColumn' => 51,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
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
        'docComment' => '/**
 * Determines if type is special class name self/parent/static.
 */',
        'startLine' => 405,
        'endLine' => 408,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Nette\\Utils',
        'declaringClassName' => 'Nette\\Utils\\Validators',
        'implementingClassName' => 'Nette\\Utils\\Validators',
        'currentClassName' => 'Nette\\Utils\\Validators',
        'aliasName' => NULL,
      ),
      'isTypeDeclaration' => 
      array (
        'name' => 'isTypeDeclaration',
        'parameters' => 
        array (
          'type' => 
          array (
            'name' => 'type',
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
            'startLine' => 414,
            'endLine' => 414,
            'startColumn' => 43,
            'endColumn' => 54,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
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
        'docComment' => '/**
 * Checks whether the given type declaration is syntactically valid.
 */',
        'startLine' => 414,
        'endLine' => 424,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Nette\\Utils',
        'declaringClassName' => 'Nette\\Utils\\Validators',
        'implementingClassName' => 'Nette\\Utils\\Validators',
        'currentClassName' => 'Nette\\Utils\\Validators',
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