<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/src/Type/Php/OpenSslCipherMethodsProvider.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\Type\Php\OpenSslCipherMethodsProvider
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-e7c83a585da0b7fd99ad3e3abb9a2f6c224c952332924a1192e5c4f2f1d5ec4d',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\Type\\Php\\OpenSslCipherMethodsProvider',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Php/OpenSslCipherMethodsProvider.php',
      ),
    ),
    'namespace' => 'PHPStan\\Type\\Php',
    'name' => 'PHPStan\\Type\\Php\\OpenSslCipherMethodsProvider',
    'shortName' => 'OpenSslCipherMethodsProvider',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => NULL,
    'attributes' => 
    array (
      0 => 
      array (
        'name' => 'PHPStan\\DependencyInjection\\AutowiredService',
        'isRepeated' => false,
        'arguments' => 
        array (
        ),
      ),
    ),
    'startLine' => 15,
    'endLine' => 57,
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
    ),
    'immediateProperties' => 
    array (
      'supportedCipherMethods' => 
      array (
        'declaringClassName' => 'PHPStan\\Type\\Php\\OpenSslCipherMethodsProvider',
        'implementingClassName' => 'PHPStan\\Type\\Php\\OpenSslCipherMethodsProvider',
        'name' => 'supportedCipherMethods',
        'modifiers' => 4,
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
                  'name' => 'array',
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
        'default' => 
        array (
          'code' => 'null',
          'attributes' => 
          array (
            'startLine' => 20,
            'endLine' => 20,
            'startTokenPos' => 100,
            'startFilePos' => 487,
            'endTokenPos' => 100,
            'endFilePos' => 490,
          ),
        ),
        'docComment' => '/** @var list<string>|null */',
        'attributes' => 
        array (
        ),
        'startLine' => 20,
        'endLine' => 20,
        'startColumn' => 2,
        'endColumn' => 47,
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
      'getSupportedCipherMethods' => 
      array (
        'name' => 'getSupportedCipherMethods',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Returns supported cipher methods in lowercase.
 *
 * Filters out methods that openssl_get_cipher_methods() reports
 * but are not actually usable due to https://github.com/php/php-src/issues/19994
 *
 * @return list<string>
 */',
        'startLine' => 30,
        'endLine' => 50,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type\\Php',
        'declaringClassName' => 'PHPStan\\Type\\Php\\OpenSslCipherMethodsProvider',
        'implementingClassName' => 'PHPStan\\Type\\Php\\OpenSslCipherMethodsProvider',
        'currentClassName' => 'PHPStan\\Type\\Php\\OpenSslCipherMethodsProvider',
        'aliasName' => NULL,
      ),
      'isSupportedCipherMethod' => 
      array (
        'name' => 'isSupportedCipherMethod',
        'parameters' => 
        array (
          'method' => 
          array (
            'name' => 'method',
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
            'startLine' => 52,
            'endLine' => 52,
            'startColumn' => 42,
            'endColumn' => 55,
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
        'docComment' => NULL,
        'startLine' => 52,
        'endLine' => 55,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type\\Php',
        'declaringClassName' => 'PHPStan\\Type\\Php\\OpenSslCipherMethodsProvider',
        'implementingClassName' => 'PHPStan\\Type\\Php\\OpenSslCipherMethodsProvider',
        'currentClassName' => 'PHPStan\\Type\\Php\\OpenSslCipherMethodsProvider',
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