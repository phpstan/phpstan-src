<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/src/Type/Php/MbFunctionsReturnTypeExtensionTrait.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\Type\Php\MbFunctionsReturnTypeExtensionTrait
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-cb6cda2ad2fb1ef86c4ad15afb49e0bf1c37d49f2cc69fc2272bf9fd3d6ffd7a',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\Type\\Php\\MbFunctionsReturnTypeExtensionTrait',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Php/MbFunctionsReturnTypeExtensionTrait.php',
      ),
    ),
    'namespace' => 'PHPStan\\Type\\Php',
    'name' => 'PHPStan\\Type\\Php\\MbFunctionsReturnTypeExtensionTrait',
    'shortName' => 'MbFunctionsReturnTypeExtensionTrait',
    'isInterface' => false,
    'isTrait' => true,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => NULL,
    'attributes' => 
    array (
    ),
    'startLine' => 16,
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
      'supportedEncodings' => 
      array (
        'declaringClassName' => 'PHPStan\\Type\\Php\\MbFunctionsReturnTypeExtensionTrait',
        'implementingClassName' => 'PHPStan\\Type\\Php\\MbFunctionsReturnTypeExtensionTrait',
        'name' => 'supportedEncodings',
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
            'startTokenPos' => 101,
            'startFilePos' => 455,
            'endTokenPos' => 101,
            'endFilePos' => 458,
          ),
        ),
        'docComment' => '/** @var string[]|null */',
        'attributes' => 
        array (
        ),
        'startLine' => 20,
        'endLine' => 20,
        'startColumn' => 2,
        'endColumn' => 43,
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
      'isSupportedEncoding' => 
      array (
        'name' => 'isSupportedEncoding',
        'parameters' => 
        array (
          'encoding' => 
          array (
            'name' => 'encoding',
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
            'startLine' => 22,
            'endLine' => 22,
            'startColumn' => 39,
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
        'docComment' => NULL,
        'startLine' => 22,
        'endLine' => 25,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'PHPStan\\Type\\Php',
        'declaringClassName' => 'PHPStan\\Type\\Php\\MbFunctionsReturnTypeExtensionTrait',
        'implementingClassName' => 'PHPStan\\Type\\Php\\MbFunctionsReturnTypeExtensionTrait',
        'currentClassName' => 'PHPStan\\Type\\Php\\MbFunctionsReturnTypeExtensionTrait',
        'aliasName' => NULL,
      ),
      'getSupportedEncodings' => 
      array (
        'name' => 'getSupportedEncodings',
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
        'docComment' => '/** @return string[] */',
        'startLine' => 28,
        'endLine' => 55,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'PHPStan\\Type\\Php',
        'declaringClassName' => 'PHPStan\\Type\\Php\\MbFunctionsReturnTypeExtensionTrait',
        'implementingClassName' => 'PHPStan\\Type\\Php\\MbFunctionsReturnTypeExtensionTrait',
        'currentClassName' => 'PHPStan\\Type\\Php\\MbFunctionsReturnTypeExtensionTrait',
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