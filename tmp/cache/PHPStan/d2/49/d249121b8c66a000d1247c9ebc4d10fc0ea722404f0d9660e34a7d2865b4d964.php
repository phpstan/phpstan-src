<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/src/PhpDoc/PhpDocNodeResolver.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\PhpDoc\PhpDocNodeResolver
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-a21a59ff78ce327b909f3d4bb83e4973dddeea7f396b9d9c24a5f68aef8ecd2b',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/src/PhpDoc/PhpDocNodeResolver.php',
      ),
    ),
    'namespace' => 'PHPStan\\PhpDoc',
    'name' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
    'shortName' => 'PhpDocNodeResolver',
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
    'startLine' => 54,
    'endLine' => 752,
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
      'typeNodeResolver' => 
      array (
        'declaringClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'implementingClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'name' => 'typeNodeResolver',
        'modifiers' => 4,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPStan\\PhpDoc\\TypeNodeResolver',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 59,
        'endLine' => 59,
        'startColumn' => 3,
        'endColumn' => 44,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'constExprNodeResolver' => 
      array (
        'declaringClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'implementingClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'name' => 'constExprNodeResolver',
        'modifiers' => 4,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPStan\\PhpDoc\\ConstExprNodeResolver',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 60,
        'endLine' => 60,
        'startColumn' => 3,
        'endColumn' => 54,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'unresolvableTypeHelper' => 
      array (
        'declaringClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'implementingClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'name' => 'unresolvableTypeHelper',
        'modifiers' => 4,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPStan\\Rules\\PhpDoc\\UnresolvableTypeHelper',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 61,
        'endLine' => 61,
        'startColumn' => 3,
        'endColumn' => 56,
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
          'typeNodeResolver' => 
          array (
            'name' => 'typeNodeResolver',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\PhpDoc\\TypeNodeResolver',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 59,
            'endLine' => 59,
            'startColumn' => 3,
            'endColumn' => 44,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'constExprNodeResolver' => 
          array (
            'name' => 'constExprNodeResolver',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\PhpDoc\\ConstExprNodeResolver',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 60,
            'endLine' => 60,
            'startColumn' => 3,
            'endColumn' => 54,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'unresolvableTypeHelper' => 
          array (
            'name' => 'unresolvableTypeHelper',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\Rules\\PhpDoc\\UnresolvableTypeHelper',
                'isIdentifier' => false,
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
            'startColumn' => 3,
            'endColumn' => 56,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 58,
        'endLine' => 64,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\PhpDoc',
        'declaringClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'implementingClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'currentClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'aliasName' => NULL,
      ),
      'resolveVarTags' => 
      array (
        'name' => 'resolveVarTags',
        'parameters' => 
        array (
          'phpDocNode' => 
          array (
            'name' => 'phpDocNode',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 69,
            'endLine' => 69,
            'startColumn' => 33,
            'endColumn' => 54,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'nameScope' => 
          array (
            'name' => 'nameScope',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\Analyser\\NameScope',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 69,
            'endLine' => 69,
            'startColumn' => 57,
            'endColumn' => 76,
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
 * @return array<(string|int), VarTag>
 */',
        'startLine' => 69,
        'endLine' => 101,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\PhpDoc',
        'declaringClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'implementingClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'currentClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'aliasName' => NULL,
      ),
      'resolvePropertyTags' => 
      array (
        'name' => 'resolvePropertyTags',
        'parameters' => 
        array (
          'phpDocNode' => 
          array (
            'name' => 'phpDocNode',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 106,
            'endLine' => 106,
            'startColumn' => 38,
            'endColumn' => 59,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'nameScope' => 
          array (
            'name' => 'nameScope',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\Analyser\\NameScope',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 106,
            'endLine' => 106,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @return array<string, PropertyTag>
 */',
        'startLine' => 106,
        'endLine' => 157,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\PhpDoc',
        'declaringClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'implementingClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'currentClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'aliasName' => NULL,
      ),
      'resolveMethodTags' => 
      array (
        'name' => 'resolveMethodTags',
        'parameters' => 
        array (
          'phpDocNode' => 
          array (
            'name' => 'phpDocNode',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 162,
            'endLine' => 162,
            'startColumn' => 36,
            'endColumn' => 57,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'nameScope' => 
          array (
            'name' => 'nameScope',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\Analyser\\NameScope',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 162,
            'endLine' => 162,
            'startColumn' => 60,
            'endColumn' => 79,
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
 * @return array<string, MethodTag>
 */',
        'startLine' => 162,
        'endLine' => 228,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\PhpDoc',
        'declaringClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'implementingClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'currentClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'aliasName' => NULL,
      ),
      'resolveExtendsTags' => 
      array (
        'name' => 'resolveExtendsTags',
        'parameters' => 
        array (
          'phpDocNode' => 
          array (
            'name' => 'phpDocNode',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 233,
            'endLine' => 233,
            'startColumn' => 37,
            'endColumn' => 58,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'nameScope' => 
          array (
            'name' => 'nameScope',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\Analyser\\NameScope',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 233,
            'endLine' => 233,
            'startColumn' => 61,
            'endColumn' => 80,
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
 * @return array<string, ExtendsTag>
 */',
        'startLine' => 233,
        'endLine' => 246,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\PhpDoc',
        'declaringClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'implementingClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'currentClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'aliasName' => NULL,
      ),
      'resolveImplementsTags' => 
      array (
        'name' => 'resolveImplementsTags',
        'parameters' => 
        array (
          'phpDocNode' => 
          array (
            'name' => 'phpDocNode',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 251,
            'endLine' => 251,
            'startColumn' => 40,
            'endColumn' => 61,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'nameScope' => 
          array (
            'name' => 'nameScope',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\Analyser\\NameScope',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 251,
            'endLine' => 251,
            'startColumn' => 64,
            'endColumn' => 83,
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
 * @return array<string, ImplementsTag>
 */',
        'startLine' => 251,
        'endLine' => 264,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\PhpDoc',
        'declaringClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'implementingClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'currentClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'aliasName' => NULL,
      ),
      'resolveUsesTags' => 
      array (
        'name' => 'resolveUsesTags',
        'parameters' => 
        array (
          'phpDocNode' => 
          array (
            'name' => 'phpDocNode',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 269,
            'endLine' => 269,
            'startColumn' => 34,
            'endColumn' => 55,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'nameScope' => 
          array (
            'name' => 'nameScope',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\Analyser\\NameScope',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 269,
            'endLine' => 269,
            'startColumn' => 58,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @return array<string, UsesTag>
 */',
        'startLine' => 269,
        'endLine' => 282,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\PhpDoc',
        'declaringClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'implementingClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'currentClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'aliasName' => NULL,
      ),
      'resolveTemplateTags' => 
      array (
        'name' => 'resolveTemplateTags',
        'parameters' => 
        array (
          'templatePhpDocNodes' => 
          array (
            'name' => 'templatePhpDocNodes',
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
            'startLine' => 288,
            'endLine' => 288,
            'startColumn' => 38,
            'endColumn' => 63,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'nameScope' => 
          array (
            'name' => 'nameScope',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\Analyser\\NameScope',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 288,
            'endLine' => 288,
            'startColumn' => 66,
            'endColumn' => 85,
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
 * @param array<string, array{string, TemplateTagValueNode}> $templatePhpDocNodes
 * @return array<string, TemplateTag>
 */',
        'startLine' => 288,
        'endLine' => 313,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\PhpDoc',
        'declaringClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'implementingClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'currentClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'aliasName' => NULL,
      ),
      'resolveParamTags' => 
      array (
        'name' => 'resolveParamTags',
        'parameters' => 
        array (
          'phpDocNode' => 
          array (
            'name' => 'phpDocNode',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 318,
            'endLine' => 318,
            'startColumn' => 35,
            'endColumn' => 56,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'nameScope' => 
          array (
            'name' => 'nameScope',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\Analyser\\NameScope',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 318,
            'endLine' => 318,
            'startColumn' => 59,
            'endColumn' => 78,
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
 * @return array<string, ParamTag>
 */',
        'startLine' => 318,
        'endLine' => 338,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\PhpDoc',
        'declaringClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'implementingClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'currentClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'aliasName' => NULL,
      ),
      'resolveParamOutTags' => 
      array (
        'name' => 'resolveParamOutTags',
        'parameters' => 
        array (
          'phpDocNode' => 
          array (
            'name' => 'phpDocNode',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 343,
            'endLine' => 343,
            'startColumn' => 38,
            'endColumn' => 59,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'nameScope' => 
          array (
            'name' => 'nameScope',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\Analyser\\NameScope',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 343,
            'endLine' => 343,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @return array<string, ParamOutTag>
 */',
        'startLine' => 343,
        'endLine' => 366,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\PhpDoc',
        'declaringClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'implementingClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'currentClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'aliasName' => NULL,
      ),
      'resolveParamImmediatelyInvokedCallable' => 
      array (
        'name' => 'resolveParamImmediatelyInvokedCallable',
        'parameters' => 
        array (
          'phpDocNode' => 
          array (
            'name' => 'phpDocNode',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 371,
            'endLine' => 371,
            'startColumn' => 57,
            'endColumn' => 78,
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
 * @return array<string, bool>
 */',
        'startLine' => 371,
        'endLine' => 388,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\PhpDoc',
        'declaringClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'implementingClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'currentClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'aliasName' => NULL,
      ),
      'resolveParamClosureThisTags' => 
      array (
        'name' => 'resolveParamClosureThisTags',
        'parameters' => 
        array (
          'phpDocNode' => 
          array (
            'name' => 'phpDocNode',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 393,
            'endLine' => 393,
            'startColumn' => 46,
            'endColumn' => 67,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'nameScope' => 
          array (
            'name' => 'nameScope',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\Analyser\\NameScope',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 393,
            'endLine' => 393,
            'startColumn' => 70,
            'endColumn' => 89,
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
 * @return array<string, ParamClosureThisTag>
 */',
        'startLine' => 393,
        'endLine' => 409,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\PhpDoc',
        'declaringClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'implementingClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'currentClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'aliasName' => NULL,
      ),
      'resolveReturnTag' => 
      array (
        'name' => 'resolveReturnTag',
        'parameters' => 
        array (
          'phpDocNode' => 
          array (
            'name' => 'phpDocNode',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 411,
            'endLine' => 411,
            'startColumn' => 35,
            'endColumn' => 56,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'nameScope' => 
          array (
            'name' => 'nameScope',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\Analyser\\NameScope',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 411,
            'endLine' => 411,
            'startColumn' => 59,
            'endColumn' => 78,
            'parameterIndex' => 1,
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
                  'name' => 'PHPStan\\PhpDoc\\Tag\\ReturnTag',
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
        'startLine' => 411,
        'endLine' => 426,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\PhpDoc',
        'declaringClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'implementingClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'currentClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'aliasName' => NULL,
      ),
      'resolveThrowsTags' => 
      array (
        'name' => 'resolveThrowsTags',
        'parameters' => 
        array (
          'phpDocNode' => 
          array (
            'name' => 'phpDocNode',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 428,
            'endLine' => 428,
            'startColumn' => 36,
            'endColumn' => 57,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'nameScope' => 
          array (
            'name' => 'nameScope',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\Analyser\\NameScope',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 428,
            'endLine' => 428,
            'startColumn' => 60,
            'endColumn' => 79,
            'parameterIndex' => 1,
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
                  'name' => 'PHPStan\\PhpDoc\\Tag\\ThrowsTag',
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
        'startLine' => 428,
        'endLine' => 448,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\PhpDoc',
        'declaringClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'implementingClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'currentClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'aliasName' => NULL,
      ),
      'resolveMixinTags' => 
      array (
        'name' => 'resolveMixinTags',
        'parameters' => 
        array (
          'phpDocNode' => 
          array (
            'name' => 'phpDocNode',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 453,
            'endLine' => 453,
            'startColumn' => 35,
            'endColumn' => 56,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'nameScope' => 
          array (
            'name' => 'nameScope',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\Analyser\\NameScope',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 453,
            'endLine' => 453,
            'startColumn' => 59,
            'endColumn' => 78,
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
 * @return array<MixinTag>
 */',
        'startLine' => 453,
        'endLine' => 458,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\PhpDoc',
        'declaringClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'implementingClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'currentClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'aliasName' => NULL,
      ),
      'resolveRequireExtendsTags' => 
      array (
        'name' => 'resolveRequireExtendsTags',
        'parameters' => 
        array (
          'phpDocNode' => 
          array (
            'name' => 'phpDocNode',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 463,
            'endLine' => 463,
            'startColumn' => 44,
            'endColumn' => 65,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'nameScope' => 
          array (
            'name' => 'nameScope',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\Analyser\\NameScope',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 463,
            'endLine' => 463,
            'startColumn' => 68,
            'endColumn' => 87,
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
 * @return array<RequireExtendsTag>
 */',
        'startLine' => 463,
        'endLine' => 476,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\PhpDoc',
        'declaringClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'implementingClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'currentClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'aliasName' => NULL,
      ),
      'resolveRequireImplementsTags' => 
      array (
        'name' => 'resolveRequireImplementsTags',
        'parameters' => 
        array (
          'phpDocNode' => 
          array (
            'name' => 'phpDocNode',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 481,
            'endLine' => 481,
            'startColumn' => 47,
            'endColumn' => 68,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'nameScope' => 
          array (
            'name' => 'nameScope',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\Analyser\\NameScope',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 481,
            'endLine' => 481,
            'startColumn' => 71,
            'endColumn' => 90,
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
 * @return array<RequireImplementsTag>
 */',
        'startLine' => 481,
        'endLine' => 494,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\PhpDoc',
        'declaringClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'implementingClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'currentClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'aliasName' => NULL,
      ),
      'resolveSealedTags' => 
      array (
        'name' => 'resolveSealedTags',
        'parameters' => 
        array (
          'phpDocNode' => 
          array (
            'name' => 'phpDocNode',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 499,
            'endLine' => 499,
            'startColumn' => 36,
            'endColumn' => 57,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'nameScope' => 
          array (
            'name' => 'nameScope',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\Analyser\\NameScope',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 499,
            'endLine' => 499,
            'startColumn' => 60,
            'endColumn' => 79,
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
 * @return array<SealedTypeTag>
 */',
        'startLine' => 499,
        'endLine' => 512,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\PhpDoc',
        'declaringClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'implementingClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'currentClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'aliasName' => NULL,
      ),
      'resolveTypeAliasTags' => 
      array (
        'name' => 'resolveTypeAliasTags',
        'parameters' => 
        array (
          'phpDocNode' => 
          array (
            'name' => 'phpDocNode',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 517,
            'endLine' => 517,
            'startColumn' => 39,
            'endColumn' => 60,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'nameScope' => 
          array (
            'name' => 'nameScope',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\Analyser\\NameScope',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 517,
            'endLine' => 517,
            'startColumn' => 63,
            'endColumn' => 82,
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
 * @return array<string, TypeAliasTag>
 */',
        'startLine' => 517,
        'endLine' => 530,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\PhpDoc',
        'declaringClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'implementingClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'currentClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'aliasName' => NULL,
      ),
      'resolveTypeAliasImportTags' => 
      array (
        'name' => 'resolveTypeAliasImportTags',
        'parameters' => 
        array (
          'phpDocNode' => 
          array (
            'name' => 'phpDocNode',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 535,
            'endLine' => 535,
            'startColumn' => 45,
            'endColumn' => 66,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'nameScope' => 
          array (
            'name' => 'nameScope',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\Analyser\\NameScope',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 535,
            'endLine' => 535,
            'startColumn' => 69,
            'endColumn' => 88,
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
 * @return array<string, TypeAliasImportTag>
 */',
        'startLine' => 535,
        'endLine' => 549,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\PhpDoc',
        'declaringClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'implementingClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'currentClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'aliasName' => NULL,
      ),
      'resolveAssertTags' => 
      array (
        'name' => 'resolveAssertTags',
        'parameters' => 
        array (
          'phpDocNode' => 
          array (
            'name' => 'phpDocNode',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 554,
            'endLine' => 554,
            'startColumn' => 36,
            'endColumn' => 57,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'nameScope' => 
          array (
            'name' => 'nameScope',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\Analyser\\NameScope',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 554,
            'endLine' => 554,
            'startColumn' => 60,
            'endColumn' => 79,
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
 * @return AssertTag[]
 */',
        'startLine' => 554,
        'endLine' => 569,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\PhpDoc',
        'declaringClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'implementingClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'currentClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'aliasName' => NULL,
      ),
      'resolveAssertTagsFor' => 
      array (
        'name' => 'resolveAssertTagsFor',
        'parameters' => 
        array (
          'phpDocNode' => 
          array (
            'name' => 'phpDocNode',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 575,
            'endLine' => 575,
            'startColumn' => 40,
            'endColumn' => 61,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'nameScope' => 
          array (
            'name' => 'nameScope',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\Analyser\\NameScope',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 575,
            'endLine' => 575,
            'startColumn' => 64,
            'endColumn' => 83,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'tagName' => 
          array (
            'name' => 'tagName',
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
            'startLine' => 575,
            'endLine' => 575,
            'startColumn' => 86,
            'endColumn' => 100,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'if' => 
          array (
            'name' => 'if',
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
            'startLine' => 575,
            'endLine' => 575,
            'startColumn' => 103,
            'endColumn' => 112,
            'parameterIndex' => 3,
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
 * @param AssertTag::NULL|AssertTag::IF_TRUE|AssertTag::IF_FALSE $if
 * @return AssertTag[]
 */',
        'startLine' => 575,
        'endLine' => 598,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'PHPStan\\PhpDoc',
        'declaringClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'implementingClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'currentClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'aliasName' => NULL,
      ),
      'resolveSelfOutTypeTag' => 
      array (
        'name' => 'resolveSelfOutTypeTag',
        'parameters' => 
        array (
          'phpDocNode' => 
          array (
            'name' => 'phpDocNode',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 600,
            'endLine' => 600,
            'startColumn' => 40,
            'endColumn' => 61,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'nameScope' => 
          array (
            'name' => 'nameScope',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\Analyser\\NameScope',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 600,
            'endLine' => 600,
            'startColumn' => 64,
            'endColumn' => 83,
            'parameterIndex' => 1,
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
                  'name' => 'PHPStan\\PhpDoc\\Tag\\SelfOutTypeTag',
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
        'startLine' => 600,
        'endLine' => 614,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\PhpDoc',
        'declaringClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'implementingClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'currentClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'aliasName' => NULL,
      ),
      'resolveDeprecatedTag' => 
      array (
        'name' => 'resolveDeprecatedTag',
        'parameters' => 
        array (
          'phpDocNode' => 
          array (
            'name' => 'phpDocNode',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 616,
            'endLine' => 616,
            'startColumn' => 39,
            'endColumn' => 60,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'nameScope' => 
          array (
            'name' => 'nameScope',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\Analyser\\NameScope',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 616,
            'endLine' => 616,
            'startColumn' => 63,
            'endColumn' => 82,
            'parameterIndex' => 1,
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
                  'name' => 'PHPStan\\PhpDoc\\Tag\\DeprecatedTag',
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
        'startLine' => 616,
        'endLine' => 624,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\PhpDoc',
        'declaringClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'implementingClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'currentClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'aliasName' => NULL,
      ),
      'resolveIsDeprecated' => 
      array (
        'name' => 'resolveIsDeprecated',
        'parameters' => 
        array (
          'phpDocNode' => 
          array (
            'name' => 'phpDocNode',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 626,
            'endLine' => 626,
            'startColumn' => 38,
            'endColumn' => 59,
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
        'startLine' => 626,
        'endLine' => 631,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\PhpDoc',
        'declaringClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'implementingClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'currentClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'aliasName' => NULL,
      ),
      'resolveIsNotDeprecated' => 
      array (
        'name' => 'resolveIsNotDeprecated',
        'parameters' => 
        array (
          'phpDocNode' => 
          array (
            'name' => 'phpDocNode',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 633,
            'endLine' => 633,
            'startColumn' => 41,
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
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 633,
        'endLine' => 638,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\PhpDoc',
        'declaringClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'implementingClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'currentClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'aliasName' => NULL,
      ),
      'resolveIsInternal' => 
      array (
        'name' => 'resolveIsInternal',
        'parameters' => 
        array (
          'phpDocNode' => 
          array (
            'name' => 'phpDocNode',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 640,
            'endLine' => 640,
            'startColumn' => 36,
            'endColumn' => 57,
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
        'startLine' => 640,
        'endLine' => 645,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\PhpDoc',
        'declaringClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'implementingClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'currentClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'aliasName' => NULL,
      ),
      'resolveIsFinal' => 
      array (
        'name' => 'resolveIsFinal',
        'parameters' => 
        array (
          'phpDocNode' => 
          array (
            'name' => 'phpDocNode',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 647,
            'endLine' => 647,
            'startColumn' => 33,
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
        'startLine' => 647,
        'endLine' => 652,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\PhpDoc',
        'declaringClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'implementingClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'currentClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'aliasName' => NULL,
      ),
      'resolveIsPure' => 
      array (
        'name' => 'resolveIsPure',
        'parameters' => 
        array (
          'phpDocNode' => 
          array (
            'name' => 'phpDocNode',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 654,
            'endLine' => 654,
            'startColumn' => 32,
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
        'docComment' => NULL,
        'startLine' => 654,
        'endLine' => 663,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\PhpDoc',
        'declaringClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'implementingClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'currentClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'aliasName' => NULL,
      ),
      'resolveIsImpure' => 
      array (
        'name' => 'resolveIsImpure',
        'parameters' => 
        array (
          'phpDocNode' => 
          array (
            'name' => 'phpDocNode',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 665,
            'endLine' => 665,
            'startColumn' => 34,
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
        'startLine' => 665,
        'endLine' => 674,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\PhpDoc',
        'declaringClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'implementingClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'currentClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'aliasName' => NULL,
      ),
      'resolveAllMethodsPure' => 
      array (
        'name' => 'resolveAllMethodsPure',
        'parameters' => 
        array (
          'phpDocNode' => 
          array (
            'name' => 'phpDocNode',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 676,
            'endLine' => 676,
            'startColumn' => 40,
            'endColumn' => 61,
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
        'startLine' => 676,
        'endLine' => 679,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\PhpDoc',
        'declaringClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'implementingClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'currentClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'aliasName' => NULL,
      ),
      'resolveAllMethodsImpure' => 
      array (
        'name' => 'resolveAllMethodsImpure',
        'parameters' => 
        array (
          'phpDocNode' => 
          array (
            'name' => 'phpDocNode',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 681,
            'endLine' => 681,
            'startColumn' => 42,
            'endColumn' => 63,
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
        'startLine' => 681,
        'endLine' => 684,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\PhpDoc',
        'declaringClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'implementingClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'currentClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'aliasName' => NULL,
      ),
      'resolveIsReadOnly' => 
      array (
        'name' => 'resolveIsReadOnly',
        'parameters' => 
        array (
          'phpDocNode' => 
          array (
            'name' => 'phpDocNode',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 686,
            'endLine' => 686,
            'startColumn' => 36,
            'endColumn' => 57,
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
        'startLine' => 686,
        'endLine' => 697,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\PhpDoc',
        'declaringClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'implementingClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'currentClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'aliasName' => NULL,
      ),
      'resolveIsImmutable' => 
      array (
        'name' => 'resolveIsImmutable',
        'parameters' => 
        array (
          'phpDocNode' => 
          array (
            'name' => 'phpDocNode',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 699,
            'endLine' => 699,
            'startColumn' => 37,
            'endColumn' => 58,
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
        'startLine' => 699,
        'endLine' => 710,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\PhpDoc',
        'declaringClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'implementingClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'currentClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'aliasName' => NULL,
      ),
      'resolveHasConsistentConstructor' => 
      array (
        'name' => 'resolveHasConsistentConstructor',
        'parameters' => 
        array (
          'phpDocNode' => 
          array (
            'name' => 'phpDocNode',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 712,
            'endLine' => 712,
            'startColumn' => 50,
            'endColumn' => 71,
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
        'startLine' => 712,
        'endLine' => 723,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\PhpDoc',
        'declaringClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'implementingClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'currentClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'aliasName' => NULL,
      ),
      'resolveAcceptsNamedArguments' => 
      array (
        'name' => 'resolveAcceptsNamedArguments',
        'parameters' => 
        array (
          'phpDocNode' => 
          array (
            'name' => 'phpDocNode',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 725,
            'endLine' => 725,
            'startColumn' => 47,
            'endColumn' => 68,
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
        'startLine' => 725,
        'endLine' => 728,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\PhpDoc',
        'declaringClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'implementingClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'currentClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'aliasName' => NULL,
      ),
      'shouldSkipType' => 
      array (
        'name' => 'shouldSkipType',
        'parameters' => 
        array (
          'tagName' => 
          array (
            'name' => 'tagName',
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
            'startLine' => 730,
            'endLine' => 730,
            'startColumn' => 34,
            'endColumn' => 48,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
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
            'startLine' => 730,
            'endLine' => 730,
            'startColumn' => 51,
            'endColumn' => 60,
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
        'docComment' => NULL,
        'startLine' => 730,
        'endLine' => 737,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'PHPStan\\PhpDoc',
        'declaringClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'implementingClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'currentClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'aliasName' => NULL,
      ),
      'resolveAllowPrivateMutation' => 
      array (
        'name' => 'resolveAllowPrivateMutation',
        'parameters' => 
        array (
          'phpDocNode' => 
          array (
            'name' => 'phpDocNode',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 739,
            'endLine' => 739,
            'startColumn' => 46,
            'endColumn' => 67,
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
        'startLine' => 739,
        'endLine' => 750,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\PhpDoc',
        'declaringClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'implementingClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
        'currentClassName' => 'PHPStan\\PhpDoc\\PhpDocNodeResolver',
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