<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/src/Reflection/Callables/FunctionCallableVariant.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\Reflection\Callables\FunctionCallableVariant
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-47916ea8882ad4184693c0851921fe8a01ddae6c5a3b21f2f658130301d0b837',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\Reflection\\Callables\\FunctionCallableVariant',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/src/Reflection/Callables/FunctionCallableVariant.php',
      ),
    ),
    'namespace' => 'PHPStan\\Reflection\\Callables',
    'name' => 'PHPStan\\Reflection\\Callables\\FunctionCallableVariant',
    'shortName' => 'FunctionCallableVariant',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => NULL,
    'attributes' => 
    array (
    ),
    'startLine' => 20,
    'endLine' => 182,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
      0 => 'PHPStan\\Reflection\\Callables\\CallableParametersAcceptor',
      1 => 'PHPStan\\Reflection\\ExtendedParametersAcceptor',
    ),
    'traitClassNames' => 
    array (
    ),
    'immediateConstants' => 
    array (
    ),
    'immediateProperties' => 
    array (
      'throwPoints' => 
      array (
        'declaringClassName' => 'PHPStan\\Reflection\\Callables\\FunctionCallableVariant',
        'implementingClassName' => 'PHPStan\\Reflection\\Callables\\FunctionCallableVariant',
        'name' => 'throwPoints',
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
            'startLine' => 24,
            'endLine' => 24,
            'startTokenPos' => 116,
            'startFilePos' => 737,
            'endTokenPos' => 116,
            'endFilePos' => 740,
          ),
        ),
        'docComment' => '/** @var SimpleThrowPoint[]|null  */',
        'attributes' => 
        array (
        ),
        'startLine' => 24,
        'endLine' => 24,
        'startColumn' => 2,
        'endColumn' => 36,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'impurePoints' => 
      array (
        'declaringClassName' => 'PHPStan\\Reflection\\Callables\\FunctionCallableVariant',
        'implementingClassName' => 'PHPStan\\Reflection\\Callables\\FunctionCallableVariant',
        'name' => 'impurePoints',
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
            'startLine' => 27,
            'endLine' => 27,
            'startTokenPos' => 130,
            'startFilePos' => 815,
            'endTokenPos' => 130,
            'endFilePos' => 818,
          ),
        ),
        'docComment' => '/** @var SimpleImpurePoint[]|null  */',
        'attributes' => 
        array (
        ),
        'startLine' => 27,
        'endLine' => 27,
        'startColumn' => 2,
        'endColumn' => 37,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'function' => 
      array (
        'declaringClassName' => 'PHPStan\\Reflection\\Callables\\FunctionCallableVariant',
        'implementingClassName' => 'PHPStan\\Reflection\\Callables\\FunctionCallableVariant',
        'name' => 'function',
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
                  'name' => 'PHPStan\\Reflection\\FunctionReflection',
                  'isIdentifier' => false,
                ),
              ),
              1 => 
              array (
                'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                'data' => 
                array (
                  'name' => 'PHPStan\\Reflection\\ExtendedMethodReflection',
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
        'startLine' => 30,
        'endLine' => 30,
        'startColumn' => 3,
        'endColumn' => 63,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'variant' => 
      array (
        'declaringClassName' => 'PHPStan\\Reflection\\Callables\\FunctionCallableVariant',
        'implementingClassName' => 'PHPStan\\Reflection\\Callables\\FunctionCallableVariant',
        'name' => 'variant',
        'modifiers' => 4,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPStan\\Reflection\\ExtendedParametersAcceptor',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 31,
        'endLine' => 31,
        'startColumn' => 3,
        'endColumn' => 45,
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
          'function' => 
          array (
            'name' => 'function',
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
                      'name' => 'PHPStan\\Reflection\\FunctionReflection',
                      'isIdentifier' => false,
                    ),
                  ),
                  1 => 
                  array (
                    'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                    'data' => 
                    array (
                      'name' => 'PHPStan\\Reflection\\ExtendedMethodReflection',
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
            'startLine' => 30,
            'endLine' => 30,
            'startColumn' => 3,
            'endColumn' => 63,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'variant' => 
          array (
            'name' => 'variant',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\Reflection\\ExtendedParametersAcceptor',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 31,
            'endLine' => 31,
            'startColumn' => 3,
            'endColumn' => 45,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 29,
        'endLine' => 34,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection\\Callables',
        'declaringClassName' => 'PHPStan\\Reflection\\Callables\\FunctionCallableVariant',
        'implementingClassName' => 'PHPStan\\Reflection\\Callables\\FunctionCallableVariant',
        'currentClassName' => 'PHPStan\\Reflection\\Callables\\FunctionCallableVariant',
        'aliasName' => NULL,
      ),
      'createFromVariants' => 
      array (
        'name' => 'createFromVariants',
        'parameters' => 
        array (
          'function' => 
          array (
            'name' => 'function',
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
                      'name' => 'PHPStan\\Reflection\\FunctionReflection',
                      'isIdentifier' => false,
                    ),
                  ),
                  1 => 
                  array (
                    'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                    'data' => 
                    array (
                      'name' => 'PHPStan\\Reflection\\ExtendedMethodReflection',
                      'isIdentifier' => false,
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
            'startLine' => 40,
            'endLine' => 40,
            'startColumn' => 44,
            'endColumn' => 96,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'variants' => 
          array (
            'name' => 'variants',
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
            'startLine' => 40,
            'endLine' => 40,
            'startColumn' => 99,
            'endColumn' => 113,
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
        'docComment' => '/**
 * @param list<ExtendedParametersAcceptor> $variants
 * @return list<self>
 */',
        'startLine' => 40,
        'endLine' => 43,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'PHPStan\\Reflection\\Callables',
        'declaringClassName' => 'PHPStan\\Reflection\\Callables\\FunctionCallableVariant',
        'implementingClassName' => 'PHPStan\\Reflection\\Callables\\FunctionCallableVariant',
        'currentClassName' => 'PHPStan\\Reflection\\Callables\\FunctionCallableVariant',
        'aliasName' => NULL,
      ),
      'getTemplateTypeMap' => 
      array (
        'name' => 'getTemplateTypeMap',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPStan\\Type\\Generic\\TemplateTypeMap',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 45,
        'endLine' => 48,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection\\Callables',
        'declaringClassName' => 'PHPStan\\Reflection\\Callables\\FunctionCallableVariant',
        'implementingClassName' => 'PHPStan\\Reflection\\Callables\\FunctionCallableVariant',
        'currentClassName' => 'PHPStan\\Reflection\\Callables\\FunctionCallableVariant',
        'aliasName' => NULL,
      ),
      'getResolvedTemplateTypeMap' => 
      array (
        'name' => 'getResolvedTemplateTypeMap',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPStan\\Type\\Generic\\TemplateTypeMap',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 50,
        'endLine' => 53,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection\\Callables',
        'declaringClassName' => 'PHPStan\\Reflection\\Callables\\FunctionCallableVariant',
        'implementingClassName' => 'PHPStan\\Reflection\\Callables\\FunctionCallableVariant',
        'currentClassName' => 'PHPStan\\Reflection\\Callables\\FunctionCallableVariant',
        'aliasName' => NULL,
      ),
      'getParameters' => 
      array (
        'name' => 'getParameters',
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
 * @return list<ExtendedParameterReflection>
 */',
        'startLine' => 58,
        'endLine' => 61,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection\\Callables',
        'declaringClassName' => 'PHPStan\\Reflection\\Callables\\FunctionCallableVariant',
        'implementingClassName' => 'PHPStan\\Reflection\\Callables\\FunctionCallableVariant',
        'currentClassName' => 'PHPStan\\Reflection\\Callables\\FunctionCallableVariant',
        'aliasName' => NULL,
      ),
      'isVariadic' => 
      array (
        'name' => 'isVariadic',
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
        'startLine' => 63,
        'endLine' => 66,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection\\Callables',
        'declaringClassName' => 'PHPStan\\Reflection\\Callables\\FunctionCallableVariant',
        'implementingClassName' => 'PHPStan\\Reflection\\Callables\\FunctionCallableVariant',
        'currentClassName' => 'PHPStan\\Reflection\\Callables\\FunctionCallableVariant',
        'aliasName' => NULL,
      ),
      'getReturnType' => 
      array (
        'name' => 'getReturnType',
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
        'startLine' => 68,
        'endLine' => 71,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection\\Callables',
        'declaringClassName' => 'PHPStan\\Reflection\\Callables\\FunctionCallableVariant',
        'implementingClassName' => 'PHPStan\\Reflection\\Callables\\FunctionCallableVariant',
        'currentClassName' => 'PHPStan\\Reflection\\Callables\\FunctionCallableVariant',
        'aliasName' => NULL,
      ),
      'getPhpDocReturnType' => 
      array (
        'name' => 'getPhpDocReturnType',
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
        'startLine' => 73,
        'endLine' => 76,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection\\Callables',
        'declaringClassName' => 'PHPStan\\Reflection\\Callables\\FunctionCallableVariant',
        'implementingClassName' => 'PHPStan\\Reflection\\Callables\\FunctionCallableVariant',
        'currentClassName' => 'PHPStan\\Reflection\\Callables\\FunctionCallableVariant',
        'aliasName' => NULL,
      ),
      'getNativeReturnType' => 
      array (
        'name' => 'getNativeReturnType',
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
        'startLine' => 78,
        'endLine' => 81,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection\\Callables',
        'declaringClassName' => 'PHPStan\\Reflection\\Callables\\FunctionCallableVariant',
        'implementingClassName' => 'PHPStan\\Reflection\\Callables\\FunctionCallableVariant',
        'currentClassName' => 'PHPStan\\Reflection\\Callables\\FunctionCallableVariant',
        'aliasName' => NULL,
      ),
      'getCallSiteVarianceMap' => 
      array (
        'name' => 'getCallSiteVarianceMap',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPStan\\Type\\Generic\\TemplateTypeVarianceMap',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 83,
        'endLine' => 86,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection\\Callables',
        'declaringClassName' => 'PHPStan\\Reflection\\Callables\\FunctionCallableVariant',
        'implementingClassName' => 'PHPStan\\Reflection\\Callables\\FunctionCallableVariant',
        'currentClassName' => 'PHPStan\\Reflection\\Callables\\FunctionCallableVariant',
        'aliasName' => NULL,
      ),
      'getThrowPoints' => 
      array (
        'name' => 'getThrowPoints',
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
        'startLine' => 88,
        'endLine' => 118,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection\\Callables',
        'declaringClassName' => 'PHPStan\\Reflection\\Callables\\FunctionCallableVariant',
        'implementingClassName' => 'PHPStan\\Reflection\\Callables\\FunctionCallableVariant',
        'currentClassName' => 'PHPStan\\Reflection\\Callables\\FunctionCallableVariant',
        'aliasName' => NULL,
      ),
      'isPure' => 
      array (
        'name' => 'isPure',
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
        'startLine' => 120,
        'endLine' => 137,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection\\Callables',
        'declaringClassName' => 'PHPStan\\Reflection\\Callables\\FunctionCallableVariant',
        'implementingClassName' => 'PHPStan\\Reflection\\Callables\\FunctionCallableVariant',
        'currentClassName' => 'PHPStan\\Reflection\\Callables\\FunctionCallableVariant',
        'aliasName' => NULL,
      ),
      'getImpurePoints' => 
      array (
        'name' => 'getImpurePoints',
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
        'startLine' => 139,
        'endLine' => 155,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection\\Callables',
        'declaringClassName' => 'PHPStan\\Reflection\\Callables\\FunctionCallableVariant',
        'implementingClassName' => 'PHPStan\\Reflection\\Callables\\FunctionCallableVariant',
        'currentClassName' => 'PHPStan\\Reflection\\Callables\\FunctionCallableVariant',
        'aliasName' => NULL,
      ),
      'getInvalidateExpressions' => 
      array (
        'name' => 'getInvalidateExpressions',
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
        'startLine' => 157,
        'endLine' => 160,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection\\Callables',
        'declaringClassName' => 'PHPStan\\Reflection\\Callables\\FunctionCallableVariant',
        'implementingClassName' => 'PHPStan\\Reflection\\Callables\\FunctionCallableVariant',
        'currentClassName' => 'PHPStan\\Reflection\\Callables\\FunctionCallableVariant',
        'aliasName' => NULL,
      ),
      'getUsedVariables' => 
      array (
        'name' => 'getUsedVariables',
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
        'startLine' => 162,
        'endLine' => 165,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection\\Callables',
        'declaringClassName' => 'PHPStan\\Reflection\\Callables\\FunctionCallableVariant',
        'implementingClassName' => 'PHPStan\\Reflection\\Callables\\FunctionCallableVariant',
        'currentClassName' => 'PHPStan\\Reflection\\Callables\\FunctionCallableVariant',
        'aliasName' => NULL,
      ),
      'acceptsNamedArguments' => 
      array (
        'name' => 'acceptsNamedArguments',
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
        'startLine' => 167,
        'endLine' => 170,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection\\Callables',
        'declaringClassName' => 'PHPStan\\Reflection\\Callables\\FunctionCallableVariant',
        'implementingClassName' => 'PHPStan\\Reflection\\Callables\\FunctionCallableVariant',
        'currentClassName' => 'PHPStan\\Reflection\\Callables\\FunctionCallableVariant',
        'aliasName' => NULL,
      ),
      'mustUseReturnValue' => 
      array (
        'name' => 'mustUseReturnValue',
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
        'startLine' => 172,
        'endLine' => 175,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection\\Callables',
        'declaringClassName' => 'PHPStan\\Reflection\\Callables\\FunctionCallableVariant',
        'implementingClassName' => 'PHPStan\\Reflection\\Callables\\FunctionCallableVariant',
        'currentClassName' => 'PHPStan\\Reflection\\Callables\\FunctionCallableVariant',
        'aliasName' => NULL,
      ),
      'getAsserts' => 
      array (
        'name' => 'getAsserts',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPStan\\Reflection\\Assertions',
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
        'namespace' => 'PHPStan\\Reflection\\Callables',
        'declaringClassName' => 'PHPStan\\Reflection\\Callables\\FunctionCallableVariant',
        'implementingClassName' => 'PHPStan\\Reflection\\Callables\\FunctionCallableVariant',
        'currentClassName' => 'PHPStan\\Reflection\\Callables\\FunctionCallableVariant',
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