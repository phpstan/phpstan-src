<?php declare(strict_types = 1);

// osfsl-/home/runner/work/phpstan-src/phpstan-src/vendor/composer/../phpstan/phpdoc-parser/src/Ast/PhpDoc/Doctrine/DoctrineArgument.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\PhpDocParser\Ast\PhpDoc\Doctrine\DoctrineArgument
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-bb170e4f8a27736c15a8c2535837d13560df99962d86c12b41ec04a871c576d8-8.4.21-6.70.0.1',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\Doctrine\\DoctrineArgument',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/vendor/composer/../phpstan/phpdoc-parser/src/Ast/PhpDoc/Doctrine/DoctrineArgument.php',
      ),
    ),
    'namespace' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\Doctrine',
    'name' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\Doctrine\\DoctrineArgument',
    'shortName' => 'DoctrineArgument',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * @phpstan-type ValueType = DoctrineAnnotation|IdentifierTypeNode|DoctrineArray|ConstExprNode
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 13,
    'endLine' => 55,
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
      'key' => 
      array (
        'declaringClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\Doctrine\\DoctrineArgument',
        'implementingClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\Doctrine\\DoctrineArgument',
        'name' => 'key',
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
                  'name' => 'PHPStan\\PhpDocParser\\Ast\\Type\\IdentifierTypeNode',
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
            'startLine' => 18,
            'endLine' => 18,
            'startTokenPos' => 62,
            'startFilePos' => 478,
            'endTokenPos' => 62,
            'endFilePos' => 481,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 18,
        'endLine' => 18,
        'startColumn' => 2,
        'endColumn' => 40,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'value' => 
      array (
        'declaringClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\Doctrine\\DoctrineArgument',
        'implementingClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\Doctrine\\DoctrineArgument',
        'name' => 'value',
        'modifiers' => 1,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/** @var ValueType */',
        'attributes' => 
        array (
        ),
        'startLine' => 21,
        'endLine' => 21,
        'startColumn' => 2,
        'endColumn' => 15,
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
          'key' => 
          array (
            'name' => 'key',
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
                      'name' => 'PHPStan\\PhpDocParser\\Ast\\Type\\IdentifierTypeNode',
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
            'startLine' => 26,
            'endLine' => 26,
            'startColumn' => 30,
            'endColumn' => 53,
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
            'startLine' => 26,
            'endLine' => 26,
            'startColumn' => 56,
            'endColumn' => 61,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @param ValueType $value
 */',
        'startLine' => 26,
        'endLine' => 30,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\Doctrine',
        'declaringClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\Doctrine\\DoctrineArgument',
        'implementingClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\Doctrine\\DoctrineArgument',
        'currentClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\Doctrine\\DoctrineArgument',
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
        'startLine' => 32,
        'endLine' => 39,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\Doctrine',
        'declaringClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\Doctrine\\DoctrineArgument',
        'implementingClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\Doctrine\\DoctrineArgument',
        'currentClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\Doctrine\\DoctrineArgument',
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
            'startLine' => 44,
            'endLine' => 44,
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
        'startLine' => 44,
        'endLine' => 53,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\Doctrine',
        'declaringClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\Doctrine\\DoctrineArgument',
        'implementingClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\Doctrine\\DoctrineArgument',
        'currentClassName' => 'PHPStan\\PhpDocParser\\Ast\\PhpDoc\\Doctrine\\DoctrineArgument',
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