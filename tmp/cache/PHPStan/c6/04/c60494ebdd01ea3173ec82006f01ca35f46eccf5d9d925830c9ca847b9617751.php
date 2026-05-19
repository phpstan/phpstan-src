<?php declare(strict_types = 1);

// osfsl-/home/runner/work/phpstan-src/phpstan-src/vendor/composer/../phpstan/phpdoc-parser/src/Printer/Printer.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\PhpDocParser\Printer\Printer
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-28ba59c22c5ba63d4b66aa17be6085d12a040248076e237416e1f44e13ac9cf5-8.4.21-6.70.0.1',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\PhpDocParser\\Printer\\Printer',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/vendor/composer/../phpstan/phpdoc-parser/src/Printer/Printer.php',
      ),
    ),
    'namespace' => 'PHPStan\\PhpDocParser\\Printer',
    'name' => 'PHPStan\\PhpDocParser\\Printer\\Printer',
    'shortName' => 'Printer',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Inspired by https://github.com/nikic/PHP-Parser/tree/36a6dcd04e7b0285e8f0868f44bd4927802f7df1
 *
 * Copyright (c) 2011, Nikita Popov
 * All rights reserved.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 92,
    'endLine' => 920,
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
      'differ' => 
      array (
        'declaringClassName' => 'PHPStan\\PhpDocParser\\Printer\\Printer',
        'implementingClassName' => 'PHPStan\\PhpDocParser\\Printer\\Printer',
        'name' => 'differ',
        'modifiers' => 4,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPStan\\PhpDocParser\\Printer\\Differ',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => '/** @var Differ<Node> */',
        'attributes' => 
        array (
        ),
        'startLine' => 96,
        'endLine' => 96,
        'startColumn' => 2,
        'endColumn' => 24,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'listInsertionMap' => 
      array (
        'declaringClassName' => 'PHPStan\\PhpDocParser\\Printer\\Printer',
        'implementingClassName' => 'PHPStan\\PhpDocParser\\Printer\\Printer',
        'name' => 'listInsertionMap',
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
        'default' => 
        array (
          'code' => '[\\PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocNode::class . \'->children\' => "\\n * ", \\PHPStan\\PhpDocParser\\Ast\\Type\\UnionTypeNode::class . \'->types\' => \'|\', \\PHPStan\\PhpDocParser\\Ast\\Type\\IntersectionTypeNode::class . \'->types\' => \'&\', \\PHPStan\\PhpDocParser\\Ast\\Type\\ArrayShapeNode::class . \'->items\' => \', \', \\PHPStan\\PhpDocParser\\Ast\\Type\\ObjectShapeNode::class . \'->items\' => \', \', \\PHPStan\\PhpDocParser\\Ast\\Type\\CallableTypeNode::class . \'->parameters\' => \', \', \\PHPStan\\PhpDocParser\\Ast\\Type\\CallableTypeNode::class . \'->templateTypes\' => \', \', \\PHPStan\\PhpDocParser\\Ast\\Type\\GenericTypeNode::class . \'->genericTypes\' => \', \', \\PHPStan\\PhpDocParser\\Ast\\ConstExpr\\ConstExprArrayNode::class . \'->items\' => \', \', \\PHPStan\\PhpDocParser\\Ast\\PhpDoc\\MethodTagValueNode::class . \'->parameters\' => \', \', \\PHPStan\\PhpDocParser\\Ast\\PhpDoc\\Doctrine\\DoctrineArray::class . \'->items\' => \', \', \\PHPStan\\PhpDocParser\\Ast\\PhpDoc\\Doctrine\\DoctrineAnnotation::class . \'->arguments\' => \', \']',
          'attributes' => 
          array (
            'startLine' => 104,
            'endLine' => 117,
            'startTokenPos' => 477,
            'startFilePos' => 4452,
            'endTokenPos' => 635,
            'endFilePos' => 5046,
          ),
        ),
        'docComment' => '/**
 * Map From "{$class}->{$subNode}" to string that should be inserted
 * between elements of this list subnode
 *
 * @var array<string, string>
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 104,
        'endLine' => 117,
        'startColumn' => 2,
        'endColumn' => 3,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'emptyListInsertionMap' => 
      array (
        'declaringClassName' => 'PHPStan\\PhpDocParser\\Printer\\Printer',
        'implementingClassName' => 'PHPStan\\PhpDocParser\\Printer\\Printer',
        'name' => 'emptyListInsertionMap',
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
        'default' => 
        array (
          'code' => '[\\PHPStan\\PhpDocParser\\Ast\\Type\\CallableTypeNode::class . \'->parameters\' => [\'(\', \'\', \'\'], \\PHPStan\\PhpDocParser\\Ast\\Type\\ArrayShapeNode::class . \'->items\' => [\'{\', \'\', \'\'], \\PHPStan\\PhpDocParser\\Ast\\Type\\ObjectShapeNode::class . \'->items\' => [\'{\', \'\', \'\'], \\PHPStan\\PhpDocParser\\Ast\\PhpDoc\\Doctrine\\DoctrineArray::class . \'->items\' => [\'{\', \'\', \'\'], \\PHPStan\\PhpDocParser\\Ast\\PhpDoc\\Doctrine\\DoctrineAnnotation::class . \'->arguments\' => [\'(\', \'\', \'\']]',
          'attributes' => 
          array (
            'startLine' => 124,
            'endLine' => 130,
            'startTokenPos' => 648,
            'startFilePos' => 5200,
            'endTokenPos' => 755,
            'endFilePos' => 5488,
          ),
        ),
        'docComment' => '/**
 * [$find, $extraLeft, $extraRight]
 *
 * @var array<string, array{string|null, string, string}>
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 124,
        'endLine' => 130,
        'startColumn' => 2,
        'endColumn' => 3,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'parenthesesMap' => 
      array (
        'declaringClassName' => 'PHPStan\\PhpDocParser\\Printer\\Printer',
        'implementingClassName' => 'PHPStan\\PhpDocParser\\Printer\\Printer',
        'name' => 'parenthesesMap',
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
        'default' => 
        array (
          'code' => '[\\PHPStan\\PhpDocParser\\Ast\\Type\\CallableTypeNode::class . \'->returnType\' => [\\PHPStan\\PhpDocParser\\Ast\\Type\\CallableTypeNode::class, \\PHPStan\\PhpDocParser\\Ast\\Type\\UnionTypeNode::class, \\PHPStan\\PhpDocParser\\Ast\\Type\\IntersectionTypeNode::class], \\PHPStan\\PhpDocParser\\Ast\\Type\\ArrayTypeNode::class . \'->type\' => [\\PHPStan\\PhpDocParser\\Ast\\Type\\CallableTypeNode::class, \\PHPStan\\PhpDocParser\\Ast\\Type\\UnionTypeNode::class, \\PHPStan\\PhpDocParser\\Ast\\Type\\IntersectionTypeNode::class, \\PHPStan\\PhpDocParser\\Ast\\Type\\ConstTypeNode::class, \\PHPStan\\PhpDocParser\\Ast\\Type\\NullableTypeNode::class], \\PHPStan\\PhpDocParser\\Ast\\Type\\OffsetAccessTypeNode::class . \'->type\' => [\\PHPStan\\PhpDocParser\\Ast\\Type\\CallableTypeNode::class, \\PHPStan\\PhpDocParser\\Ast\\Type\\UnionTypeNode::class, \\PHPStan\\PhpDocParser\\Ast\\Type\\IntersectionTypeNode::class, \\PHPStan\\PhpDocParser\\Ast\\Type\\NullableTypeNode::class]]',
          'attributes' => 
          array (
            'startLine' => 133,
            'endLine' => 152,
            'startTokenPos' => 768,
            'startFilePos' => 5582,
            'endTokenPos' => 875,
            'endFilePos' => 6069,
          ),
        ),
        'docComment' => '/** @var array<string, list<class-string<TypeNode>>> */',
        'attributes' => 
        array (
        ),
        'startLine' => 133,
        'endLine' => 152,
        'startColumn' => 2,
        'endColumn' => 3,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'parenthesesListMap' => 
      array (
        'declaringClassName' => 'PHPStan\\PhpDocParser\\Printer\\Printer',
        'implementingClassName' => 'PHPStan\\PhpDocParser\\Printer\\Printer',
        'name' => 'parenthesesListMap',
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
        'default' => 
        array (
          'code' => '[\\PHPStan\\PhpDocParser\\Ast\\Type\\IntersectionTypeNode::class . \'->types\' => [\\PHPStan\\PhpDocParser\\Ast\\Type\\IntersectionTypeNode::class, \\PHPStan\\PhpDocParser\\Ast\\Type\\UnionTypeNode::class, \\PHPStan\\PhpDocParser\\Ast\\Type\\NullableTypeNode::class], \\PHPStan\\PhpDocParser\\Ast\\Type\\UnionTypeNode::class . \'->types\' => [\\PHPStan\\PhpDocParser\\Ast\\Type\\IntersectionTypeNode::class, \\PHPStan\\PhpDocParser\\Ast\\Type\\UnionTypeNode::class, \\PHPStan\\PhpDocParser\\Ast\\Type\\NullableTypeNode::class]]',
          'attributes' => 
          array (
            'startLine' => 155,
            'endLine' => 166,
            'startTokenPos' => 888,
            'startFilePos' => 6167,
            'endTokenPos' => 950,
            'endFilePos' => 6437,
          ),
        ),
        'docComment' => '/** @var array<string, list<class-string<TypeNode>>> */',
        'attributes' => 
        array (
        ),
        'startLine' => 155,
        'endLine' => 166,
        'startColumn' => 2,
        'endColumn' => 3,
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
      'printFormatPreserving' => 
      array (
        'name' => 'printFormatPreserving',
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
            'startLine' => 168,
            'endLine' => 168,
            'startColumn' => 40,
            'endColumn' => 55,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'originalNode' => 
          array (
            'name' => 'originalNode',
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
            'startLine' => 168,
            'endLine' => 168,
            'startColumn' => 58,
            'endColumn' => 81,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'originalTokens' => 
          array (
            'name' => 'originalTokens',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\PhpDocParser\\Parser\\TokenIterator',
                'isIdentifier' => false,
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
            'startColumn' => 84,
            'endColumn' => 112,
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
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 168,
        'endLine' => 192,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\PhpDocParser\\Printer',
        'declaringClassName' => 'PHPStan\\PhpDocParser\\Printer\\Printer',
        'implementingClassName' => 'PHPStan\\PhpDocParser\\Printer\\Printer',
        'currentClassName' => 'PHPStan\\PhpDocParser\\Printer\\Printer',
        'aliasName' => NULL,
      ),
      'print' => 
      array (
        'name' => 'print',
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
                'name' => 'PHPStan\\PhpDocParser\\Ast\\Node',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 194,
            'endLine' => 194,
            'startColumn' => 24,
            'endColumn' => 33,
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
        'startLine' => 194,
        'endLine' => 282,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\PhpDocParser\\Printer',
        'declaringClassName' => 'PHPStan\\PhpDocParser\\Printer\\Printer',
        'implementingClassName' => 'PHPStan\\PhpDocParser\\Printer\\Printer',
        'currentClassName' => 'PHPStan\\PhpDocParser\\Printer\\Printer',
        'aliasName' => NULL,
      ),
      'printTagValue' => 
      array (
        'name' => 'printTagValue',
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
                'name' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\PhpDocTagValueNode',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 284,
            'endLine' => 284,
            'startColumn' => 33,
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
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 284,
        'endLine' => 399,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'PHPStan\\PhpDocParser\\Printer',
        'declaringClassName' => 'PHPStan\\PhpDocParser\\Printer\\Printer',
        'implementingClassName' => 'PHPStan\\PhpDocParser\\Printer\\Printer',
        'currentClassName' => 'PHPStan\\PhpDocParser\\Printer\\Printer',
        'aliasName' => NULL,
      ),
      'printType' => 
      array (
        'name' => 'printType',
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
                'name' => 'PHPStan\\PhpDocParser\\Ast\\Type\\TypeNode',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 401,
            'endLine' => 401,
            'startColumn' => 29,
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
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 401,
        'endLine' => 509,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'PHPStan\\PhpDocParser\\Printer',
        'declaringClassName' => 'PHPStan\\PhpDocParser\\Printer\\Printer',
        'implementingClassName' => 'PHPStan\\PhpDocParser\\Printer\\Printer',
        'currentClassName' => 'PHPStan\\PhpDocParser\\Printer\\Printer',
        'aliasName' => NULL,
      ),
      'wrapInParentheses' => 
      array (
        'name' => 'wrapInParentheses',
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
                'name' => 'PHPStan\\PhpDocParser\\Ast\\Type\\TypeNode',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 511,
            'endLine' => 511,
            'startColumn' => 37,
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
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 511,
        'endLine' => 514,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'PHPStan\\PhpDocParser\\Printer',
        'declaringClassName' => 'PHPStan\\PhpDocParser\\Printer\\Printer',
        'implementingClassName' => 'PHPStan\\PhpDocParser\\Printer\\Printer',
        'currentClassName' => 'PHPStan\\PhpDocParser\\Printer\\Printer',
        'aliasName' => NULL,
      ),
      'printOffsetAccessType' => 
      array (
        'name' => 'printOffsetAccessType',
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
                'name' => 'PHPStan\\PhpDocParser\\Ast\\Type\\TypeNode',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 516,
            'endLine' => 516,
            'startColumn' => 41,
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
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 516,
        'endLine' => 528,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'PHPStan\\PhpDocParser\\Printer',
        'declaringClassName' => 'PHPStan\\PhpDocParser\\Printer\\Printer',
        'implementingClassName' => 'PHPStan\\PhpDocParser\\Printer\\Printer',
        'currentClassName' => 'PHPStan\\PhpDocParser\\Printer\\Printer',
        'aliasName' => NULL,
      ),
      'printConstExpr' => 
      array (
        'name' => 'printConstExpr',
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
                'name' => 'PHPStan\\PhpDocParser\\Ast\\ConstExpr\\ConstExprNode',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 530,
            'endLine' => 530,
            'startColumn' => 34,
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
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 530,
        'endLine' => 534,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'PHPStan\\PhpDocParser\\Printer',
        'declaringClassName' => 'PHPStan\\PhpDocParser\\Printer\\Printer',
        'implementingClassName' => 'PHPStan\\PhpDocParser\\Printer\\Printer',
        'currentClassName' => 'PHPStan\\PhpDocParser\\Printer\\Printer',
        'aliasName' => NULL,
      ),
      'printArrayFormatPreserving' => 
      array (
        'name' => 'printArrayFormatPreserving',
        'parameters' => 
        array (
          'nodes' => 
          array (
            'name' => 'nodes',
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
            'startLine' => 540,
            'endLine' => 540,
            'startColumn' => 46,
            'endColumn' => 57,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'originalNodes' => 
          array (
            'name' => 'originalNodes',
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
            'startLine' => 540,
            'endLine' => 540,
            'startColumn' => 60,
            'endColumn' => 79,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'originalTokens' => 
          array (
            'name' => 'originalTokens',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\PhpDocParser\\Parser\\TokenIterator',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 540,
            'endLine' => 540,
            'startColumn' => 82,
            'endColumn' => 110,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'tokenIndex' => 
          array (
            'name' => 'tokenIndex',
            'default' => NULL,
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
            'byRef' => true,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 540,
            'endLine' => 540,
            'startColumn' => 113,
            'endColumn' => 128,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
          'parentNodeClass' => 
          array (
            'name' => 'parentNodeClass',
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
            'startLine' => 540,
            'endLine' => 540,
            'startColumn' => 131,
            'endColumn' => 153,
            'parameterIndex' => 4,
            'isOptional' => false,
          ),
          'subNodeName' => 
          array (
            'name' => 'subNodeName',
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
            'startLine' => 540,
            'endLine' => 540,
            'startColumn' => 156,
            'endColumn' => 174,
            'parameterIndex' => 5,
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
        'docComment' => '/**
 * @param Node[] $nodes
 * @param Node[] $originalNodes
 */',
        'startLine' => 540,
        'endLine' => 747,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'PHPStan\\PhpDocParser\\Printer',
        'declaringClassName' => 'PHPStan\\PhpDocParser\\Printer\\Printer',
        'implementingClassName' => 'PHPStan\\PhpDocParser\\Printer\\Printer',
        'currentClassName' => 'PHPStan\\PhpDocParser\\Printer\\Printer',
        'aliasName' => NULL,
      ),
      'printComments' => 
      array (
        'name' => 'printComments',
        'parameters' => 
        array (
          'comments' => 
          array (
            'name' => 'comments',
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
            'startLine' => 752,
            'endLine' => 752,
            'startColumn' => 33,
            'endColumn' => 47,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'beforeAsteriskIndent' => 
          array (
            'name' => 'beforeAsteriskIndent',
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
            'startLine' => 752,
            'endLine' => 752,
            'startColumn' => 50,
            'endColumn' => 77,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'afterAsteriskIndent' => 
          array (
            'name' => 'afterAsteriskIndent',
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
            'startLine' => 752,
            'endLine' => 752,
            'startColumn' => 80,
            'endColumn' => 106,
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
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @param list<Comment> $comments
 */',
        'startLine' => 752,
        'endLine' => 761,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'PHPStan\\PhpDocParser\\Printer',
        'declaringClassName' => 'PHPStan\\PhpDocParser\\Printer\\Printer',
        'implementingClassName' => 'PHPStan\\PhpDocParser\\Printer\\Printer',
        'currentClassName' => 'PHPStan\\PhpDocParser\\Printer\\Printer',
        'aliasName' => NULL,
      ),
      'isMultiline' => 
      array (
        'name' => 'isMultiline',
        'parameters' => 
        array (
          'initialIndex' => 
          array (
            'name' => 'initialIndex',
            'default' => NULL,
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
            'startLine' => 767,
            'endLine' => 767,
            'startColumn' => 31,
            'endColumn' => 47,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'nodes' => 
          array (
            'name' => 'nodes',
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
            'startLine' => 767,
            'endLine' => 767,
            'startColumn' => 50,
            'endColumn' => 61,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'originalTokens' => 
          array (
            'name' => 'originalTokens',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\PhpDocParser\\Parser\\TokenIterator',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 767,
            'endLine' => 767,
            'startColumn' => 64,
            'endColumn' => 92,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @param array<Node|null> $nodes
 * @return array{bool, string, string}
 */',
        'startLine' => 767,
        'endLine' => 812,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'PHPStan\\PhpDocParser\\Printer',
        'declaringClassName' => 'PHPStan\\PhpDocParser\\Printer\\Printer',
        'implementingClassName' => 'PHPStan\\PhpDocParser\\Printer\\Printer',
        'currentClassName' => 'PHPStan\\PhpDocParser\\Printer\\Printer',
        'aliasName' => NULL,
      ),
      'printNodeFormatPreserving' => 
      array (
        'name' => 'printNodeFormatPreserving',
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
                'name' => 'PHPStan\\PhpDocParser\\Ast\\Node',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 814,
            'endLine' => 814,
            'startColumn' => 45,
            'endColumn' => 54,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'originalTokens' => 
          array (
            'name' => 'originalTokens',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\PhpDocParser\\Parser\\TokenIterator',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 814,
            'endLine' => 814,
            'startColumn' => 57,
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
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 814,
        'endLine' => 918,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'PHPStan\\PhpDocParser\\Printer',
        'declaringClassName' => 'PHPStan\\PhpDocParser\\Printer\\Printer',
        'implementingClassName' => 'PHPStan\\PhpDocParser\\Printer\\Printer',
        'currentClassName' => 'PHPStan\\PhpDocParser\\Printer\\Printer',
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