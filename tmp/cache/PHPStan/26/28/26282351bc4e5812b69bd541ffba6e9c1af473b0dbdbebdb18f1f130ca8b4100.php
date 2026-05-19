<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/src/Type/Accessory/HasOffsetType.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\Type\Accessory\HasOffsetType
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-9db4b217bb7387e6a48eabc0f21b4788eabdc41bd0f3f966a2ebe1d59b141e72',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Accessory/HasOffsetType.php',
      ),
    ),
    'namespace' => 'PHPStan\\Type\\Accessory',
    'name' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
    'shortName' => 'HasOffsetType',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => NULL,
    'attributes' => 
    array (
    ),
    'startLine' => 44,
    'endLine' => 473,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
      0 => 'PHPStan\\Type\\CompoundType',
      1 => 'PHPStan\\Type\\Accessory\\AccessoryType',
    ),
    'traitClassNames' => 
    array (
      0 => 'PHPStan\\Type\\Traits\\MaybeArrayTypeTrait',
      1 => 'PHPStan\\Type\\Traits\\MaybeCallableTypeTrait',
      2 => 'PHPStan\\Type\\Traits\\MaybeIterableTypeTrait',
      3 => 'PHPStan\\Type\\Traits\\MaybeObjectTypeTrait',
      4 => 'PHPStan\\Type\\Traits\\MaybeStringTypeTrait',
      5 => 'PHPStan\\Type\\Traits\\TruthyBooleanTypeTrait',
      6 => 'PHPStan\\Type\\Traits\\NonGenericTypeTrait',
      7 => 'PHPStan\\Type\\Traits\\UndecidedComparisonCompoundTypeTrait',
      8 => 'PHPStan\\Type\\Traits\\NonRemoveableTypeTrait',
      9 => 'PHPStan\\Type\\Traits\\NonGeneralizableTypeTrait',
    ),
    'immediateConstants' => 
    array (
    ),
    'immediateProperties' => 
    array (
      'offsetType' => 
      array (
        'declaringClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'implementingClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'name' => 'offsetType',
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
                  'name' => 'PHPStan\\Type\\Constant\\ConstantStringType',
                  'isIdentifier' => false,
                ),
              ),
              1 => 
              array (
                'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                'data' => 
                array (
                  'name' => 'PHPStan\\Type\\Constant\\ConstantIntegerType',
                  'isIdentifier' => false,
                ),
              ),
            ),
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 61,
        'endLine' => 61,
        'startColumn' => 30,
        'endColumn' => 87,
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
                      'name' => 'PHPStan\\Type\\Constant\\ConstantStringType',
                      'isIdentifier' => false,
                    ),
                  ),
                  1 => 
                  array (
                    'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                    'data' => 
                    array (
                      'name' => 'PHPStan\\Type\\Constant\\ConstantIntegerType',
                      'isIdentifier' => false,
                    ),
                  ),
                ),
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 61,
            'endLine' => 61,
            'startColumn' => 30,
            'endColumn' => 87,
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
 * @api
 */',
        'startLine' => 61,
        'endLine' => 63,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type\\Accessory',
        'declaringClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'implementingClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'currentClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'aliasName' => NULL,
      ),
      'getOffsetType' => 
      array (
        'name' => 'getOffsetType',
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
                  'name' => 'PHPStan\\Type\\Constant\\ConstantStringType',
                  'isIdentifier' => false,
                ),
              ),
              1 => 
              array (
                'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                'data' => 
                array (
                  'name' => 'PHPStan\\Type\\Constant\\ConstantIntegerType',
                  'isIdentifier' => false,
                ),
              ),
            ),
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 65,
        'endLine' => 68,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type\\Accessory',
        'declaringClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'implementingClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'currentClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'aliasName' => NULL,
      ),
      'getReferencedClasses' => 
      array (
        'name' => 'getReferencedClasses',
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
        'docComment' => NULL,
        'startLine' => 70,
        'endLine' => 73,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type\\Accessory',
        'declaringClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'implementingClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'currentClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'aliasName' => NULL,
      ),
      'getObjectClassNames' => 
      array (
        'name' => 'getObjectClassNames',
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
        'docComment' => NULL,
        'startLine' => 75,
        'endLine' => 78,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type\\Accessory',
        'declaringClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'implementingClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'currentClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'aliasName' => NULL,
      ),
      'getObjectClassReflections' => 
      array (
        'name' => 'getObjectClassReflections',
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
        'docComment' => NULL,
        'startLine' => 80,
        'endLine' => 83,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type\\Accessory',
        'declaringClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'implementingClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'currentClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'aliasName' => NULL,
      ),
      'accepts' => 
      array (
        'name' => 'accepts',
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
            'startLine' => 85,
            'endLine' => 85,
            'startColumn' => 26,
            'endColumn' => 35,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'strictTypes' => 
          array (
            'name' => 'strictTypes',
            'default' => NULL,
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
            'startLine' => 85,
            'endLine' => 85,
            'startColumn' => 38,
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
            'name' => 'PHPStan\\Type\\AcceptsResult',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 85,
        'endLine' => 92,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type\\Accessory',
        'declaringClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'implementingClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'currentClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'aliasName' => NULL,
      ),
      'isSuperTypeOf' => 
      array (
        'name' => 'isSuperTypeOf',
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
            'startLine' => 94,
            'endLine' => 94,
            'startColumn' => 32,
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
            'name' => 'PHPStan\\Type\\IsSuperTypeOfResult',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 94,
        'endLine' => 100,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type\\Accessory',
        'declaringClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'implementingClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'currentClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'aliasName' => NULL,
      ),
      'isSubTypeOf' => 
      array (
        'name' => 'isSubTypeOf',
        'parameters' => 
        array (
          'otherType' => 
          array (
            'name' => 'otherType',
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
            'startLine' => 102,
            'endLine' => 102,
            'startColumn' => 30,
            'endColumn' => 44,
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
            'name' => 'PHPStan\\Type\\IsSuperTypeOfResult',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 102,
        'endLine' => 112,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type\\Accessory',
        'declaringClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'implementingClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'currentClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'aliasName' => NULL,
      ),
      'isAcceptedBy' => 
      array (
        'name' => 'isAcceptedBy',
        'parameters' => 
        array (
          'acceptingType' => 
          array (
            'name' => 'acceptingType',
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
            'startLine' => 114,
            'endLine' => 114,
            'startColumn' => 31,
            'endColumn' => 49,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'strictTypes' => 
          array (
            'name' => 'strictTypes',
            'default' => NULL,
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
            'startLine' => 114,
            'endLine' => 114,
            'startColumn' => 52,
            'endColumn' => 68,
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
            'name' => 'PHPStan\\Type\\AcceptsResult',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 114,
        'endLine' => 117,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type\\Accessory',
        'declaringClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'implementingClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'currentClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'aliasName' => NULL,
      ),
      'equals' => 
      array (
        'name' => 'equals',
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
            'startLine' => 119,
            'endLine' => 119,
            'startColumn' => 25,
            'endColumn' => 34,
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
        'startLine' => 119,
        'endLine' => 123,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type\\Accessory',
        'declaringClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'implementingClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'currentClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'aliasName' => NULL,
      ),
      'describe' => 
      array (
        'name' => 'describe',
        'parameters' => 
        array (
          'level' => 
          array (
            'name' => 'level',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\Type\\VerbosityLevel',
                'isIdentifier' => false,
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
            'startColumn' => 27,
            'endColumn' => 47,
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
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 125,
        'endLine' => 128,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type\\Accessory',
        'declaringClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'implementingClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'currentClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'aliasName' => NULL,
      ),
      'isOffsetAccessible' => 
      array (
        'name' => 'isOffsetAccessible',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPStan\\TrinaryLogic',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 130,
        'endLine' => 133,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type\\Accessory',
        'declaringClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'implementingClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'currentClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'aliasName' => NULL,
      ),
      'isOffsetAccessLegal' => 
      array (
        'name' => 'isOffsetAccessLegal',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPStan\\TrinaryLogic',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 135,
        'endLine' => 138,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type\\Accessory',
        'declaringClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'implementingClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'currentClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'aliasName' => NULL,
      ),
      'hasOffsetValueType' => 
      array (
        'name' => 'hasOffsetValueType',
        'parameters' => 
        array (
          'offsetType' => 
          array (
            'name' => 'offsetType',
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
            'startLine' => 140,
            'endLine' => 140,
            'startColumn' => 37,
            'endColumn' => 52,
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
            'name' => 'PHPStan\\TrinaryLogic',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 140,
        'endLine' => 147,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type\\Accessory',
        'declaringClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'implementingClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'currentClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'aliasName' => NULL,
      ),
      'getOffsetValueType' => 
      array (
        'name' => 'getOffsetValueType',
        'parameters' => 
        array (
          'offsetType' => 
          array (
            'name' => 'offsetType',
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
            'startLine' => 149,
            'endLine' => 149,
            'startColumn' => 37,
            'endColumn' => 52,
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
            'name' => 'PHPStan\\Type\\Type',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 149,
        'endLine' => 152,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type\\Accessory',
        'declaringClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'implementingClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'currentClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
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
            'startLine' => 154,
            'endLine' => 154,
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
            'startLine' => 154,
            'endLine' => 154,
            'startColumn' => 56,
            'endColumn' => 70,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'unionValues' => 
          array (
            'name' => 'unionValues',
            'default' => 
            array (
              'code' => 'true',
              'attributes' => 
              array (
                'startLine' => 154,
                'endLine' => 154,
                'startTokenPos' => 923,
                'startFilePos' => 4276,
                'endTokenPos' => 923,
                'endFilePos' => 4279,
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
            'startLine' => 154,
            'endLine' => 154,
            'startColumn' => 73,
            'endColumn' => 96,
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
            'name' => 'PHPStan\\Type\\Type',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 154,
        'endLine' => 157,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type\\Accessory',
        'declaringClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'implementingClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'currentClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'aliasName' => NULL,
      ),
      'setExistingOffsetValueType' => 
      array (
        'name' => 'setExistingOffsetValueType',
        'parameters' => 
        array (
          'offsetType' => 
          array (
            'name' => 'offsetType',
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
            'startLine' => 159,
            'endLine' => 159,
            'startColumn' => 45,
            'endColumn' => 60,
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
            'startLine' => 159,
            'endLine' => 159,
            'startColumn' => 63,
            'endColumn' => 77,
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
            'name' => 'PHPStan\\Type\\Type',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 159,
        'endLine' => 162,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type\\Accessory',
        'declaringClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'implementingClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'currentClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'aliasName' => NULL,
      ),
      'unsetOffset' => 
      array (
        'name' => 'unsetOffset',
        'parameters' => 
        array (
          'offsetType' => 
          array (
            'name' => 'offsetType',
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
            'startLine' => 164,
            'endLine' => 164,
            'startColumn' => 30,
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
            'name' => 'PHPStan\\Type\\Type',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 164,
        'endLine' => 170,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type\\Accessory',
        'declaringClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'implementingClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'currentClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'aliasName' => NULL,
      ),
      'chunkArray' => 
      array (
        'name' => 'chunkArray',
        'parameters' => 
        array (
          'lengthType' => 
          array (
            'name' => 'lengthType',
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
            'startLine' => 172,
            'endLine' => 172,
            'startColumn' => 29,
            'endColumn' => 44,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'preserveKeys' => 
          array (
            'name' => 'preserveKeys',
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
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 172,
            'endLine' => 172,
            'startColumn' => 47,
            'endColumn' => 72,
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
            'name' => 'PHPStan\\Type\\Type',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 172,
        'endLine' => 175,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type\\Accessory',
        'declaringClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'implementingClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'currentClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'aliasName' => NULL,
      ),
      'fillKeysArray' => 
      array (
        'name' => 'fillKeysArray',
        'parameters' => 
        array (
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
            'startLine' => 177,
            'endLine' => 177,
            'startColumn' => 32,
            'endColumn' => 46,
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
            'name' => 'PHPStan\\Type\\Type',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 177,
        'endLine' => 180,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type\\Accessory',
        'declaringClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'implementingClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'currentClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'aliasName' => NULL,
      ),
      'intersectKeyArray' => 
      array (
        'name' => 'intersectKeyArray',
        'parameters' => 
        array (
          'otherArraysType' => 
          array (
            'name' => 'otherArraysType',
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
            'startLine' => 182,
            'endLine' => 182,
            'startColumn' => 36,
            'endColumn' => 56,
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
            'name' => 'PHPStan\\Type\\Type',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 182,
        'endLine' => 189,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type\\Accessory',
        'declaringClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'implementingClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'currentClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'aliasName' => NULL,
      ),
      'reverseArray' => 
      array (
        'name' => 'reverseArray',
        'parameters' => 
        array (
          'preserveKeys' => 
          array (
            'name' => 'preserveKeys',
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
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 191,
            'endLine' => 191,
            'startColumn' => 31,
            'endColumn' => 56,
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
            'name' => 'PHPStan\\Type\\Type',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 191,
        'endLine' => 198,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type\\Accessory',
        'declaringClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'implementingClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'currentClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'aliasName' => NULL,
      ),
      'shuffleArray' => 
      array (
        'name' => 'shuffleArray',
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
        'startLine' => 200,
        'endLine' => 203,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type\\Accessory',
        'declaringClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'implementingClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'currentClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'aliasName' => NULL,
      ),
      'sliceArray' => 
      array (
        'name' => 'sliceArray',
        'parameters' => 
        array (
          'offsetType' => 
          array (
            'name' => 'offsetType',
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
            'startLine' => 205,
            'endLine' => 205,
            'startColumn' => 29,
            'endColumn' => 44,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'lengthType' => 
          array (
            'name' => 'lengthType',
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
            'startLine' => 205,
            'endLine' => 205,
            'startColumn' => 47,
            'endColumn' => 62,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'preserveKeys' => 
          array (
            'name' => 'preserveKeys',
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
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 205,
            'endLine' => 205,
            'startColumn' => 65,
            'endColumn' => 90,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
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
        'startLine' => 205,
        'endLine' => 217,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type\\Accessory',
        'declaringClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'implementingClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'currentClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'aliasName' => NULL,
      ),
      'spliceArray' => 
      array (
        'name' => 'spliceArray',
        'parameters' => 
        array (
          'offsetType' => 
          array (
            'name' => 'offsetType',
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
            'startLine' => 219,
            'endLine' => 219,
            'startColumn' => 30,
            'endColumn' => 45,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'lengthType' => 
          array (
            'name' => 'lengthType',
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
            'startLine' => 219,
            'endLine' => 219,
            'startColumn' => 48,
            'endColumn' => 63,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'replacementType' => 
          array (
            'name' => 'replacementType',
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
            'startLine' => 219,
            'endLine' => 219,
            'startColumn' => 66,
            'endColumn' => 86,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
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
        'startLine' => 219,
        'endLine' => 226,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type\\Accessory',
        'declaringClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'implementingClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'currentClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'aliasName' => NULL,
      ),
      'makeListMaybe' => 
      array (
        'name' => 'makeListMaybe',
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
        'startLine' => 228,
        'endLine' => 232,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type\\Accessory',
        'declaringClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'implementingClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'currentClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'aliasName' => NULL,
      ),
      'mapValueType' => 
      array (
        'name' => 'mapValueType',
        'parameters' => 
        array (
          'cb' => 
          array (
            'name' => 'cb',
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
            'startLine' => 234,
            'endLine' => 234,
            'startColumn' => 31,
            'endColumn' => 42,
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
            'name' => 'PHPStan\\Type\\Type',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 234,
        'endLine' => 239,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type\\Accessory',
        'declaringClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'implementingClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'currentClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'aliasName' => NULL,
      ),
      'mapKeyType' => 
      array (
        'name' => 'mapKeyType',
        'parameters' => 
        array (
          'cb' => 
          array (
            'name' => 'cb',
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
            'startLine' => 241,
            'endLine' => 241,
            'startColumn' => 29,
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
            'name' => 'PHPStan\\Type\\Type',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 241,
        'endLine' => 246,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type\\Accessory',
        'declaringClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'implementingClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'currentClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'aliasName' => NULL,
      ),
      'makeAllArrayKeysOptional' => 
      array (
        'name' => 'makeAllArrayKeysOptional',
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
        'startLine' => 248,
        'endLine' => 252,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type\\Accessory',
        'declaringClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'implementingClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'currentClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'aliasName' => NULL,
      ),
      'changeKeyCaseArray' => 
      array (
        'name' => 'changeKeyCaseArray',
        'parameters' => 
        array (
          'case' => 
          array (
            'name' => 'case',
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
            'startLine' => 254,
            'endLine' => 254,
            'startColumn' => 37,
            'endColumn' => 46,
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
            'name' => 'PHPStan\\Type\\Type',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 254,
        'endLine' => 272,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type\\Accessory',
        'declaringClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'implementingClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'currentClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'aliasName' => NULL,
      ),
      'filterArrayRemovingFalsey' => 
      array (
        'name' => 'filterArrayRemovingFalsey',
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
        'startLine' => 274,
        'endLine' => 279,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type\\Accessory',
        'declaringClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'implementingClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'currentClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'aliasName' => NULL,
      ),
      'isIterableAtLeastOnce' => 
      array (
        'name' => 'isIterableAtLeastOnce',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPStan\\TrinaryLogic',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 281,
        'endLine' => 284,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type\\Accessory',
        'declaringClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'implementingClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'currentClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
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
            'name' => 'PHPStan\\TrinaryLogic',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 286,
        'endLine' => 293,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type\\Accessory',
        'declaringClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'implementingClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'currentClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'aliasName' => NULL,
      ),
      'isNull' => 
      array (
        'name' => 'isNull',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPStan\\TrinaryLogic',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 295,
        'endLine' => 298,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type\\Accessory',
        'declaringClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'implementingClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'currentClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'aliasName' => NULL,
      ),
      'isConstantValue' => 
      array (
        'name' => 'isConstantValue',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPStan\\TrinaryLogic',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 300,
        'endLine' => 303,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type\\Accessory',
        'declaringClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'implementingClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'currentClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'aliasName' => NULL,
      ),
      'isConstantScalarValue' => 
      array (
        'name' => 'isConstantScalarValue',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPStan\\TrinaryLogic',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 305,
        'endLine' => 308,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type\\Accessory',
        'declaringClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'implementingClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'currentClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'aliasName' => NULL,
      ),
      'getConstantScalarTypes' => 
      array (
        'name' => 'getConstantScalarTypes',
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
        'docComment' => NULL,
        'startLine' => 310,
        'endLine' => 313,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type\\Accessory',
        'declaringClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'implementingClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'currentClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'aliasName' => NULL,
      ),
      'getConstantScalarValues' => 
      array (
        'name' => 'getConstantScalarValues',
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
        'docComment' => NULL,
        'startLine' => 315,
        'endLine' => 318,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type\\Accessory',
        'declaringClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'implementingClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'currentClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'aliasName' => NULL,
      ),
      'isTrue' => 
      array (
        'name' => 'isTrue',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPStan\\TrinaryLogic',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 320,
        'endLine' => 323,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type\\Accessory',
        'declaringClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'implementingClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'currentClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'aliasName' => NULL,
      ),
      'isFalse' => 
      array (
        'name' => 'isFalse',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPStan\\TrinaryLogic',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 325,
        'endLine' => 328,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type\\Accessory',
        'declaringClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'implementingClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'currentClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'aliasName' => NULL,
      ),
      'isBoolean' => 
      array (
        'name' => 'isBoolean',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPStan\\TrinaryLogic',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 330,
        'endLine' => 333,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type\\Accessory',
        'declaringClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'implementingClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'currentClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'aliasName' => NULL,
      ),
      'isFloat' => 
      array (
        'name' => 'isFloat',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPStan\\TrinaryLogic',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 335,
        'endLine' => 338,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type\\Accessory',
        'declaringClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'implementingClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'currentClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'aliasName' => NULL,
      ),
      'isInteger' => 
      array (
        'name' => 'isInteger',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPStan\\TrinaryLogic',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 340,
        'endLine' => 343,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type\\Accessory',
        'declaringClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'implementingClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'currentClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'aliasName' => NULL,
      ),
      'getClassStringObjectType' => 
      array (
        'name' => 'getClassStringObjectType',
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
        'startLine' => 345,
        'endLine' => 348,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type\\Accessory',
        'declaringClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'implementingClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'currentClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'aliasName' => NULL,
      ),
      'getObjectTypeOrClassStringObjectType' => 
      array (
        'name' => 'getObjectTypeOrClassStringObjectType',
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
        'startLine' => 350,
        'endLine' => 353,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type\\Accessory',
        'declaringClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'implementingClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'currentClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'aliasName' => NULL,
      ),
      'isVoid' => 
      array (
        'name' => 'isVoid',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPStan\\TrinaryLogic',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 355,
        'endLine' => 358,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type\\Accessory',
        'declaringClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'implementingClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'currentClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'aliasName' => NULL,
      ),
      'looseCompare' => 
      array (
        'name' => 'looseCompare',
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
            'startLine' => 360,
            'endLine' => 360,
            'startColumn' => 31,
            'endColumn' => 40,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'phpVersion' => 
          array (
            'name' => 'phpVersion',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\Php\\PhpVersion',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 360,
            'endLine' => 360,
            'startColumn' => 43,
            'endColumn' => 64,
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
            'name' => 'PHPStan\\Type\\BooleanType',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 360,
        'endLine' => 363,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type\\Accessory',
        'declaringClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'implementingClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'currentClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'aliasName' => NULL,
      ),
      'getKeysArrayFiltered' => 
      array (
        'name' => 'getKeysArrayFiltered',
        'parameters' => 
        array (
          'filterValueType' => 
          array (
            'name' => 'filterValueType',
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
            'startLine' => 365,
            'endLine' => 365,
            'startColumn' => 39,
            'endColumn' => 59,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'strict' => 
          array (
            'name' => 'strict',
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
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 365,
            'endLine' => 365,
            'startColumn' => 62,
            'endColumn' => 81,
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
            'name' => 'PHPStan\\Type\\Type',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 365,
        'endLine' => 368,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type\\Accessory',
        'declaringClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'implementingClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'currentClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'aliasName' => NULL,
      ),
      'getKeysArray' => 
      array (
        'name' => 'getKeysArray',
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
        'startLine' => 370,
        'endLine' => 373,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type\\Accessory',
        'declaringClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'implementingClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'currentClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'aliasName' => NULL,
      ),
      'getValuesArray' => 
      array (
        'name' => 'getValuesArray',
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
        'startLine' => 375,
        'endLine' => 378,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type\\Accessory',
        'declaringClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'implementingClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'currentClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'aliasName' => NULL,
      ),
      'toNumber' => 
      array (
        'name' => 'toNumber',
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
        'startLine' => 380,
        'endLine' => 383,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type\\Accessory',
        'declaringClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'implementingClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'currentClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'aliasName' => NULL,
      ),
      'toBitwiseNotType' => 
      array (
        'name' => 'toBitwiseNotType',
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
        'endLine' => 388,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type\\Accessory',
        'declaringClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'implementingClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'currentClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'aliasName' => NULL,
      ),
      'toAbsoluteNumber' => 
      array (
        'name' => 'toAbsoluteNumber',
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
        'startLine' => 390,
        'endLine' => 393,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type\\Accessory',
        'declaringClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'implementingClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'currentClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'aliasName' => NULL,
      ),
      'toInteger' => 
      array (
        'name' => 'toInteger',
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
        'startLine' => 395,
        'endLine' => 398,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type\\Accessory',
        'declaringClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'implementingClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'currentClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'aliasName' => NULL,
      ),
      'toFloat' => 
      array (
        'name' => 'toFloat',
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
        'startLine' => 400,
        'endLine' => 403,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type\\Accessory',
        'declaringClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'implementingClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'currentClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'aliasName' => NULL,
      ),
      'toString' => 
      array (
        'name' => 'toString',
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
        'startLine' => 405,
        'endLine' => 408,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type\\Accessory',
        'declaringClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'implementingClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'currentClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'aliasName' => NULL,
      ),
      'toArray' => 
      array (
        'name' => 'toArray',
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
        'startLine' => 410,
        'endLine' => 413,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type\\Accessory',
        'declaringClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'implementingClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'currentClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'aliasName' => NULL,
      ),
      'toArrayKey' => 
      array (
        'name' => 'toArrayKey',
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
        'startLine' => 415,
        'endLine' => 418,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type\\Accessory',
        'declaringClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'implementingClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'currentClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'aliasName' => NULL,
      ),
      'toCoercedArgumentType' => 
      array (
        'name' => 'toCoercedArgumentType',
        'parameters' => 
        array (
          'strictTypes' => 
          array (
            'name' => 'strictTypes',
            'default' => NULL,
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
            'startLine' => 420,
            'endLine' => 420,
            'startColumn' => 40,
            'endColumn' => 56,
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
            'name' => 'PHPStan\\Type\\Type',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 420,
        'endLine' => 423,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type\\Accessory',
        'declaringClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'implementingClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'currentClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'aliasName' => NULL,
      ),
      'getEnumCases' => 
      array (
        'name' => 'getEnumCases',
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
        'docComment' => NULL,
        'startLine' => 425,
        'endLine' => 428,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type\\Accessory',
        'declaringClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'implementingClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'currentClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'aliasName' => NULL,
      ),
      'getEnumCaseObject' => 
      array (
        'name' => 'getEnumCaseObject',
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
                  'name' => 'PHPStan\\Type\\Enum\\EnumCaseObjectType',
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
        'docComment' => NULL,
        'startLine' => 430,
        'endLine' => 433,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type\\Accessory',
        'declaringClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'implementingClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'currentClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'aliasName' => NULL,
      ),
      'traverse' => 
      array (
        'name' => 'traverse',
        'parameters' => 
        array (
          'cb' => 
          array (
            'name' => 'cb',
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
            'startLine' => 435,
            'endLine' => 435,
            'startColumn' => 27,
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
            'name' => 'PHPStan\\Type\\Type',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 435,
        'endLine' => 438,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type\\Accessory',
        'declaringClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'implementingClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'currentClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'aliasName' => NULL,
      ),
      'traverseSimultaneously' => 
      array (
        'name' => 'traverseSimultaneously',
        'parameters' => 
        array (
          'right' => 
          array (
            'name' => 'right',
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
            'startLine' => 440,
            'endLine' => 440,
            'startColumn' => 41,
            'endColumn' => 51,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'cb' => 
          array (
            'name' => 'cb',
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
            'startLine' => 440,
            'endLine' => 440,
            'startColumn' => 54,
            'endColumn' => 65,
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
            'name' => 'PHPStan\\Type\\Type',
            'isIdentifier' => false,
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
        'namespace' => 'PHPStan\\Type\\Accessory',
        'declaringClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'implementingClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'currentClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'aliasName' => NULL,
      ),
      'exponentiate' => 
      array (
        'name' => 'exponentiate',
        'parameters' => 
        array (
          'exponent' => 
          array (
            'name' => 'exponent',
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
            'startLine' => 445,
            'endLine' => 445,
            'startColumn' => 31,
            'endColumn' => 44,
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
            'name' => 'PHPStan\\Type\\Type',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 445,
        'endLine' => 448,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type\\Accessory',
        'declaringClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'implementingClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'currentClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'aliasName' => NULL,
      ),
      'getFiniteTypes' => 
      array (
        'name' => 'getFiniteTypes',
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
        'docComment' => NULL,
        'startLine' => 450,
        'endLine' => 453,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type\\Accessory',
        'declaringClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'implementingClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'currentClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'aliasName' => NULL,
      ),
      'getDefaultBaseType' => 
      array (
        'name' => 'getDefaultBaseType',
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
        'startLine' => 455,
        'endLine' => 461,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type\\Accessory',
        'declaringClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'implementingClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'currentClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'aliasName' => NULL,
      ),
      'toPhpDocNode' => 
      array (
        'name' => 'toPhpDocNode',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPStan\\PhpDocParser\\Ast\\Type\\TypeNode',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 463,
        'endLine' => 466,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type\\Accessory',
        'declaringClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'implementingClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'currentClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'aliasName' => NULL,
      ),
      'hasTemplateOrLateResolvableType' => 
      array (
        'name' => 'hasTemplateOrLateResolvableType',
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
        'startLine' => 468,
        'endLine' => 471,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type\\Accessory',
        'declaringClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'implementingClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
        'currentClassName' => 'PHPStan\\Type\\Accessory\\HasOffsetType',
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