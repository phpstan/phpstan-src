<?php declare(strict_types = 1);

// osfsl-/home/runner/work/phpstan-src/phpstan-src/vendor/composer/../phpstan/phpdoc-parser/src/Ast/Type/ArrayShapeUnsealedTypeNode.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\PhpDocParser\Ast\Type\ArrayShapeUnsealedTypeNode
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-dfb233fff42f977a6659d6dabb0f22d36a2ce70f6d39124e1f6facaa4079727b-8.4.21-6.70.0.1',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\PhpDocParser\\Ast\\Type\\ArrayShapeUnsealedTypeNode',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/vendor/composer/../phpstan/phpdoc-parser/src/Ast/Type/ArrayShapeUnsealedTypeNode.php',
      ),
    ),
    'namespace' => 'PHPStan\\PhpDocParser\\Ast\\Type',
    'name' => 'PHPStan\\PhpDocParser\\Ast\\Type\\ArrayShapeUnsealedTypeNode',
    'shortName' => 'ArrayShapeUnsealedTypeNode',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => NULL,
    'attributes' => 
    array (
    ),
    'startLine' => 9,
    'endLine' => 46,
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
      'valueType' => 
      array (
        'declaringClassName' => 'PHPStan\\PhpDocParser\\Ast\\Type\\ArrayShapeUnsealedTypeNode',
        'implementingClassName' => 'PHPStan\\PhpDocParser\\Ast\\Type\\ArrayShapeUnsealedTypeNode',
        'name' => 'valueType',
        'modifiers' => 1,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPStan\\PhpDocParser\\Ast\\Type\\TypeNode',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 14,
        'endLine' => 14,
        'startColumn' => 2,
        'endColumn' => 28,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'keyType' => 
      array (
        'declaringClassName' => 'PHPStan\\PhpDocParser\\Ast\\Type\\ArrayShapeUnsealedTypeNode',
        'implementingClassName' => 'PHPStan\\PhpDocParser\\Ast\\Type\\ArrayShapeUnsealedTypeNode',
        'name' => 'keyType',
        'modifiers' => 1,
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
                  'name' => 'PHPStan\\PhpDocParser\\Ast\\Type\\TypeNode',
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
        'default' => 
        array (
          'code' => 'null',
          'attributes' => 
          array (
            'startLine' => 16,
            'endLine' => 16,
            'startTokenPos' => 64,
            'startFilePos' => 312,
            'endTokenPos' => 64,
            'endFilePos' => 315,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 16,
        'endLine' => 16,
        'startColumn' => 2,
        'endColumn' => 34,
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
          'valueType' => 
          array (
            'name' => 'valueType',
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
            'startLine' => 18,
            'endLine' => 18,
            'startColumn' => 30,
            'endColumn' => 48,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'keyType' => 
          array (
            'name' => 'keyType',
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
                      'name' => 'PHPStan\\PhpDocParser\\Ast\\Type\\TypeNode',
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
            'startLine' => 18,
            'endLine' => 18,
            'startColumn' => 51,
            'endColumn' => 68,
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
        'startLine' => 18,
        'endLine' => 22,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\PhpDocParser\\Ast\\Type',
        'declaringClassName' => 'PHPStan\\PhpDocParser\\Ast\\Type\\ArrayShapeUnsealedTypeNode',
        'implementingClassName' => 'PHPStan\\PhpDocParser\\Ast\\Type\\ArrayShapeUnsealedTypeNode',
        'currentClassName' => 'PHPStan\\PhpDocParser\\Ast\\Type\\ArrayShapeUnsealedTypeNode',
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
        'startLine' => 24,
        'endLine' => 30,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\PhpDocParser\\Ast\\Type',
        'declaringClassName' => 'PHPStan\\PhpDocParser\\Ast\\Type\\ArrayShapeUnsealedTypeNode',
        'implementingClassName' => 'PHPStan\\PhpDocParser\\Ast\\Type\\ArrayShapeUnsealedTypeNode',
        'currentClassName' => 'PHPStan\\PhpDocParser\\Ast\\Type\\ArrayShapeUnsealedTypeNode',
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
            'startLine' => 35,
            'endLine' => 35,
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
        'startLine' => 35,
        'endLine' => 44,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'PHPStan\\PhpDocParser\\Ast\\Type',
        'declaringClassName' => 'PHPStan\\PhpDocParser\\Ast\\Type\\ArrayShapeUnsealedTypeNode',
        'implementingClassName' => 'PHPStan\\PhpDocParser\\Ast\\Type\\ArrayShapeUnsealedTypeNode',
        'currentClassName' => 'PHPStan\\PhpDocParser\\Ast\\Type\\ArrayShapeUnsealedTypeNode',
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