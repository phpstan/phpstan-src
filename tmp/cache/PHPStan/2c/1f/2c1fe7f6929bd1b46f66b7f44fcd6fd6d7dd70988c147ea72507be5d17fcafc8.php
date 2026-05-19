<?php declare(strict_types = 1);

// osfsl-/home/runner/work/phpstan-src/phpstan-src/vendor/composer/../phpstan/phpdoc-parser/src/Ast/PhpDoc/PhpDocNode.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-3e1aaea09fe54946f9f4a0fdcaf257b5ec0e79e70bb44472254758a5d9302d12-8.4.21-6.70.0.1',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/vendor/composer/../phpstan/phpdoc-parser/src/Ast/PhpDoc/PhpDocNode.php',
      ),
    ),
    'namespace' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc',
    'name' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
    'shortName' => 'PhpDocNode',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => NULL,
    'attributes' => 
    array (
    ),
    'startLine' => 12,
    'endLine' => 389,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
      0 => 'PHPStan\\PhpDocParser\\Ast\\Node',
    ),
    'traitClassNames' => 
    array (
      0 => 'PHPStan\\PhpDocParser\\Ast\\NodeAttributes',
    ),
    'immediateConstants' => 
    array (
    ),
    'immediateProperties' => 
    array (
      'children' => 
      array (
        'declaringClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
        'implementingClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
        'name' => 'children',
        'modifiers' => 1,
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
        'docComment' => '/** @var PhpDocChildNode[] */',
        'attributes' => 
        array (
        ),
        'startLine' => 18,
        'endLine' => 18,
        'startColumn' => 2,
        'endColumn' => 24,
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
          'children' => 
          array (
            'name' => 'children',
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
            'startLine' => 23,
            'endLine' => 23,
            'startColumn' => 30,
            'endColumn' => 44,
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
 * @param PhpDocChildNode[] $children
 */',
        'startLine' => 23,
        'endLine' => 26,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc',
        'declaringClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
        'implementingClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
        'currentClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
        'aliasName' => NULL,
      ),
      'getTags' => 
      array (
        'name' => 'getTags',
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
 * @return PhpDocTagNode[]
 */',
        'startLine' => 31,
        'endLine' => 34,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc',
        'declaringClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
        'implementingClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
        'currentClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
        'aliasName' => NULL,
      ),
      'getTagsByName' => 
      array (
        'name' => 'getTagsByName',
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
            'startLine' => 39,
            'endLine' => 39,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @return PhpDocTagNode[]
 */',
        'startLine' => 39,
        'endLine' => 42,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc',
        'declaringClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
        'implementingClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
        'currentClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
        'aliasName' => NULL,
      ),
      'getVarTagValues' => 
      array (
        'name' => 'getVarTagValues',
        'parameters' => 
        array (
          'tagName' => 
          array (
            'name' => 'tagName',
            'default' => 
            array (
              'code' => '\'@var\'',
              'attributes' => 
              array (
                'startLine' => 47,
                'endLine' => 47,
                'startTokenPos' => 223,
                'startFilePos' => 1000,
                'endTokenPos' => 223,
                'endFilePos' => 1005,
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
            'startLine' => 47,
            'endLine' => 47,
            'startColumn' => 34,
            'endColumn' => 57,
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
 * @return VarTagValueNode[]
 */',
        'startLine' => 47,
        'endLine' => 53,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc',
        'declaringClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
        'implementingClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
        'currentClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
        'aliasName' => NULL,
      ),
      'getParamTagValues' => 
      array (
        'name' => 'getParamTagValues',
        'parameters' => 
        array (
          'tagName' => 
          array (
            'name' => 'tagName',
            'default' => 
            array (
              'code' => '\'@param\'',
              'attributes' => 
              array (
                'startLine' => 58,
                'endLine' => 58,
                'startTokenPos' => 291,
                'startFilePos' => 1288,
                'endTokenPos' => 291,
                'endFilePos' => 1295,
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
            'startLine' => 58,
            'endLine' => 58,
            'startColumn' => 36,
            'endColumn' => 61,
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
 * @return ParamTagValueNode[]
 */',
        'startLine' => 58,
        'endLine' => 64,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc',
        'declaringClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
        'implementingClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
        'currentClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
        'aliasName' => NULL,
      ),
      'getTypelessParamTagValues' => 
      array (
        'name' => 'getTypelessParamTagValues',
        'parameters' => 
        array (
          'tagName' => 
          array (
            'name' => 'tagName',
            'default' => 
            array (
              'code' => '\'@param\'',
              'attributes' => 
              array (
                'startLine' => 69,
                'endLine' => 69,
                'startTokenPos' => 359,
                'startFilePos' => 1596,
                'endTokenPos' => 359,
                'endFilePos' => 1603,
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
            'startLine' => 69,
            'endLine' => 69,
            'startColumn' => 44,
            'endColumn' => 69,
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
 * @return TypelessParamTagValueNode[]
 */',
        'startLine' => 69,
        'endLine' => 75,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc',
        'declaringClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
        'implementingClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
        'currentClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
        'aliasName' => NULL,
      ),
      'getParamImmediatelyInvokedCallableTagValues' => 
      array (
        'name' => 'getParamImmediatelyInvokedCallableTagValues',
        'parameters' => 
        array (
          'tagName' => 
          array (
            'name' => 'tagName',
            'default' => 
            array (
              'code' => '\'@param-immediately-invoked-callable\'',
              'attributes' => 
              array (
                'startLine' => 80,
                'endLine' => 80,
                'startTokenPos' => 427,
                'startFilePos' => 1948,
                'endTokenPos' => 427,
                'endFilePos' => 1984,
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
            'startLine' => 80,
            'endLine' => 80,
            'startColumn' => 62,
            'endColumn' => 116,
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
 * @return ParamImmediatelyInvokedCallableTagValueNode[]
 */',
        'startLine' => 80,
        'endLine' => 86,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc',
        'declaringClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
        'implementingClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
        'currentClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
        'aliasName' => NULL,
      ),
      'getParamLaterInvokedCallableTagValues' => 
      array (
        'name' => 'getParamLaterInvokedCallableTagValues',
        'parameters' => 
        array (
          'tagName' => 
          array (
            'name' => 'tagName',
            'default' => 
            array (
              'code' => '\'@param-later-invoked-callable\'',
              'attributes' => 
              array (
                'startLine' => 91,
                'endLine' => 91,
                'startTokenPos' => 495,
                'startFilePos' => 2335,
                'endTokenPos' => 495,
                'endFilePos' => 2365,
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
            'startLine' => 91,
            'endLine' => 91,
            'startColumn' => 56,
            'endColumn' => 104,
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
 * @return ParamLaterInvokedCallableTagValueNode[]
 */',
        'startLine' => 91,
        'endLine' => 97,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc',
        'declaringClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
        'implementingClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
        'currentClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
        'aliasName' => NULL,
      ),
      'getParamClosureThisTagValues' => 
      array (
        'name' => 'getParamClosureThisTagValues',
        'parameters' => 
        array (
          'tagName' => 
          array (
            'name' => 'tagName',
            'default' => 
            array (
              'code' => '\'@param-closure-this\'',
              'attributes' => 
              array (
                'startLine' => 102,
                'endLine' => 102,
                'startTokenPos' => 563,
                'startFilePos' => 2692,
                'endTokenPos' => 563,
                'endFilePos' => 2712,
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
            'startLine' => 102,
            'endLine' => 102,
            'startColumn' => 47,
            'endColumn' => 85,
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
 * @return ParamClosureThisTagValueNode[]
 */',
        'startLine' => 102,
        'endLine' => 108,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc',
        'declaringClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
        'implementingClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
        'currentClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
        'aliasName' => NULL,
      ),
      'getPureUnlessCallableIsImpureTagValues' => 
      array (
        'name' => 'getPureUnlessCallableIsImpureTagValues',
        'parameters' => 
        array (
          'tagName' => 
          array (
            'name' => 'tagName',
            'default' => 
            array (
              'code' => '\'@pure-unless-callable-is-impure\'',
              'attributes' => 
              array (
                'startLine' => 113,
                'endLine' => 113,
                'startTokenPos' => 631,
                'startFilePos' => 3050,
                'endTokenPos' => 631,
                'endFilePos' => 3082,
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
            'startLine' => 113,
            'endLine' => 113,
            'startColumn' => 57,
            'endColumn' => 107,
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
 * @return PureUnlessCallableIsImpureTagValueNode[]
 */',
        'startLine' => 113,
        'endLine' => 119,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc',
        'declaringClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
        'implementingClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
        'currentClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
        'aliasName' => NULL,
      ),
      'getTemplateTagValues' => 
      array (
        'name' => 'getTemplateTagValues',
        'parameters' => 
        array (
          'tagName' => 
          array (
            'name' => 'tagName',
            'default' => 
            array (
              'code' => '\'@template\'',
              'attributes' => 
              array (
                'startLine' => 124,
                'endLine' => 124,
                'startTokenPos' => 699,
                'startFilePos' => 3394,
                'endTokenPos' => 699,
                'endFilePos' => 3404,
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
            'startLine' => 124,
            'endLine' => 124,
            'startColumn' => 39,
            'endColumn' => 67,
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
 * @return TemplateTagValueNode[]
 */',
        'startLine' => 124,
        'endLine' => 130,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc',
        'declaringClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
        'implementingClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
        'currentClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
        'aliasName' => NULL,
      ),
      'getExtendsTagValues' => 
      array (
        'name' => 'getExtendsTagValues',
        'parameters' => 
        array (
          'tagName' => 
          array (
            'name' => 'tagName',
            'default' => 
            array (
              'code' => '\'@extends\'',
              'attributes' => 
              array (
                'startLine' => 135,
                'endLine' => 135,
                'startTokenPos' => 767,
                'startFilePos' => 3696,
                'endTokenPos' => 767,
                'endFilePos' => 3705,
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
            'startLine' => 135,
            'endLine' => 135,
            'startColumn' => 38,
            'endColumn' => 65,
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
 * @return ExtendsTagValueNode[]
 */',
        'startLine' => 135,
        'endLine' => 141,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc',
        'declaringClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
        'implementingClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
        'currentClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
        'aliasName' => NULL,
      ),
      'getImplementsTagValues' => 
      array (
        'name' => 'getImplementsTagValues',
        'parameters' => 
        array (
          'tagName' => 
          array (
            'name' => 'tagName',
            'default' => 
            array (
              'code' => '\'@implements\'',
              'attributes' => 
              array (
                'startLine' => 146,
                'endLine' => 146,
                'startTokenPos' => 835,
                'startFilePos' => 4002,
                'endTokenPos' => 835,
                'endFilePos' => 4014,
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
            'startLine' => 146,
            'endLine' => 146,
            'startColumn' => 41,
            'endColumn' => 71,
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
 * @return ImplementsTagValueNode[]
 */',
        'startLine' => 146,
        'endLine' => 152,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc',
        'declaringClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
        'implementingClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
        'currentClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
        'aliasName' => NULL,
      ),
      'getUsesTagValues' => 
      array (
        'name' => 'getUsesTagValues',
        'parameters' => 
        array (
          'tagName' => 
          array (
            'name' => 'tagName',
            'default' => 
            array (
              'code' => '\'@use\'',
              'attributes' => 
              array (
                'startLine' => 157,
                'endLine' => 157,
                'startTokenPos' => 903,
                'startFilePos' => 4302,
                'endTokenPos' => 903,
                'endFilePos' => 4307,
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
            'startLine' => 157,
            'endLine' => 157,
            'startColumn' => 35,
            'endColumn' => 58,
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
 * @return UsesTagValueNode[]
 */',
        'startLine' => 157,
        'endLine' => 163,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc',
        'declaringClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
        'implementingClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
        'currentClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
        'aliasName' => NULL,
      ),
      'getReturnTagValues' => 
      array (
        'name' => 'getReturnTagValues',
        'parameters' => 
        array (
          'tagName' => 
          array (
            'name' => 'tagName',
            'default' => 
            array (
              'code' => '\'@return\'',
              'attributes' => 
              array (
                'startLine' => 168,
                'endLine' => 168,
                'startTokenPos' => 971,
                'startFilePos' => 4593,
                'endTokenPos' => 971,
                'endFilePos' => 4601,
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
            'startLine' => 168,
            'endLine' => 168,
            'startColumn' => 37,
            'endColumn' => 63,
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
 * @return ReturnTagValueNode[]
 */',
        'startLine' => 168,
        'endLine' => 174,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc',
        'declaringClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
        'implementingClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
        'currentClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
        'aliasName' => NULL,
      ),
      'getThrowsTagValues' => 
      array (
        'name' => 'getThrowsTagValues',
        'parameters' => 
        array (
          'tagName' => 
          array (
            'name' => 'tagName',
            'default' => 
            array (
              'code' => '\'@throws\'',
              'attributes' => 
              array (
                'startLine' => 179,
                'endLine' => 179,
                'startTokenPos' => 1039,
                'startFilePos' => 4889,
                'endTokenPos' => 1039,
                'endFilePos' => 4897,
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
            'startLine' => 179,
            'endLine' => 179,
            'startColumn' => 37,
            'endColumn' => 63,
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
 * @return ThrowsTagValueNode[]
 */',
        'startLine' => 179,
        'endLine' => 185,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc',
        'declaringClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
        'implementingClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
        'currentClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
        'aliasName' => NULL,
      ),
      'getMixinTagValues' => 
      array (
        'name' => 'getMixinTagValues',
        'parameters' => 
        array (
          'tagName' => 
          array (
            'name' => 'tagName',
            'default' => 
            array (
              'code' => '\'@mixin\'',
              'attributes' => 
              array (
                'startLine' => 190,
                'endLine' => 190,
                'startTokenPos' => 1107,
                'startFilePos' => 5183,
                'endTokenPos' => 1107,
                'endFilePos' => 5190,
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
            'startLine' => 190,
            'endLine' => 190,
            'startColumn' => 36,
            'endColumn' => 61,
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
 * @return MixinTagValueNode[]
 */',
        'startLine' => 190,
        'endLine' => 196,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc',
        'declaringClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
        'implementingClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
        'currentClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
        'aliasName' => NULL,
      ),
      'getRequireExtendsTagValues' => 
      array (
        'name' => 'getRequireExtendsTagValues',
        'parameters' => 
        array (
          'tagName' => 
          array (
            'name' => 'tagName',
            'default' => 
            array (
              'code' => '\'@phpstan-require-extends\'',
              'attributes' => 
              array (
                'startLine' => 201,
                'endLine' => 201,
                'startTokenPos' => 1175,
                'startFilePos' => 5493,
                'endTokenPos' => 1175,
                'endFilePos' => 5518,
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
            'startLine' => 201,
            'endLine' => 201,
            'startColumn' => 45,
            'endColumn' => 88,
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
 * @return RequireExtendsTagValueNode[]
 */',
        'startLine' => 201,
        'endLine' => 207,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc',
        'declaringClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
        'implementingClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
        'currentClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
        'aliasName' => NULL,
      ),
      'getRequireImplementsTagValues' => 
      array (
        'name' => 'getRequireImplementsTagValues',
        'parameters' => 
        array (
          'tagName' => 
          array (
            'name' => 'tagName',
            'default' => 
            array (
              'code' => '\'@phpstan-require-implements\'',
              'attributes' => 
              array (
                'startLine' => 212,
                'endLine' => 212,
                'startTokenPos' => 1243,
                'startFilePos' => 5836,
                'endTokenPos' => 1243,
                'endFilePos' => 5864,
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
            'startLine' => 212,
            'endLine' => 212,
            'startColumn' => 48,
            'endColumn' => 94,
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
 * @return RequireImplementsTagValueNode[]
 */',
        'startLine' => 212,
        'endLine' => 218,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc',
        'declaringClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
        'implementingClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
        'currentClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
        'aliasName' => NULL,
      ),
      'getSealedTagValues' => 
      array (
        'name' => 'getSealedTagValues',
        'parameters' => 
        array (
          'tagName' => 
          array (
            'name' => 'tagName',
            'default' => 
            array (
              'code' => '\'@phpstan-sealed\'',
              'attributes' => 
              array (
                'startLine' => 223,
                'endLine' => 223,
                'startTokenPos' => 1311,
                'startFilePos' => 6163,
                'endTokenPos' => 1311,
                'endFilePos' => 6179,
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
            'startLine' => 223,
            'endLine' => 223,
            'startColumn' => 37,
            'endColumn' => 71,
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
 * @return SealedTagValueNode[]
 */',
        'startLine' => 223,
        'endLine' => 229,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc',
        'declaringClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
        'implementingClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
        'currentClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
        'aliasName' => NULL,
      ),
      'getDeprecatedTagValues' => 
      array (
        'name' => 'getDeprecatedTagValues',
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
 * @return DeprecatedTagValueNode[]
 */',
        'startLine' => 234,
        'endLine' => 240,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc',
        'declaringClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
        'implementingClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
        'currentClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
        'aliasName' => NULL,
      ),
      'getPropertyTagValues' => 
      array (
        'name' => 'getPropertyTagValues',
        'parameters' => 
        array (
          'tagName' => 
          array (
            'name' => 'tagName',
            'default' => 
            array (
              'code' => '\'@property\'',
              'attributes' => 
              array (
                'startLine' => 245,
                'endLine' => 245,
                'startTokenPos' => 1440,
                'startFilePos' => 6757,
                'endTokenPos' => 1440,
                'endFilePos' => 6767,
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
            'startLine' => 245,
            'endLine' => 245,
            'startColumn' => 39,
            'endColumn' => 67,
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
 * @return PropertyTagValueNode[]
 */',
        'startLine' => 245,
        'endLine' => 251,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc',
        'declaringClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
        'implementingClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
        'currentClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
        'aliasName' => NULL,
      ),
      'getPropertyReadTagValues' => 
      array (
        'name' => 'getPropertyReadTagValues',
        'parameters' => 
        array (
          'tagName' => 
          array (
            'name' => 'tagName',
            'default' => 
            array (
              'code' => '\'@property-read\'',
              'attributes' => 
              array (
                'startLine' => 256,
                'endLine' => 256,
                'startTokenPos' => 1508,
                'startFilePos' => 7065,
                'endTokenPos' => 1508,
                'endFilePos' => 7080,
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
            'startLine' => 256,
            'endLine' => 256,
            'startColumn' => 43,
            'endColumn' => 76,
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
 * @return PropertyTagValueNode[]
 */',
        'startLine' => 256,
        'endLine' => 262,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc',
        'declaringClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
        'implementingClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
        'currentClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
        'aliasName' => NULL,
      ),
      'getPropertyWriteTagValues' => 
      array (
        'name' => 'getPropertyWriteTagValues',
        'parameters' => 
        array (
          'tagName' => 
          array (
            'name' => 'tagName',
            'default' => 
            array (
              'code' => '\'@property-write\'',
              'attributes' => 
              array (
                'startLine' => 267,
                'endLine' => 267,
                'startTokenPos' => 1576,
                'startFilePos' => 7379,
                'endTokenPos' => 1576,
                'endFilePos' => 7395,
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
            'startLine' => 267,
            'endLine' => 267,
            'startColumn' => 44,
            'endColumn' => 78,
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
 * @return PropertyTagValueNode[]
 */',
        'startLine' => 267,
        'endLine' => 273,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc',
        'declaringClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
        'implementingClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
        'currentClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
        'aliasName' => NULL,
      ),
      'getMethodTagValues' => 
      array (
        'name' => 'getMethodTagValues',
        'parameters' => 
        array (
          'tagName' => 
          array (
            'name' => 'tagName',
            'default' => 
            array (
              'code' => '\'@method\'',
              'attributes' => 
              array (
                'startLine' => 278,
                'endLine' => 278,
                'startTokenPos' => 1644,
                'startFilePos' => 7685,
                'endTokenPos' => 1644,
                'endFilePos' => 7693,
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
            'startLine' => 278,
            'endLine' => 278,
            'startColumn' => 37,
            'endColumn' => 63,
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
 * @return MethodTagValueNode[]
 */',
        'startLine' => 278,
        'endLine' => 284,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc',
        'declaringClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
        'implementingClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
        'currentClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
        'aliasName' => NULL,
      ),
      'getTypeAliasTagValues' => 
      array (
        'name' => 'getTypeAliasTagValues',
        'parameters' => 
        array (
          'tagName' => 
          array (
            'name' => 'tagName',
            'default' => 
            array (
              'code' => '\'@phpstan-type\'',
              'attributes' => 
              array (
                'startLine' => 289,
                'endLine' => 289,
                'startTokenPos' => 1712,
                'startFilePos' => 7987,
                'endTokenPos' => 1712,
                'endFilePos' => 8001,
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
            'startLine' => 289,
            'endLine' => 289,
            'startColumn' => 40,
            'endColumn' => 72,
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
 * @return TypeAliasTagValueNode[]
 */',
        'startLine' => 289,
        'endLine' => 295,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc',
        'declaringClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
        'implementingClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
        'currentClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
        'aliasName' => NULL,
      ),
      'getTypeAliasImportTagValues' => 
      array (
        'name' => 'getTypeAliasImportTagValues',
        'parameters' => 
        array (
          'tagName' => 
          array (
            'name' => 'tagName',
            'default' => 
            array (
              'code' => '\'@phpstan-import-type\'',
              'attributes' => 
              array (
                'startLine' => 300,
                'endLine' => 300,
                'startTokenPos' => 1780,
                'startFilePos' => 8310,
                'endTokenPos' => 1780,
                'endFilePos' => 8331,
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
            'startLine' => 300,
            'endLine' => 300,
            'startColumn' => 46,
            'endColumn' => 85,
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
 * @return TypeAliasImportTagValueNode[]
 */',
        'startLine' => 300,
        'endLine' => 306,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc',
        'declaringClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
        'implementingClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
        'currentClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
        'aliasName' => NULL,
      ),
      'getAssertTagValues' => 
      array (
        'name' => 'getAssertTagValues',
        'parameters' => 
        array (
          'tagName' => 
          array (
            'name' => 'tagName',
            'default' => 
            array (
              'code' => '\'@phpstan-assert\'',
              'attributes' => 
              array (
                'startLine' => 311,
                'endLine' => 311,
                'startTokenPos' => 1848,
                'startFilePos' => 8628,
                'endTokenPos' => 1848,
                'endFilePos' => 8644,
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
            'startLine' => 311,
            'endLine' => 311,
            'startColumn' => 37,
            'endColumn' => 71,
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
 * @return AssertTagValueNode[]
 */',
        'startLine' => 311,
        'endLine' => 317,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc',
        'declaringClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
        'implementingClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
        'currentClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
        'aliasName' => NULL,
      ),
      'getAssertPropertyTagValues' => 
      array (
        'name' => 'getAssertPropertyTagValues',
        'parameters' => 
        array (
          'tagName' => 
          array (
            'name' => 'tagName',
            'default' => 
            array (
              'code' => '\'@phpstan-assert\'',
              'attributes' => 
              array (
                'startLine' => 322,
                'endLine' => 322,
                'startTokenPos' => 1916,
                'startFilePos' => 8948,
                'endTokenPos' => 1916,
                'endFilePos' => 8964,
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
            'startLine' => 322,
            'endLine' => 322,
            'startColumn' => 45,
            'endColumn' => 79,
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
 * @return AssertTagPropertyValueNode[]
 */',
        'startLine' => 322,
        'endLine' => 328,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc',
        'declaringClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
        'implementingClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
        'currentClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
        'aliasName' => NULL,
      ),
      'getAssertMethodTagValues' => 
      array (
        'name' => 'getAssertMethodTagValues',
        'parameters' => 
        array (
          'tagName' => 
          array (
            'name' => 'tagName',
            'default' => 
            array (
              'code' => '\'@phpstan-assert\'',
              'attributes' => 
              array (
                'startLine' => 333,
                'endLine' => 333,
                'startTokenPos' => 1984,
                'startFilePos' => 9272,
                'endTokenPos' => 1984,
                'endFilePos' => 9288,
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
            'startLine' => 333,
            'endLine' => 333,
            'startColumn' => 43,
            'endColumn' => 77,
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
 * @return AssertTagMethodValueNode[]
 */',
        'startLine' => 333,
        'endLine' => 339,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc',
        'declaringClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
        'implementingClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
        'currentClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
        'aliasName' => NULL,
      ),
      'getSelfOutTypeTagValues' => 
      array (
        'name' => 'getSelfOutTypeTagValues',
        'parameters' => 
        array (
          'tagName' => 
          array (
            'name' => 'tagName',
            'default' => 
            array (
              'code' => '\'@phpstan-this-out\'',
              'attributes' => 
              array (
                'startLine' => 344,
                'endLine' => 344,
                'startTokenPos' => 2052,
                'startFilePos' => 9588,
                'endTokenPos' => 2052,
                'endFilePos' => 9606,
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
            'startLine' => 344,
            'endLine' => 344,
            'startColumn' => 42,
            'endColumn' => 78,
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
 * @return SelfOutTagValueNode[]
 */',
        'startLine' => 344,
        'endLine' => 350,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc',
        'declaringClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
        'implementingClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
        'currentClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
        'aliasName' => NULL,
      ),
      'getParamOutTypeTagValues' => 
      array (
        'name' => 'getParamOutTypeTagValues',
        'parameters' => 
        array (
          'tagName' => 
          array (
            'name' => 'tagName',
            'default' => 
            array (
              'code' => '\'@param-out\'',
              'attributes' => 
              array (
                'startLine' => 355,
                'endLine' => 355,
                'startTokenPos' => 2120,
                'startFilePos' => 9903,
                'endTokenPos' => 2120,
                'endFilePos' => 9914,
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
            'startLine' => 355,
            'endLine' => 355,
            'startColumn' => 43,
            'endColumn' => 72,
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
 * @return ParamOutTagValueNode[]
 */',
        'startLine' => 355,
        'endLine' => 361,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc',
        'declaringClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
        'implementingClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
        'currentClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
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
        'docComment' => NULL,
        'startLine' => 363,
        'endLine' => 373,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc',
        'declaringClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
        'implementingClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
        'currentClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
        'aliasName' => NULL,
      ),
      '__set_state' => 
      array (
        'name' => '__set_state',
        'parameters' => 
        array (
          'properties' => 
          array (
            'name' => 'properties',
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
            'startLine' => 378,
            'endLine' => 378,
            'startColumn' => 37,
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
            'name' => 'self',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @param array<string, mixed> $properties
 */',
        'startLine' => 378,
        'endLine' => 387,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc',
        'declaringClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
        'implementingClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
        'currentClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode',
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