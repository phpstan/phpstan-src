<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/src/Rules/Api/ApiInstanceofTypeRule.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\Rules\Api\ApiInstanceofTypeRule
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-f83ca6cf5bbed6a9850b834c50b9c51b3be6f6bf1516540c86f28db8d7a3d1c9',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\Rules\\Api\\ApiInstanceofTypeRule',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/src/Rules/Api/ApiInstanceofTypeRule.php',
      ),
    ),
    'namespace' => 'PHPStan\\Rules\\Api',
    'name' => 'PHPStan\\Rules\\Api\\ApiInstanceofTypeRule',
    'shortName' => 'ApiInstanceofTypeRule',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * @implements Rule<Instanceof_>
 */',
    'attributes' => 
    array (
      0 => 
      array (
        'name' => 'PHPStan\\DependencyInjection\\RegisteredRule',
        'isRepeated' => false,
        'arguments' => 
        array (
          'level' => 
          array (
            'code' => '0',
            'attributes' => 
            array (
              'startLine' => 56,
              'endLine' => 56,
              'startTokenPos' => 265,
              'startFilePos' => 1965,
              'endTokenPos' => 265,
              'endFilePos' => 1965,
            ),
          ),
        ),
      ),
    ),
    'startLine' => 56,
    'endLine' => 171,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
      0 => 'PHPStan\\Rules\\Rule',
    ),
    'traitClassNames' => 
    array (
    ),
    'immediateConstants' => 
    array (
      'MAP' => 
      array (
        'declaringClassName' => 'PHPStan\\Rules\\Api\\ApiInstanceofTypeRule',
        'implementingClassName' => 'PHPStan\\Rules\\Api\\ApiInstanceofTypeRule',
        'name' => 'MAP',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[
    \\PHPStan\\Type\\TypeWithClassName::class => \'Type::getObjectClassNames() or Type::getObjectClassReflections()\',
    \\PHPStan\\Type\\Enum\\EnumCaseObjectType::class => \'Type::getEnumCases()\',
    \\PHPStan\\Type\\Constant\\ConstantArrayType::class => \'Type::getConstantArrays()\',
    \\PHPStan\\Type\\ArrayType::class => \'Type::isArray() or Type::getArrays()\',
    \\PHPStan\\Type\\Constant\\ConstantStringType::class => \'Type::getConstantStrings()\',
    \\PHPStan\\Type\\StringType::class => \'Type::isString()\',
    \\PHPStan\\Type\\ClassStringType::class => \'Type::isClassStringType()\',
    \\PHPStan\\Type\\IntegerType::class => \'Type::isInteger()\',
    \\PHPStan\\Type\\FloatType::class => \'Type::isFloat()\',
    \\PHPStan\\Type\\NullType::class => \'Type::isNull()\',
    \\PHPStan\\Type\\VoidType::class => \'Type::isVoid()\',
    \\PHPStan\\Type\\BooleanType::class => \'Type::isBoolean()\',
    \\PHPStan\\Type\\Constant\\ConstantBooleanType::class => \'Type::isTrue() or Type::isFalse()\',
    \\PHPStan\\Type\\CallableType::class => \'Type::isCallable() and Type::getCallableParametersAcceptors()\',
    \\PHPStan\\Type\\IterableType::class => \'Type::isIterable()\',
    \\PHPStan\\Type\\ObjectWithoutClassType::class => \'Type::isObject()\',
    \\PHPStan\\Type\\ObjectType::class => \'Type::isObject() or Type::getObjectClassNames()\',
    \\PHPStan\\Type\\Generic\\GenericClassStringType::class => \'Type::isClassStringType() and Type::getClassStringObjectType()\',
    \\PHPStan\\Type\\Generic\\GenericObjectType::class => null,
    \\PHPStan\\Type\\IntersectionType::class => null,
    \\PHPStan\\Type\\ConstantScalarType::class => \'Type::isConstantScalarValue() or Type::getConstantScalarTypes() or Type::getConstantScalarValues()\',
    \\PHPStan\\Type\\ObjectShapeType::class => \'Type::isObject() and Type::hasProperty()\',
    // accessory types
    \\PHPStan\\Type\\Accessory\\NonEmptyArrayType::class => \'Type::isIterableAtLeastOnce()\',
    \\PHPStan\\Type\\Accessory\\OversizedArrayType::class => \'Type::isOversizedArray()\',
    \\PHPStan\\Type\\Accessory\\AccessoryArrayListType::class => \'Type::isList()\',
    \\PHPStan\\Type\\Accessory\\AccessoryNumericStringType::class => \'Type::isNumericString()\',
    \\PHPStan\\Type\\Accessory\\AccessoryLiteralStringType::class => \'Type::isLiteralString()\',
    \\PHPStan\\Type\\Accessory\\AccessoryLowercaseStringType::class => \'Type::isLowercaseString()\',
    \\PHPStan\\Type\\Accessory\\AccessoryUppercaseStringType::class => \'Type::isUppercaseString()\',
    \\PHPStan\\Type\\Accessory\\AccessoryNonEmptyStringType::class => \'Type::isNonEmptyString()\',
    \\PHPStan\\Type\\Accessory\\AccessoryNonFalsyStringType::class => \'Type::isNonFalsyString()\',
    \\PHPStan\\Type\\Accessory\\HasMethodType::class => \'Type::hasMethod()\',
    \\PHPStan\\Type\\Accessory\\HasPropertyType::class => \'Type::hasProperty()\',
    \\PHPStan\\Type\\Accessory\\HasOffsetType::class => \'Type::hasOffsetValueType()\',
    \\PHPStan\\Type\\Accessory\\AccessoryType::class => \'methods on PHPStan\\Type\\Type\',
]',
          'attributes' => 
          array (
            'startLine' => 60,
            'endLine' => 98,
            'startTokenPos' => 289,
            'startFilePos' => 2043,
            'endTokenPos' => 608,
            'endFilePos' => 4221,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 60,
        'endLine' => 98,
        'startColumn' => 2,
        'endColumn' => 3,
      ),
    ),
    'immediateProperties' => 
    array (
      'lowerMap' => 
      array (
        'declaringClassName' => 'PHPStan\\Rules\\Api\\ApiInstanceofTypeRule',
        'implementingClassName' => 'PHPStan\\Rules\\Api\\ApiInstanceofTypeRule',
        'name' => 'lowerMap',
        'modifiers' => 132,
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
        'docComment' => '/** @var array<lowercase-string, string|null> */',
        'attributes' => 
        array (
        ),
        'startLine' => 101,
        'endLine' => 101,
        'startColumn' => 2,
        'endColumn' => 34,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'reflectionProvider' => 
      array (
        'declaringClassName' => 'PHPStan\\Rules\\Api\\ApiInstanceofTypeRule',
        'implementingClassName' => 'PHPStan\\Rules\\Api\\ApiInstanceofTypeRule',
        'name' => 'reflectionProvider',
        'modifiers' => 4,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPStan\\Reflection\\ReflectionProvider',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 104,
        'endLine' => 104,
        'startColumn' => 3,
        'endColumn' => 48,
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
          'reflectionProvider' => 
          array (
            'name' => 'reflectionProvider',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\Reflection\\ReflectionProvider',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 104,
            'endLine' => 104,
            'startColumn' => 3,
            'endColumn' => 48,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 103,
        'endLine' => 112,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Rules\\Api',
        'declaringClassName' => 'PHPStan\\Rules\\Api\\ApiInstanceofTypeRule',
        'implementingClassName' => 'PHPStan\\Rules\\Api\\ApiInstanceofTypeRule',
        'currentClassName' => 'PHPStan\\Rules\\Api\\ApiInstanceofTypeRule',
        'aliasName' => NULL,
      ),
      'getNodeType' => 
      array (
        'name' => 'getNodeType',
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
        'namespace' => 'PHPStan\\Rules\\Api',
        'declaringClassName' => 'PHPStan\\Rules\\Api\\ApiInstanceofTypeRule',
        'implementingClassName' => 'PHPStan\\Rules\\Api\\ApiInstanceofTypeRule',
        'currentClassName' => 'PHPStan\\Rules\\Api\\ApiInstanceofTypeRule',
        'aliasName' => NULL,
      ),
      'processNode' => 
      array (
        'name' => 'processNode',
        'parameters' => 
        array (
          'node' => 
          array (
            'name' => 'node',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PhpParser\\Node',
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
            'startColumn' => 30,
            'endColumn' => 39,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'scope' => 
          array (
            'name' => 'scope',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\Analyser\\Scope',
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
            'startColumn' => 42,
            'endColumn' => 53,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 119,
        'endLine' => 169,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Rules\\Api',
        'declaringClassName' => 'PHPStan\\Rules\\Api\\ApiInstanceofTypeRule',
        'implementingClassName' => 'PHPStan\\Rules\\Api\\ApiInstanceofTypeRule',
        'currentClassName' => 'PHPStan\\Rules\\Api\\ApiInstanceofTypeRule',
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