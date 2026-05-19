<?php declare(strict_types = 1);

// osfsl-/home/runner/work/phpstan-src/phpstan-src/vendor/composer/../ondrejmirtes/better-reflection/src/Reflection/Adapter/ReflectionClass.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\BetterReflection\Reflection\Adapter\ReflectionClass
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-550ed353381158e84abcafd77662102b0e3550218eaf67e2390d945192314b0c-8.4.21-6.70.0.1',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/vendor/composer/../ondrejmirtes/better-reflection/src/Reflection/Adapter/ReflectionClass.php',
      ),
    ),
    'namespace' => 'PHPStan\\BetterReflection\\Reflection\\Adapter',
    'name' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
    'shortName' => 'ReflectionClass',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * @template-extends CoreReflectionClass<object>
 * @psalm-suppress PropertyNotSetInConstructor
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 37,
    'endLine' => 806,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'ReflectionClass',
    'implementsClassNames' => 
    array (
    ),
    'traitClassNames' => 
    array (
    ),
    'immediateConstants' => 
    array (
      'SKIP_INITIALIZATION_ON_SERIALIZE_COMPATIBILITY' => 
      array (
        'declaringClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'implementingClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'name' => 'SKIP_INITIALIZATION_ON_SERIALIZE_COMPATIBILITY',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '8',
          'attributes' => 
          array (
            'startLine' => 48,
            'endLine' => 48,
            'startTokenPos' => 226,
            'startFilePos' => 1726,
            'endTokenPos' => 226,
            'endFilePos' => 1726,
          ),
        ),
        'docComment' => '/**
 * @internal
 *
 * @see CoreReflectionClass::SKIP_INITIALIZATION_ON_SERIALIZE_COMPATIBILITY
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 48,
        'endLine' => 48,
        'startColumn' => 5,
        'endColumn' => 68,
      ),
      'SKIP_DESTRUCTOR_COMPATIBILITY' => 
      array (
        'declaringClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'implementingClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'name' => 'SKIP_DESTRUCTOR_COMPATIBILITY',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '16',
          'attributes' => 
          array (
            'startLine' => 55,
            'endLine' => 55,
            'startTokenPos' => 239,
            'startFilePos' => 1868,
            'endTokenPos' => 239,
            'endFilePos' => 1869,
          ),
        ),
        'docComment' => '/**
 * @internal
 *
 * @see CoreReflectionClass::SKIP_DESTRUCTOR
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 55,
        'endLine' => 55,
        'startColumn' => 5,
        'endColumn' => 52,
      ),
      'IS_READONLY_COMPATIBILITY' => 
      array (
        'declaringClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'implementingClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'name' => 'IS_READONLY_COMPATIBILITY',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '65536',
          'attributes' => 
          array (
            'startLine' => 58,
            'endLine' => 58,
            'startTokenPos' => 252,
            'startFilePos' => 1939,
            'endTokenPos' => 252,
            'endFilePos' => 1943,
          ),
        ),
        'docComment' => '/** @internal */',
        'attributes' => 
        array (
        ),
        'startLine' => 58,
        'endLine' => 58,
        'startColumn' => 5,
        'endColumn' => 51,
      ),
    ),
    'immediateProperties' => 
    array (
      'betterReflectionClass' => 
      array (
        'declaringClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'implementingClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'name' => 'betterReflectionClass',
        'modifiers' => 4,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/**
 * @var BetterReflectionClass|BetterReflectionEnum
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 42,
        'endLine' => 42,
        'startColumn' => 5,
        'endColumn' => 35,
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
          'betterReflectionClass' => 
          array (
            'name' => 'betterReflectionClass',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 63,
            'endLine' => 63,
            'startColumn' => 33,
            'endColumn' => 54,
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
 * @param BetterReflectionClass|BetterReflectionEnum $betterReflectionClass
 */',
        'startLine' => 63,
        'endLine' => 68,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\BetterReflection\\Reflection\\Adapter',
        'declaringClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'implementingClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'currentClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'aliasName' => NULL,
      ),
      'getBetterReflection' => 
      array (
        'name' => 'getBetterReflection',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @return BetterReflectionClass|BetterReflectionEnum
 */',
        'startLine' => 73,
        'endLine' => 76,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\BetterReflection\\Reflection\\Adapter',
        'declaringClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'implementingClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'currentClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'aliasName' => NULL,
      ),
      '__toString' => 
      array (
        'name' => '__toString',
        'parameters' => 
        array (
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
        'docComment' => '/** @return non-empty-string */',
        'startLine' => 79,
        'endLine' => 82,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\BetterReflection\\Reflection\\Adapter',
        'declaringClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'implementingClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'currentClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'aliasName' => NULL,
      ),
      '__get' => 
      array (
        'name' => '__get',
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
            'startLine' => 87,
            'endLine' => 87,
            'startColumn' => 27,
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
 * @return mixed
 */',
        'startLine' => 87,
        'endLine' => 94,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\BetterReflection\\Reflection\\Adapter',
        'declaringClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'implementingClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'currentClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'aliasName' => NULL,
      ),
      'getName' => 
      array (
        'name' => 'getName',
        'parameters' => 
        array (
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
 * @psalm-mutation-free
 * @return class-string
 */',
        'startLine' => 100,
        'endLine' => 103,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\BetterReflection\\Reflection\\Adapter',
        'declaringClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'implementingClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'currentClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'aliasName' => NULL,
      ),
      'isAnonymous' => 
      array (
        'name' => 'isAnonymous',
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
        'docComment' => '/** @psalm-mutation-free */',
        'startLine' => 106,
        'endLine' => 109,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\BetterReflection\\Reflection\\Adapter',
        'declaringClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'implementingClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'currentClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'aliasName' => NULL,
      ),
      'isInternal' => 
      array (
        'name' => 'isInternal',
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
        'docComment' => '/** @psalm-mutation-free */',
        'startLine' => 112,
        'endLine' => 115,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\BetterReflection\\Reflection\\Adapter',
        'declaringClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'implementingClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'currentClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'aliasName' => NULL,
      ),
      'isUserDefined' => 
      array (
        'name' => 'isUserDefined',
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
        'docComment' => '/** @psalm-mutation-free */',
        'startLine' => 118,
        'endLine' => 121,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\BetterReflection\\Reflection\\Adapter',
        'declaringClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'implementingClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'currentClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'aliasName' => NULL,
      ),
      'isInstantiable' => 
      array (
        'name' => 'isInstantiable',
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
        'docComment' => '/** @psalm-mutation-free */',
        'startLine' => 124,
        'endLine' => 127,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\BetterReflection\\Reflection\\Adapter',
        'declaringClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'implementingClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'currentClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'aliasName' => NULL,
      ),
      'isCloneable' => 
      array (
        'name' => 'isCloneable',
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
        'docComment' => '/** @psalm-mutation-free */',
        'startLine' => 130,
        'endLine' => 133,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\BetterReflection\\Reflection\\Adapter',
        'declaringClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'implementingClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'currentClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'aliasName' => NULL,
      ),
      'getFileName' => 
      array (
        'name' => 'getFileName',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
          0 => 
          array (
            'name' => 'ReturnTypeWillChange',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => '/**
 * {@inheritDoc}
 * @return non-empty-string|false
 */',
        'startLine' => 139,
        'endLine' => 145,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\BetterReflection\\Reflection\\Adapter',
        'declaringClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'implementingClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'currentClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'aliasName' => NULL,
      ),
      'getStartLine' => 
      array (
        'name' => 'getStartLine',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
          0 => 
          array (
            'name' => 'ReturnTypeWillChange',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => '/**
 * {@inheritDoc}
 * @psalm-mutation-free
 */',
        'startLine' => 151,
        'endLine' => 155,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\BetterReflection\\Reflection\\Adapter',
        'declaringClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'implementingClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'currentClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'aliasName' => NULL,
      ),
      'getEndLine' => 
      array (
        'name' => 'getEndLine',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
          0 => 
          array (
            'name' => 'ReturnTypeWillChange',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => '/**
 * {@inheritDoc}
 * @psalm-mutation-free
 */',
        'startLine' => 161,
        'endLine' => 165,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\BetterReflection\\Reflection\\Adapter',
        'declaringClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'implementingClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'currentClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'aliasName' => NULL,
      ),
      'getDocComment' => 
      array (
        'name' => 'getDocComment',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
          0 => 
          array (
            'name' => 'ReturnTypeWillChange',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => '/**
 * {@inheritDoc}
 */',
        'startLine' => 170,
        'endLine' => 174,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\BetterReflection\\Reflection\\Adapter',
        'declaringClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'implementingClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'currentClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'aliasName' => NULL,
      ),
      'getConstructor' => 
      array (
        'name' => 'getConstructor',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
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
                  'name' => 'ReflectionMethod',
                  'isIdentifier' => false,
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
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @psalm-mutation-free
 * @return ReflectionMethod|null
 */',
        'startLine' => 180,
        'endLine' => 189,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\BetterReflection\\Reflection\\Adapter',
        'declaringClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'implementingClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'currentClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'aliasName' => NULL,
      ),
      'hasMethod' => 
      array (
        'name' => 'hasMethod',
        'parameters' => 
        array (
          'name' => 
          array (
            'name' => 'name',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 194,
            'endLine' => 194,
            'startColumn' => 31,
            'endColumn' => 35,
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
 * {@inheritDoc}
 */',
        'startLine' => 194,
        'endLine' => 201,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\BetterReflection\\Reflection\\Adapter',
        'declaringClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'implementingClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'currentClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'aliasName' => NULL,
      ),
      'getMethod' => 
      array (
        'name' => 'getMethod',
        'parameters' => 
        array (
          'name' => 
          array (
            'name' => 'name',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 207,
            'endLine' => 207,
            'startColumn' => 31,
            'endColumn' => 35,
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
            'name' => 'ReflectionMethod',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @param string $name
 * @return ReflectionMethod
 */',
        'startLine' => 207,
        'endLine' => 216,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\BetterReflection\\Reflection\\Adapter',
        'declaringClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'implementingClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'currentClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'aliasName' => NULL,
      ),
      'getMethods' => 
      array (
        'name' => 'getMethods',
        'parameters' => 
        array (
          'filter' => 
          array (
            'name' => 'filter',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 222,
                'endLine' => 222,
                'startTokenPos' => 927,
                'startFilePos' => 5879,
                'endTokenPos' => 927,
                'endFilePos' => 5882,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 222,
            'endLine' => 222,
            'startColumn' => 32,
            'endColumn' => 45,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
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
 * @param int-mask-of<ReflectionMethod::IS_*>|null $filter
 * @return list<ReflectionMethod>
 */',
        'startLine' => 222,
        'endLine' => 229,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\BetterReflection\\Reflection\\Adapter',
        'declaringClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'implementingClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'currentClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'aliasName' => NULL,
      ),
      'hasProperty' => 
      array (
        'name' => 'hasProperty',
        'parameters' => 
        array (
          'name' => 
          array (
            'name' => 'name',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 234,
            'endLine' => 234,
            'startColumn' => 33,
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
 * {@inheritDoc}
 */',
        'startLine' => 234,
        'endLine' => 241,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\BetterReflection\\Reflection\\Adapter',
        'declaringClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'implementingClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'currentClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'aliasName' => NULL,
      ),
      'getProperty' => 
      array (
        'name' => 'getProperty',
        'parameters' => 
        array (
          'name' => 
          array (
            'name' => 'name',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 247,
            'endLine' => 247,
            'startColumn' => 33,
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
            'name' => 'ReflectionProperty',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @param string $name
 * @return ReflectionProperty
 */',
        'startLine' => 247,
        'endLine' => 256,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\BetterReflection\\Reflection\\Adapter',
        'declaringClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'implementingClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'currentClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'aliasName' => NULL,
      ),
      'getProperties' => 
      array (
        'name' => 'getProperties',
        'parameters' => 
        array (
          'filter' => 
          array (
            'name' => 'filter',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 262,
                'endLine' => 262,
                'startTokenPos' => 1141,
                'startFilePos' => 7096,
                'endTokenPos' => 1141,
                'endFilePos' => 7099,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 262,
            'endLine' => 262,
            'startColumn' => 35,
            'endColumn' => 48,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
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
 * @param int-mask-of<ReflectionProperty::IS_*>|null $filter
 * @return list<ReflectionProperty>
 */',
        'startLine' => 262,
        'endLine' => 269,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\BetterReflection\\Reflection\\Adapter',
        'declaringClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'implementingClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'currentClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'aliasName' => NULL,
      ),
      'hasConstant' => 
      array (
        'name' => 'hasConstant',
        'parameters' => 
        array (
          'name' => 
          array (
            'name' => 'name',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 274,
            'endLine' => 274,
            'startColumn' => 33,
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
 * {@inheritDoc}
 */',
        'startLine' => 274,
        'endLine' => 285,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\BetterReflection\\Reflection\\Adapter',
        'declaringClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'implementingClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'currentClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'aliasName' => NULL,
      ),
      'getConstants' => 
      array (
        'name' => 'getConstants',
        'parameters' => 
        array (
          'filter' => 
          array (
            'name' => 'filter',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 294,
                'endLine' => 294,
                'startTokenPos' => 1297,
                'startFilePos' => 8018,
                'endTokenPos' => 1297,
                'endFilePos' => 8021,
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
            'startLine' => 294,
            'endLine' => 294,
            'startColumn' => 34,
            'endColumn' => 52,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
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
 * @param int-mask-of<ReflectionClassConstant::IS_*>|null $filter
 *
 * @return array<non-empty-string, mixed>
 *
 * @psalm-mutation-free
 */',
        'startLine' => 294,
        'endLine' => 301,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\BetterReflection\\Reflection\\Adapter',
        'declaringClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'implementingClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'currentClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'aliasName' => NULL,
      ),
      'getConstant' => 
      array (
        'name' => 'getConstant',
        'parameters' => 
        array (
          'name' => 
          array (
            'name' => 'name',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 307,
            'endLine' => 307,
            'startColumn' => 33,
            'endColumn' => 37,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
          0 => 
          array (
            'name' => 'ReturnTypeWillChange',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => '/**
 * @return mixed
 */',
        'startLine' => 306,
        'endLine' => 326,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\BetterReflection\\Reflection\\Adapter',
        'declaringClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'implementingClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'currentClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'aliasName' => NULL,
      ),
      'getConstantValue' => 
      array (
        'name' => 'getConstantValue',
        'parameters' => 
        array (
          'betterConstantOrEnumCase' => 
          array (
            'name' => 'betterConstantOrEnumCase',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 331,
            'endLine' => 331,
            'startColumn' => 39,
            'endColumn' => 63,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/** @psalm-pure
 * @param BetterReflectionClassConstant|BetterReflectionEnumCase $betterConstantOrEnumCase
 * @return mixed */',
        'startLine' => 331,
        'endLine' => 338,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'PHPStan\\BetterReflection\\Reflection\\Adapter',
        'declaringClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'implementingClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'currentClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'aliasName' => NULL,
      ),
      'getReflectionConstant' => 
      array (
        'name' => 'getReflectionConstant',
        'parameters' => 
        array (
          'name' => 
          array (
            'name' => 'name',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 345,
            'endLine' => 345,
            'startColumn' => 43,
            'endColumn' => 47,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
          0 => 
          array (
            'name' => 'ReturnTypeWillChange',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => '/**
 * @param string $name
 * @return ReflectionClassConstant|false
 */',
        'startLine' => 344,
        'endLine' => 364,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\BetterReflection\\Reflection\\Adapter',
        'declaringClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'implementingClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'currentClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'aliasName' => NULL,
      ),
      'getReflectionConstants' => 
      array (
        'name' => 'getReflectionConstants',
        'parameters' => 
        array (
          'filter' => 
          array (
            'name' => 'filter',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 373,
                'endLine' => 373,
                'startTokenPos' => 1673,
                'startFilePos' => 10388,
                'endTokenPos' => 1673,
                'endFilePos' => 10391,
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
            'startLine' => 373,
            'endLine' => 373,
            'startColumn' => 44,
            'endColumn' => 62,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
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
 * @param int-mask-of<ReflectionClassConstant::IS_*>|null $filter
 *
 * @return list<ReflectionClassConstant>
 *
 * @psalm-mutation-free
 */',
        'startLine' => 373,
        'endLine' => 379,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\BetterReflection\\Reflection\\Adapter',
        'declaringClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'implementingClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'currentClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'aliasName' => NULL,
      ),
      'filterBetterReflectionClassConstants' => 
      array (
        'name' => 'filterBetterReflectionClassConstants',
        'parameters' => 
        array (
          'filter' => 
          array (
            'name' => 'filter',
            'default' => NULL,
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
            'startLine' => 388,
            'endLine' => 388,
            'startColumn' => 59,
            'endColumn' => 70,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @param int-mask-of<ReflectionClassConstant::IS_*>|null $filter
 *
 * @return array<non-empty-string, BetterReflectionClassConstant|BetterReflectionEnumCase>
 *
 * @psalm-mutation-free
 */',
        'startLine' => 388,
        'endLine' => 403,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'PHPStan\\BetterReflection\\Reflection\\Adapter',
        'declaringClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'implementingClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'currentClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'aliasName' => NULL,
      ),
      'getInterfaceClassNames' => 
      array (
        'name' => 'getInterfaceClassNames',
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
        'docComment' => '/** @return list<class-string> */',
        'startLine' => 406,
        'endLine' => 409,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\BetterReflection\\Reflection\\Adapter',
        'declaringClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'implementingClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'currentClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'aliasName' => NULL,
      ),
      'getInterfaces' => 
      array (
        'name' => 'getInterfaces',
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
 * @psalm-mutation-free
 * @return array<class-string, self>
 */',
        'startLine' => 415,
        'endLine' => 422,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\BetterReflection\\Reflection\\Adapter',
        'declaringClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'implementingClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'currentClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'aliasName' => NULL,
      ),
      'getInterfaceNames' => 
      array (
        'name' => 'getInterfaceNames',
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
 * @return list<class-string>
 *
 * @psalm-mutation-free
 */',
        'startLine' => 429,
        'endLine' => 432,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\BetterReflection\\Reflection\\Adapter',
        'declaringClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'implementingClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'currentClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'aliasName' => NULL,
      ),
      'isInterface' => 
      array (
        'name' => 'isInterface',
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
        'docComment' => '/** @psalm-mutation-free */',
        'startLine' => 435,
        'endLine' => 438,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\BetterReflection\\Reflection\\Adapter',
        'declaringClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'implementingClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'currentClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'aliasName' => NULL,
      ),
      'getTraitClassNames' => 
      array (
        'name' => 'getTraitClassNames',
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
        'docComment' => '/** @return list<trait-string> */',
        'startLine' => 441,
        'endLine' => 444,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\BetterReflection\\Reflection\\Adapter',
        'declaringClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'implementingClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'currentClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'aliasName' => NULL,
      ),
      'getTraits' => 
      array (
        'name' => 'getTraits',
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
 * @psalm-mutation-free
 * @return array<trait-string, self>
 */',
        'startLine' => 450,
        'endLine' => 462,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\BetterReflection\\Reflection\\Adapter',
        'declaringClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'implementingClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'currentClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'aliasName' => NULL,
      ),
      'getTraitNames' => 
      array (
        'name' => 'getTraitNames',
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
 * @return list<trait-string>
 *
 * @psalm-mutation-free
 */',
        'startLine' => 469,
        'endLine' => 472,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\BetterReflection\\Reflection\\Adapter',
        'declaringClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'implementingClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'currentClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'aliasName' => NULL,
      ),
      'getTraitAliases' => 
      array (
        'name' => 'getTraitAliases',
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
 * @return array<non-empty-string, non-empty-string>
 *
 * @psalm-mutation-free
 */',
        'startLine' => 479,
        'endLine' => 482,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\BetterReflection\\Reflection\\Adapter',
        'declaringClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'implementingClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'currentClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'aliasName' => NULL,
      ),
      'isTrait' => 
      array (
        'name' => 'isTrait',
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
        'docComment' => '/** @psalm-mutation-free */',
        'startLine' => 485,
        'endLine' => 488,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\BetterReflection\\Reflection\\Adapter',
        'declaringClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'implementingClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'currentClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'aliasName' => NULL,
      ),
      'isAbstract' => 
      array (
        'name' => 'isAbstract',
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
        'docComment' => '/** @psalm-mutation-free */',
        'startLine' => 491,
        'endLine' => 494,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\BetterReflection\\Reflection\\Adapter',
        'declaringClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'implementingClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'currentClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'aliasName' => NULL,
      ),
      'isFinal' => 
      array (
        'name' => 'isFinal',
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
        'docComment' => '/** @psalm-mutation-free */',
        'startLine' => 497,
        'endLine' => 500,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\BetterReflection\\Reflection\\Adapter',
        'declaringClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'implementingClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'currentClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'aliasName' => NULL,
      ),
      'isReadOnly' => 
      array (
        'name' => 'isReadOnly',
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
        'docComment' => '/** @psalm-mutation-free */',
        'startLine' => 503,
        'endLine' => 506,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\BetterReflection\\Reflection\\Adapter',
        'declaringClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'implementingClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'currentClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'aliasName' => NULL,
      ),
      'getModifiers' => 
      array (
        'name' => 'getModifiers',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'int',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/** @psalm-mutation-free */',
        'startLine' => 509,
        'endLine' => 512,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\BetterReflection\\Reflection\\Adapter',
        'declaringClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'implementingClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'currentClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'aliasName' => NULL,
      ),
      'isInstance' => 
      array (
        'name' => 'isInstance',
        'parameters' => 
        array (
          'object' => 
          array (
            'name' => 'object',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 517,
            'endLine' => 517,
            'startColumn' => 32,
            'endColumn' => 38,
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
 * {@inheritDoc}
 */',
        'startLine' => 517,
        'endLine' => 520,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\BetterReflection\\Reflection\\Adapter',
        'declaringClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'implementingClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'currentClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'aliasName' => NULL,
      ),
      'newInstance' => 
      array (
        'name' => 'newInstance',
        'parameters' => 
        array (
          'arg' => 
          array (
            'name' => 'arg',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 528,
                'endLine' => 528,
                'startTokenPos' => 2318,
                'startFilePos' => 14427,
                'endTokenPos' => 2318,
                'endFilePos' => 14430,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 528,
            'endLine' => 528,
            'startColumn' => 33,
            'endColumn' => 43,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
          'args' => 
          array (
            'name' => 'args',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => true,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 528,
            'endLine' => 528,
            'startColumn' => 46,
            'endColumn' => 53,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
          0 => 
          array (
            'name' => 'ReturnTypeWillChange',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => '/**
 * @return object
 * @param mixed $arg
 * @param mixed $args
 */',
        'startLine' => 527,
        'endLine' => 534,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => true,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\BetterReflection\\Reflection\\Adapter',
        'declaringClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'implementingClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'currentClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'aliasName' => NULL,
      ),
      'newInstanceWithoutConstructor' => 
      array (
        'name' => 'newInstanceWithoutConstructor',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'object',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 536,
        'endLine' => 542,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\BetterReflection\\Reflection\\Adapter',
        'declaringClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'implementingClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'currentClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'aliasName' => NULL,
      ),
      'newInstanceArgs' => 
      array (
        'name' => 'newInstanceArgs',
        'parameters' => 
        array (
          'args' => 
          array (
            'name' => 'args',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 544,
                'endLine' => 544,
                'startTokenPos' => 2441,
                'startFilePos' => 14970,
                'endTokenPos' => 2441,
                'endFilePos' => 14973,
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
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 544,
            'endLine' => 544,
            'startColumn' => 37,
            'endColumn' => 55,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'object',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 544,
        'endLine' => 550,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\BetterReflection\\Reflection\\Adapter',
        'declaringClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'implementingClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'currentClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'aliasName' => NULL,
      ),
      'newLazyGhost' => 
      array (
        'name' => 'newLazyGhost',
        'parameters' => 
        array (
          'initializer' => 
          array (
            'name' => 'initializer',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'callable',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 557,
            'endLine' => 557,
            'startColumn' => 34,
            'endColumn' => 54,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'options' => 
          array (
            'name' => 'options',
            'default' => 
            array (
              'code' => '0',
              'attributes' => 
              array (
                'startLine' => 557,
                'endLine' => 557,
                'startTokenPos' => 2511,
                'startFilePos' => 15347,
                'endTokenPos' => 2511,
                'endFilePos' => 15347,
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
            'startLine' => 557,
            'endLine' => 557,
            'startColumn' => 57,
            'endColumn' => 72,
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
            'name' => 'object',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @param int-mask-of<self::SKIP_*> $options
 *
 * @return never
 */',
        'startLine' => 557,
        'endLine' => 560,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\BetterReflection\\Reflection\\Adapter',
        'declaringClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'implementingClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'currentClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'aliasName' => NULL,
      ),
      'newLazyProxy' => 
      array (
        'name' => 'newLazyProxy',
        'parameters' => 
        array (
          'factory' => 
          array (
            'name' => 'factory',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'callable',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 567,
            'endLine' => 567,
            'startColumn' => 34,
            'endColumn' => 50,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'options' => 
          array (
            'name' => 'options',
            'default' => 
            array (
              'code' => '0',
              'attributes' => 
              array (
                'startLine' => 567,
                'endLine' => 567,
                'startTokenPos' => 2549,
                'startFilePos' => 15609,
                'endTokenPos' => 2549,
                'endFilePos' => 15609,
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
            'startLine' => 567,
            'endLine' => 567,
            'startColumn' => 53,
            'endColumn' => 68,
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
            'name' => 'object',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @param int-mask-of<self::SKIP_*> $options
 *
 * @return never
 */',
        'startLine' => 567,
        'endLine' => 570,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\BetterReflection\\Reflection\\Adapter',
        'declaringClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'implementingClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'currentClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'aliasName' => NULL,
      ),
      'markLazyObjectAsInitialized' => 
      array (
        'name' => 'markLazyObjectAsInitialized',
        'parameters' => 
        array (
          'object' => 
          array (
            'name' => 'object',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'object',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 573,
            'endLine' => 573,
            'startColumn' => 49,
            'endColumn' => 62,
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
            'name' => 'object',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/** @return never */',
        'startLine' => 573,
        'endLine' => 576,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\BetterReflection\\Reflection\\Adapter',
        'declaringClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'implementingClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'currentClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'aliasName' => NULL,
      ),
      'getLazyInitializer' => 
      array (
        'name' => 'getLazyInitializer',
        'parameters' => 
        array (
          'object' => 
          array (
            'name' => 'object',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'object',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 578,
            'endLine' => 578,
            'startColumn' => 40,
            'endColumn' => 53,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
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
                  'name' => 'callable',
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
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 578,
        'endLine' => 581,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\BetterReflection\\Reflection\\Adapter',
        'declaringClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'implementingClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'currentClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'aliasName' => NULL,
      ),
      'initializeLazyObject' => 
      array (
        'name' => 'initializeLazyObject',
        'parameters' => 
        array (
          'object' => 
          array (
            'name' => 'object',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'object',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 584,
            'endLine' => 584,
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
            'name' => 'object',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/** @return never */',
        'startLine' => 584,
        'endLine' => 587,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\BetterReflection\\Reflection\\Adapter',
        'declaringClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'implementingClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'currentClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'aliasName' => NULL,
      ),
      'isUninitializedLazyObject' => 
      array (
        'name' => 'isUninitializedLazyObject',
        'parameters' => 
        array (
          'object' => 
          array (
            'name' => 'object',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'object',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 590,
            'endLine' => 590,
            'startColumn' => 47,
            'endColumn' => 60,
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
        'docComment' => '/** @return never */',
        'startLine' => 590,
        'endLine' => 593,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\BetterReflection\\Reflection\\Adapter',
        'declaringClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'implementingClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'currentClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'aliasName' => NULL,
      ),
      'resetAsLazyGhost' => 
      array (
        'name' => 'resetAsLazyGhost',
        'parameters' => 
        array (
          'object' => 
          array (
            'name' => 'object',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'object',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 596,
            'endLine' => 596,
            'startColumn' => 38,
            'endColumn' => 51,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'initializer' => 
          array (
            'name' => 'initializer',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'callable',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 596,
            'endLine' => 596,
            'startColumn' => 54,
            'endColumn' => 74,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'options' => 
          array (
            'name' => 'options',
            'default' => 
            array (
              'code' => '0',
              'attributes' => 
              array (
                'startLine' => 596,
                'endLine' => 596,
                'startTokenPos' => 2707,
                'startFilePos' => 16565,
                'endTokenPos' => 2707,
                'endFilePos' => 16565,
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
            'startLine' => 596,
            'endLine' => 596,
            'startColumn' => 77,
            'endColumn' => 92,
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
        'docComment' => '/** @param int-mask-of<self::SKIP_*> $options */',
        'startLine' => 596,
        'endLine' => 599,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\BetterReflection\\Reflection\\Adapter',
        'declaringClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'implementingClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'currentClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'aliasName' => NULL,
      ),
      'resetAsLazyProxy' => 
      array (
        'name' => 'resetAsLazyProxy',
        'parameters' => 
        array (
          'object' => 
          array (
            'name' => 'object',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'object',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 602,
            'endLine' => 602,
            'startColumn' => 38,
            'endColumn' => 51,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'factory' => 
          array (
            'name' => 'factory',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'callable',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 602,
            'endLine' => 602,
            'startColumn' => 54,
            'endColumn' => 70,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'options' => 
          array (
            'name' => 'options',
            'default' => 
            array (
              'code' => '0',
              'attributes' => 
              array (
                'startLine' => 602,
                'endLine' => 602,
                'startTokenPos' => 2750,
                'startFilePos' => 16805,
                'endTokenPos' => 2750,
                'endFilePos' => 16805,
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
            'startLine' => 602,
            'endLine' => 602,
            'startColumn' => 73,
            'endColumn' => 88,
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
        'docComment' => '/** @param int-mask-of<self::SKIP_*> $options */',
        'startLine' => 602,
        'endLine' => 605,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\BetterReflection\\Reflection\\Adapter',
        'declaringClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'implementingClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'currentClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'aliasName' => NULL,
      ),
      'getParentClassName' => 
      array (
        'name' => 'getParentClassName',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
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
        'attributes' => 
        array (
        ),
        'docComment' => '/** @return class-string|null */',
        'startLine' => 608,
        'endLine' => 611,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\BetterReflection\\Reflection\\Adapter',
        'declaringClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'implementingClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'currentClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'aliasName' => NULL,
      ),
      'getParentClass' => 
      array (
        'name' => 'getParentClass',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
          0 => 
          array (
            'name' => 'ReturnTypeWillChange',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => '/**
 * {@inheritDoc}
 * @psalm-mutation-free
 * @return self|false
 */',
        'startLine' => 618,
        'endLine' => 628,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\BetterReflection\\Reflection\\Adapter',
        'declaringClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'implementingClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'currentClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'aliasName' => NULL,
      ),
      'isSubclassOf' => 
      array (
        'name' => 'isSubclassOf',
        'parameters' => 
        array (
          'class' => 
          array (
            'name' => 'class',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 633,
            'endLine' => 633,
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
 * {@inheritDoc}
 */',
        'startLine' => 633,
        'endLine' => 649,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\BetterReflection\\Reflection\\Adapter',
        'declaringClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'implementingClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'currentClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'aliasName' => NULL,
      ),
      'getStaticProperties' => 
      array (
        'name' => 'getStaticProperties',
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
 * @return array<string, mixed>
 *
 * @psalm-suppress LessSpecificImplementedReturnType
 */',
        'startLine' => 656,
        'endLine' => 659,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\BetterReflection\\Reflection\\Adapter',
        'declaringClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'implementingClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'currentClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'aliasName' => NULL,
      ),
      'getStaticPropertyValue' => 
      array (
        'name' => 'getStaticPropertyValue',
        'parameters' => 
        array (
          'name' => 
          array (
            'name' => 'name',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 665,
            'endLine' => 665,
            'startColumn' => 44,
            'endColumn' => 48,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'default' => 
          array (
            'name' => 'default',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 665,
                'endLine' => 665,
                'startTokenPos' => 3052,
                'startFilePos' => 18572,
                'endTokenPos' => 3052,
                'endFilePos' => 18575,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 665,
            'endLine' => 665,
            'startColumn' => 51,
            'endColumn' => 65,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
          0 => 
          array (
            'name' => 'ReturnTypeWillChange',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => '/**
 * {@inheritDoc}
 */',
        'startLine' => 664,
        'endLine' => 684,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => true,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\BetterReflection\\Reflection\\Adapter',
        'declaringClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'implementingClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'currentClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'aliasName' => NULL,
      ),
      'setStaticPropertyValue' => 
      array (
        'name' => 'setStaticPropertyValue',
        'parameters' => 
        array (
          'name' => 
          array (
            'name' => 'name',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 689,
            'endLine' => 689,
            'startColumn' => 44,
            'endColumn' => 48,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
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
            'startLine' => 689,
            'endLine' => 689,
            'startColumn' => 51,
            'endColumn' => 56,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * {@inheritDoc}
 */',
        'startLine' => 689,
        'endLine' => 704,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\BetterReflection\\Reflection\\Adapter',
        'declaringClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'implementingClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'currentClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'aliasName' => NULL,
      ),
      'getDefaultProperties' => 
      array (
        'name' => 'getDefaultProperties',
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
 * @return array<non-empty-string, mixed>
 *
 * @psalm-mutation-free
 */',
        'startLine' => 711,
        'endLine' => 714,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\BetterReflection\\Reflection\\Adapter',
        'declaringClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'implementingClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'currentClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'aliasName' => NULL,
      ),
      'isIterateable' => 
      array (
        'name' => 'isIterateable',
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
        'docComment' => '/** @psalm-mutation-free */',
        'startLine' => 717,
        'endLine' => 720,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\BetterReflection\\Reflection\\Adapter',
        'declaringClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'implementingClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'currentClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'aliasName' => NULL,
      ),
      'isIterable' => 
      array (
        'name' => 'isIterable',
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
        'docComment' => '/** @psalm-mutation-free */',
        'startLine' => 723,
        'endLine' => 726,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\BetterReflection\\Reflection\\Adapter',
        'declaringClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'implementingClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'currentClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'aliasName' => NULL,
      ),
      'implementsInterface' => 
      array (
        'name' => 'implementsInterface',
        'parameters' => 
        array (
          'interface' => 
          array (
            'name' => 'interface',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 731,
            'endLine' => 731,
            'startColumn' => 41,
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
 * @param \\ReflectionClass|string $interface
 */',
        'startLine' => 731,
        'endLine' => 743,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\BetterReflection\\Reflection\\Adapter',
        'declaringClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'implementingClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'currentClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'aliasName' => NULL,
      ),
      'getExtension' => 
      array (
        'name' => 'getExtension',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
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
                  'name' => 'ReflectionExtension',
                  'isIdentifier' => false,
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
        'attributes' => 
        array (
        ),
        'docComment' => '/** @psalm-mutation-free */',
        'startLine' => 746,
        'endLine' => 749,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\BetterReflection\\Reflection\\Adapter',
        'declaringClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'implementingClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'currentClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'aliasName' => NULL,
      ),
      'getExtensionName' => 
      array (
        'name' => 'getExtensionName',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
          0 => 
          array (
            'name' => 'ReturnTypeWillChange',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => '/**
 * {@inheritDoc}
 */',
        'startLine' => 754,
        'endLine' => 758,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\BetterReflection\\Reflection\\Adapter',
        'declaringClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'implementingClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'currentClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'aliasName' => NULL,
      ),
      'inNamespace' => 
      array (
        'name' => 'inNamespace',
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
        'docComment' => '/** @psalm-mutation-free */',
        'startLine' => 761,
        'endLine' => 764,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\BetterReflection\\Reflection\\Adapter',
        'declaringClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'implementingClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'currentClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'aliasName' => NULL,
      ),
      'getNamespaceName' => 
      array (
        'name' => 'getNamespaceName',
        'parameters' => 
        array (
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
        'docComment' => '/** @psalm-mutation-free */',
        'startLine' => 767,
        'endLine' => 770,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\BetterReflection\\Reflection\\Adapter',
        'declaringClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'implementingClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'currentClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'aliasName' => NULL,
      ),
      'getShortName' => 
      array (
        'name' => 'getShortName',
        'parameters' => 
        array (
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
        'docComment' => '/** @psalm-mutation-free */',
        'startLine' => 773,
        'endLine' => 776,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\BetterReflection\\Reflection\\Adapter',
        'declaringClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'implementingClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'currentClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'aliasName' => NULL,
      ),
      'getAttributes' => 
      array (
        'name' => 'getAttributes',
        'parameters' => 
        array (
          'name' => 
          array (
            'name' => 'name',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 783,
                'endLine' => 783,
                'startTokenPos' => 3730,
                'startFilePos' => 22273,
                'endTokenPos' => 3730,
                'endFilePos' => 22276,
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
            'startLine' => 783,
            'endLine' => 783,
            'startColumn' => 35,
            'endColumn' => 54,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
          'flags' => 
          array (
            'name' => 'flags',
            'default' => 
            array (
              'code' => '0',
              'attributes' => 
              array (
                'startLine' => 783,
                'endLine' => 783,
                'startTokenPos' => 3739,
                'startFilePos' => 22292,
                'endTokenPos' => 3739,
                'endFilePos' => 22292,
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
            'startLine' => 783,
            'endLine' => 783,
            'startColumn' => 57,
            'endColumn' => 70,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @param class-string|null $name
 *
 * @return list<ReflectionAttribute|FakeReflectionAttribute>
 */',
        'startLine' => 783,
        'endLine' => 799,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\BetterReflection\\Reflection\\Adapter',
        'declaringClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'implementingClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'currentClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'aliasName' => NULL,
      ),
      'isEnum' => 
      array (
        'name' => 'isEnum',
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
        'docComment' => '/** @psalm-mutation-free */',
        'startLine' => 802,
        'endLine' => 805,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\BetterReflection\\Reflection\\Adapter',
        'declaringClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'implementingClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
        'currentClassName' => 'PHPStan\\BetterReflection\\Reflection\\Adapter\\ReflectionClass',
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