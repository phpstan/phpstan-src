<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/src/Type/Constant/ConstantArrayTypeBuilder.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\Type\Constant\ConstantArrayTypeBuilder
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-2039da0d8c358fbe85277213f3a2669d5d90a75a6758120a9e46bd9507f44b4f',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\Type\\Constant\\ConstantArrayTypeBuilder',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Constant/ConstantArrayTypeBuilder.php',
      ),
    ),
    'namespace' => 'PHPStan\\Type\\Constant',
    'name' => 'PHPStan\\Type\\Constant\\ConstantArrayTypeBuilder',
    'shortName' => 'ConstantArrayTypeBuilder',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * @api
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 30,
    'endLine' => 445,
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
      'ARRAY_COUNT_LIMIT' => 
      array (
        'declaringClassName' => 'PHPStan\\Type\\Constant\\ConstantArrayTypeBuilder',
        'implementingClassName' => 'PHPStan\\Type\\Constant\\ConstantArrayTypeBuilder',
        'name' => 'ARRAY_COUNT_LIMIT',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '256',
          'attributes' => 
          array (
            'startLine' => 33,
            'endLine' => 33,
            'startTokenPos' => 157,
            'startFilePos' => 782,
            'endTokenPos' => 157,
            'endFilePos' => 784,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 33,
        'endLine' => 33,
        'startColumn' => 2,
        'endColumn' => 38,
      ),
      'CLOSURES_COUNT_LIMIT' => 
      array (
        'declaringClassName' => 'PHPStan\\Type\\Constant\\ConstantArrayTypeBuilder',
        'implementingClassName' => 'PHPStan\\Type\\Constant\\ConstantArrayTypeBuilder',
        'name' => 'CLOSURES_COUNT_LIMIT',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '32',
          'attributes' => 
          array (
            'startLine' => 34,
            'endLine' => 34,
            'startTokenPos' => 168,
            'startFilePos' => 825,
            'endTokenPos' => 168,
            'endFilePos' => 826,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 34,
        'endLine' => 34,
        'startColumn' => 2,
        'endColumn' => 41,
      ),
    ),
    'immediateProperties' => 
    array (
      'degradeToGeneralArray' => 
      array (
        'declaringClassName' => 'PHPStan\\Type\\Constant\\ConstantArrayTypeBuilder',
        'implementingClassName' => 'PHPStan\\Type\\Constant\\ConstantArrayTypeBuilder',
        'name' => 'degradeToGeneralArray',
        'modifiers' => 4,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'default' => 
        array (
          'code' => 'false',
          'attributes' => 
          array (
            'startLine' => 36,
            'endLine' => 36,
            'startTokenPos' => 179,
            'startFilePos' => 869,
            'endTokenPos' => 179,
            'endFilePos' => 873,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 36,
        'endLine' => 36,
        'startColumn' => 2,
        'endColumn' => 45,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'disableArrayDegradation' => 
      array (
        'declaringClassName' => 'PHPStan\\Type\\Constant\\ConstantArrayTypeBuilder',
        'implementingClassName' => 'PHPStan\\Type\\Constant\\ConstantArrayTypeBuilder',
        'name' => 'disableArrayDegradation',
        'modifiers' => 4,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'default' => 
        array (
          'code' => 'false',
          'attributes' => 
          array (
            'startLine' => 38,
            'endLine' => 38,
            'startTokenPos' => 190,
            'startFilePos' => 918,
            'endTokenPos' => 190,
            'endFilePos' => 922,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 38,
        'endLine' => 38,
        'startColumn' => 2,
        'endColumn' => 47,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'degradeClosures' => 
      array (
        'declaringClassName' => 'PHPStan\\Type\\Constant\\ConstantArrayTypeBuilder',
        'implementingClassName' => 'PHPStan\\Type\\Constant\\ConstantArrayTypeBuilder',
        'name' => 'degradeClosures',
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
                  'name' => 'bool',
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
            'startLine' => 40,
            'endLine' => 40,
            'startTokenPos' => 202,
            'startFilePos' => 960,
            'endTokenPos' => 202,
            'endFilePos' => 963,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 40,
        'endLine' => 40,
        'startColumn' => 2,
        'endColumn' => 39,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'oversized' => 
      array (
        'declaringClassName' => 'PHPStan\\Type\\Constant\\ConstantArrayTypeBuilder',
        'implementingClassName' => 'PHPStan\\Type\\Constant\\ConstantArrayTypeBuilder',
        'name' => 'oversized',
        'modifiers' => 4,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'default' => 
        array (
          'code' => 'false',
          'attributes' => 
          array (
            'startLine' => 42,
            'endLine' => 42,
            'startTokenPos' => 213,
            'startFilePos' => 994,
            'endTokenPos' => 213,
            'endFilePos' => 998,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 42,
        'endLine' => 42,
        'startColumn' => 2,
        'endColumn' => 33,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'isNonEmpty' => 
      array (
        'declaringClassName' => 'PHPStan\\Type\\Constant\\ConstantArrayTypeBuilder',
        'implementingClassName' => 'PHPStan\\Type\\Constant\\ConstantArrayTypeBuilder',
        'name' => 'isNonEmpty',
        'modifiers' => 4,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPStan\\TrinaryLogic',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 44,
        'endLine' => 44,
        'startColumn' => 2,
        'endColumn' => 34,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'keyTypes' => 
      array (
        'declaringClassName' => 'PHPStan\\Type\\Constant\\ConstantArrayTypeBuilder',
        'implementingClassName' => 'PHPStan\\Type\\Constant\\ConstantArrayTypeBuilder',
        'name' => 'keyTypes',
        'modifiers' => 4,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 53,
        'endLine' => 53,
        'startColumn' => 3,
        'endColumn' => 25,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'valueTypes' => 
      array (
        'declaringClassName' => 'PHPStan\\Type\\Constant\\ConstantArrayTypeBuilder',
        'implementingClassName' => 'PHPStan\\Type\\Constant\\ConstantArrayTypeBuilder',
        'name' => 'valueTypes',
        'modifiers' => 4,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 54,
        'endLine' => 54,
        'startColumn' => 3,
        'endColumn' => 27,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'nextAutoIndexes' => 
      array (
        'declaringClassName' => 'PHPStan\\Type\\Constant\\ConstantArrayTypeBuilder',
        'implementingClassName' => 'PHPStan\\Type\\Constant\\ConstantArrayTypeBuilder',
        'name' => 'nextAutoIndexes',
        'modifiers' => 4,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 55,
        'endLine' => 55,
        'startColumn' => 3,
        'endColumn' => 32,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'optionalKeys' => 
      array (
        'declaringClassName' => 'PHPStan\\Type\\Constant\\ConstantArrayTypeBuilder',
        'implementingClassName' => 'PHPStan\\Type\\Constant\\ConstantArrayTypeBuilder',
        'name' => 'optionalKeys',
        'modifiers' => 4,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 56,
        'endLine' => 56,
        'startColumn' => 3,
        'endColumn' => 29,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'isList' => 
      array (
        'declaringClassName' => 'PHPStan\\Type\\Constant\\ConstantArrayTypeBuilder',
        'implementingClassName' => 'PHPStan\\Type\\Constant\\ConstantArrayTypeBuilder',
        'name' => 'isList',
        'modifiers' => 4,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPStan\\TrinaryLogic',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 57,
        'endLine' => 57,
        'startColumn' => 3,
        'endColumn' => 30,
        'isPromoted' => true,
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
          'keyTypes' => 
          array (
            'name' => 'keyTypes',
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
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 53,
            'endLine' => 53,
            'startColumn' => 3,
            'endColumn' => 25,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'valueTypes' => 
          array (
            'name' => 'valueTypes',
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
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 54,
            'endLine' => 54,
            'startColumn' => 3,
            'endColumn' => 27,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'nextAutoIndexes' => 
          array (
            'name' => 'nextAutoIndexes',
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
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 55,
            'endLine' => 55,
            'startColumn' => 3,
            'endColumn' => 32,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'optionalKeys' => 
          array (
            'name' => 'optionalKeys',
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
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 56,
            'endLine' => 56,
            'startColumn' => 3,
            'endColumn' => 29,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
          'isList' => 
          array (
            'name' => 'isList',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\TrinaryLogic',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 57,
            'endLine' => 57,
            'startColumn' => 3,
            'endColumn' => 30,
            'parameterIndex' => 4,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @param list<Type> $keyTypes
 * @param array<int, Type> $valueTypes
 * @param list<int> $nextAutoIndexes
 * @param array<int> $optionalKeys
 */',
        'startLine' => 52,
        'endLine' => 61,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'PHPStan\\Type\\Constant',
        'declaringClassName' => 'PHPStan\\Type\\Constant\\ConstantArrayTypeBuilder',
        'implementingClassName' => 'PHPStan\\Type\\Constant\\ConstantArrayTypeBuilder',
        'currentClassName' => 'PHPStan\\Type\\Constant\\ConstantArrayTypeBuilder',
        'aliasName' => NULL,
      ),
      'createEmpty' => 
      array (
        'name' => 'createEmpty',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'self',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 63,
        'endLine' => 66,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'PHPStan\\Type\\Constant',
        'declaringClassName' => 'PHPStan\\Type\\Constant\\ConstantArrayTypeBuilder',
        'implementingClassName' => 'PHPStan\\Type\\Constant\\ConstantArrayTypeBuilder',
        'currentClassName' => 'PHPStan\\Type\\Constant\\ConstantArrayTypeBuilder',
        'aliasName' => NULL,
      ),
      'createFromConstantArray' => 
      array (
        'name' => 'createFromConstantArray',
        'parameters' => 
        array (
          'startArrayType' => 
          array (
            'name' => 'startArrayType',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\Type\\Constant\\ConstantArrayType',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 68,
            'endLine' => 68,
            'startColumn' => 49,
            'endColumn' => 81,
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
            'name' => 'self',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 68,
        'endLine' => 84,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'PHPStan\\Type\\Constant',
        'declaringClassName' => 'PHPStan\\Type\\Constant\\ConstantArrayTypeBuilder',
        'implementingClassName' => 'PHPStan\\Type\\Constant\\ConstantArrayTypeBuilder',
        'currentClassName' => 'PHPStan\\Type\\Constant\\ConstantArrayTypeBuilder',
        'aliasName' => NULL,
      ),
      'setOffsetValueType' => 
      array (
        'name' => 'setOffsetValueType',
        'parameters' => 
        array (
          'offsetType' => 
          array (
            'name' => 'offsetType',
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
                      'name' => 'PHPStan\\Type\\Type',
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
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 86,
            'endLine' => 86,
            'startColumn' => 37,
            'endColumn' => 53,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'valueType' => 
          array (
            'name' => 'valueType',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\Type\\Type',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 86,
            'endLine' => 86,
            'startColumn' => 56,
            'endColumn' => 70,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'optional' => 
          array (
            'name' => 'optional',
            'default' => 
            array (
              'code' => 'false',
              'attributes' => 
              array (
                'startLine' => 86,
                'endLine' => 86,
                'startTokenPos' => 473,
                'startFilePos' => 2153,
                'endTokenPos' => 473,
                'endFilePos' => 2157,
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
            'startLine' => 86,
            'endLine' => 86,
            'startColumn' => 73,
            'endColumn' => 94,
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
        'docComment' => NULL,
        'startLine' => 86,
        'endLine' => 361,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type\\Constant',
        'declaringClassName' => 'PHPStan\\Type\\Constant\\ConstantArrayTypeBuilder',
        'implementingClassName' => 'PHPStan\\Type\\Constant\\ConstantArrayTypeBuilder',
        'currentClassName' => 'PHPStan\\Type\\Constant\\ConstantArrayTypeBuilder',
        'aliasName' => NULL,
      ),
      'degradeToGeneralArray' => 
      array (
        'name' => 'degradeToGeneralArray',
        'parameters' => 
        array (
          'oversized' => 
          array (
            'name' => 'oversized',
            'default' => 
            array (
              'code' => 'false',
              'attributes' => 
              array (
                'startLine' => 363,
                'endLine' => 363,
                'startTokenPos' => 2512,
                'startFilePos' => 9538,
                'endTokenPos' => 2512,
                'endFilePos' => 9542,
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
            'startLine' => 363,
            'endLine' => 363,
            'startColumn' => 40,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 363,
        'endLine' => 371,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type\\Constant',
        'declaringClassName' => 'PHPStan\\Type\\Constant\\ConstantArrayTypeBuilder',
        'implementingClassName' => 'PHPStan\\Type\\Constant\\ConstantArrayTypeBuilder',
        'currentClassName' => 'PHPStan\\Type\\Constant\\ConstantArrayTypeBuilder',
        'aliasName' => NULL,
      ),
      'disableClosureDegradation' => 
      array (
        'name' => 'disableClosureDegradation',
        'parameters' => 
        array (
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
        'docComment' => NULL,
        'startLine' => 373,
        'endLine' => 376,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type\\Constant',
        'declaringClassName' => 'PHPStan\\Type\\Constant\\ConstantArrayTypeBuilder',
        'implementingClassName' => 'PHPStan\\Type\\Constant\\ConstantArrayTypeBuilder',
        'currentClassName' => 'PHPStan\\Type\\Constant\\ConstantArrayTypeBuilder',
        'aliasName' => NULL,
      ),
      'disableArrayDegradation' => 
      array (
        'name' => 'disableArrayDegradation',
        'parameters' => 
        array (
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
        'docComment' => NULL,
        'startLine' => 378,
        'endLine' => 383,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type\\Constant',
        'declaringClassName' => 'PHPStan\\Type\\Constant\\ConstantArrayTypeBuilder',
        'implementingClassName' => 'PHPStan\\Type\\Constant\\ConstantArrayTypeBuilder',
        'currentClassName' => 'PHPStan\\Type\\Constant\\ConstantArrayTypeBuilder',
        'aliasName' => NULL,
      ),
      'getArray' => 
      array (
        'name' => 'getArray',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPStan\\Type\\Type',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 385,
        'endLine' => 438,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type\\Constant',
        'declaringClassName' => 'PHPStan\\Type\\Constant\\ConstantArrayTypeBuilder',
        'implementingClassName' => 'PHPStan\\Type\\Constant\\ConstantArrayTypeBuilder',
        'currentClassName' => 'PHPStan\\Type\\Constant\\ConstantArrayTypeBuilder',
        'aliasName' => NULL,
      ),
      'isList' => 
      array (
        'name' => 'isList',
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
        'startLine' => 440,
        'endLine' => 443,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type\\Constant',
        'declaringClassName' => 'PHPStan\\Type\\Constant\\ConstantArrayTypeBuilder',
        'implementingClassName' => 'PHPStan\\Type\\Constant\\ConstantArrayTypeBuilder',
        'currentClassName' => 'PHPStan\\Type\\Constant\\ConstantArrayTypeBuilder',
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