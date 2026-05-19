<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/vendor/nette/utils/src/Utils/Json.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Nette\Utils\Json
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-e6982c9faffb4af94af55d489a0259ed82608d6eea7c9a3edef2185bb1308aa5',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Nette\\Utils\\Json',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/vendor/nette/utils/src/Utils/Json.php',
      ),
    ),
    'namespace' => 'Nette\\Utils',
    'name' => 'Nette\\Utils\\Json',
    'shortName' => 'Json',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * JSON encoder and decoder.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 18,
    'endLine' => 63,
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
      'FORCE_ARRAY' => 
      array (
        'declaringClassName' => 'Nette\\Utils\\Json',
        'implementingClassName' => 'Nette\\Utils\\Json',
        'name' => 'FORCE_ARRAY',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => 'JSON_OBJECT_AS_ARRAY',
          'attributes' => 
          array (
            'startLine' => 22,
            'endLine' => 22,
            'startTokenPos' => 45,
            'startFilePos' => 311,
            'endTokenPos' => 45,
            'endFilePos' => 330,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 22,
        'endLine' => 22,
        'startColumn' => 2,
        'endColumn' => 49,
      ),
      'PRETTY' => 
      array (
        'declaringClassName' => 'Nette\\Utils\\Json',
        'implementingClassName' => 'Nette\\Utils\\Json',
        'name' => 'PRETTY',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => 'JSON_PRETTY_PRINT',
          'attributes' => 
          array (
            'startLine' => 23,
            'endLine' => 23,
            'startTokenPos' => 56,
            'startFilePos' => 356,
            'endTokenPos' => 56,
            'endFilePos' => 372,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 23,
        'endLine' => 23,
        'startColumn' => 2,
        'endColumn' => 41,
      ),
      'ESCAPE_UNICODE' => 
      array (
        'declaringClassName' => 'Nette\\Utils\\Json',
        'implementingClassName' => 'Nette\\Utils\\Json',
        'name' => 'ESCAPE_UNICODE',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '1 << 19',
          'attributes' => 
          array (
            'startLine' => 24,
            'endLine' => 24,
            'startTokenPos' => 67,
            'startFilePos' => 406,
            'endTokenPos' => 71,
            'endFilePos' => 412,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 24,
        'endLine' => 24,
        'startColumn' => 2,
        'endColumn' => 39,
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
            'startLine' => 33,
            'endLine' => 33,
            'startColumn' => 32,
            'endColumn' => 37,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'flags' => 
          array (
            'name' => 'flags',
            'default' => 
            array (
              'code' => '0',
              'attributes' => 
              array (
                'startLine' => 33,
                'endLine' => 33,
                'startTokenPos' => 93,
                'startFilePos' => 694,
                'endTokenPos' => 93,
                'endFilePos' => 694,
              ),
            ),
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'int',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 33,
            'endLine' => 33,
            'startColumn' => 40,
            'endColumn' => 53,
            'parameterIndex' => 1,
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
 * Converts value to JSON format. The flag can be Json::PRETTY, which formats JSON for easier reading and clarity,
 * and Json::ESCAPE_UNICODE for ASCII output.
 * @param  mixed  $value
 * @throws JsonException
 */',
        'startLine' => 33,
        'endLine' => 46,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Nette\\Utils',
        'declaringClassName' => 'Nette\\Utils\\Json',
        'implementingClassName' => 'Nette\\Utils\\Json',
        'currentClassName' => 'Nette\\Utils\\Json',
        'aliasName' => NULL,
      ),
      'decode' => 
      array (
        'name' => 'decode',
        'parameters' => 
        array (
          'json' => 
          array (
            'name' => 'json',
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
            'startLine' => 54,
            'endLine' => 54,
            'startColumn' => 32,
            'endColumn' => 43,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'flags' => 
          array (
            'name' => 'flags',
            'default' => 
            array (
              'code' => '0',
              'attributes' => 
              array (
                'startLine' => 54,
                'endLine' => 54,
                'startTokenPos' => 232,
                'startFilePos' => 1368,
                'endTokenPos' => 232,
                'endFilePos' => 1368,
              ),
            ),
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'int',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 54,
            'endLine' => 54,
            'startColumn' => 46,
            'endColumn' => 59,
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
 * Parses JSON to PHP value. The flag can be Json::FORCE_ARRAY, which forces an array instead of an object as the return value.
 * @return mixed
 * @throws JsonException
 */',
        'startLine' => 54,
        'endLine' => 62,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Nette\\Utils',
        'declaringClassName' => 'Nette\\Utils\\Json',
        'implementingClassName' => 'Nette\\Utils\\Json',
        'currentClassName' => 'Nette\\Utils\\Json',
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