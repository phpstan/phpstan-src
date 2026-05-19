<?php declare(strict_types = 1);

// osfsl-/home/runner/work/phpstan-src/phpstan-src/vendor/composer/../hoa/compiler/./Llk/TreeNode.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Hoa\Compiler\Llk\TreeNode
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-8d284f54697b0264e0ae1f2662f935a4bb25907a133be8130827b08b80d926e1-8.4.21-6.70.0.1',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Hoa\\Compiler\\Llk\\TreeNode',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/vendor/composer/../hoa/compiler/./Llk/TreeNode.php',
      ),
    ),
    'namespace' => 'Hoa\\Compiler\\Llk',
    'name' => 'Hoa\\Compiler\\Llk\\TreeNode',
    'shortName' => 'TreeNode',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Class \\Hoa\\Compiler\\Llk\\TreeNode.
 *
 * Provide a generic node for the AST produced by LL(k) parser.
 *
 * @copyright  Copyright © 2007-2017 Hoa community
 * @license    New BSD License
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 49,
    'endLine' => 347,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
      0 => 'Hoa\\Visitor\\Element',
    ),
    'traitClassNames' => 
    array (
    ),
    'immediateConstants' => 
    array (
    ),
    'immediateProperties' => 
    array (
      '_id' => 
      array (
        'declaringClassName' => 'Hoa\\Compiler\\Llk\\TreeNode',
        'implementingClassName' => 'Hoa\\Compiler\\Llk\\TreeNode',
        'name' => '_id',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => 'null',
          'attributes' => 
          array (
            'startLine' => 56,
            'endLine' => 56,
            'startTokenPos' => 34,
            'startFilePos' => 2065,
            'endTokenPos' => 34,
            'endFilePos' => 2068,
          ),
        ),
        'docComment' => '/**
 * ID (should be something like #ruleName or token).
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 56,
        'endLine' => 56,
        'startColumn' => 5,
        'endColumn' => 32,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      '_value' => 
      array (
        'declaringClassName' => 'Hoa\\Compiler\\Llk\\TreeNode',
        'implementingClassName' => 'Hoa\\Compiler\\Llk\\TreeNode',
        'name' => '_value',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => 'null',
          'attributes' => 
          array (
            'startLine' => 63,
            'endLine' => 63,
            'startTokenPos' => 45,
            'startFilePos' => 2193,
            'endTokenPos' => 45,
            'endFilePos' => 2196,
          ),
        ),
        'docComment' => '/**
 * Value of the node (non-null for token nodes).
 *
 * @var array
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 63,
        'endLine' => 63,
        'startColumn' => 5,
        'endColumn' => 32,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      '_children' => 
      array (
        'declaringClassName' => 'Hoa\\Compiler\\Llk\\TreeNode',
        'implementingClassName' => 'Hoa\\Compiler\\Llk\\TreeNode',
        'name' => '_children',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => 'null',
          'attributes' => 
          array (
            'startLine' => 70,
            'endLine' => 70,
            'startTokenPos' => 56,
            'startFilePos' => 2285,
            'endTokenPos' => 56,
            'endFilePos' => 2288,
          ),
        ),
        'docComment' => '/**
 * Children.
 *
 * @var array
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 70,
        'endLine' => 70,
        'startColumn' => 5,
        'endColumn' => 32,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      '_parent' => 
      array (
        'declaringClassName' => 'Hoa\\Compiler\\Llk\\TreeNode',
        'implementingClassName' => 'Hoa\\Compiler\\Llk\\TreeNode',
        'name' => '_parent',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => 'null',
          'attributes' => 
          array (
            'startLine' => 77,
            'endLine' => 77,
            'startTokenPos' => 67,
            'startFilePos' => 2396,
            'endTokenPos' => 67,
            'endFilePos' => 2399,
          ),
        ),
        'docComment' => '/**
 * Parent.
 *
 * @var \\Hoa\\Compiler\\Llk\\TreeNode
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 77,
        'endLine' => 77,
        'startColumn' => 5,
        'endColumn' => 32,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      '_data' => 
      array (
        'declaringClassName' => 'Hoa\\Compiler\\Llk\\TreeNode',
        'implementingClassName' => 'Hoa\\Compiler\\Llk\\TreeNode',
        'name' => '_data',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[]',
          'attributes' => 
          array (
            'startLine' => 84,
            'endLine' => 84,
            'startTokenPos' => 78,
            'startFilePos' => 2493,
            'endTokenPos' => 79,
            'endFilePos' => 2494,
          ),
        ),
        'docComment' => '/**
 * Attached data.
 *
 * @var array
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 84,
        'endLine' => 84,
        'startColumn' => 5,
        'endColumn' => 30,
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
          'id' => 
          array (
            'name' => 'id',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 97,
            'endLine' => 97,
            'startColumn' => 9,
            'endColumn' => 11,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'value' => 
          array (
            'name' => 'value',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 98,
                'endLine' => 98,
                'startTokenPos' => 101,
                'startFilePos' => 2871,
                'endTokenPos' => 101,
                'endFilePos' => 2874,
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
            'startLine' => 98,
            'endLine' => 98,
            'startColumn' => 9,
            'endColumn' => 31,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
          'children' => 
          array (
            'name' => 'children',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 99,
                'endLine' => 99,
                'startTokenPos' => 110,
                'startFilePos' => 2903,
                'endTokenPos' => 111,
                'endFilePos' => 2904,
              ),
            ),
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
            'startLine' => 99,
            'endLine' => 99,
            'startColumn' => 9,
            'endColumn' => 28,
            'parameterIndex' => 2,
            'isOptional' => true,
          ),
          'parent' => 
          array (
            'name' => 'parent',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 100,
                'endLine' => 100,
                'startTokenPos' => 121,
                'startFilePos' => 2934,
                'endTokenPos' => 121,
                'endFilePos' => 2937,
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
                      'name' => 'self',
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
            'startLine' => 100,
            'endLine' => 100,
            'startColumn' => 9,
            'endColumn' => 31,
            'parameterIndex' => 3,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Constructor.
 *
 * @param   string                      $id          ID.
 * @param   array                       $value       Value.
 * @param   array                       $children    Children.
 * @param   \\Hoa\\Compiler\\Llk\\TreeNode  $parent    Parent.
 */',
        'startLine' => 96,
        'endLine' => 115,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Hoa\\Compiler\\Llk',
        'declaringClassName' => 'Hoa\\Compiler\\Llk\\TreeNode',
        'implementingClassName' => 'Hoa\\Compiler\\Llk\\TreeNode',
        'currentClassName' => 'Hoa\\Compiler\\Llk\\TreeNode',
        'aliasName' => NULL,
      ),
      'setId' => 
      array (
        'name' => 'setId',
        'parameters' => 
        array (
          'id' => 
          array (
            'name' => 'id',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 123,
            'endLine' => 123,
            'startColumn' => 27,
            'endColumn' => 29,
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
 * Set ID.
 *
 * @param   string  $id    ID.
 * @return  string
 */',
        'startLine' => 123,
        'endLine' => 129,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Hoa\\Compiler\\Llk',
        'declaringClassName' => 'Hoa\\Compiler\\Llk\\TreeNode',
        'implementingClassName' => 'Hoa\\Compiler\\Llk\\TreeNode',
        'currentClassName' => 'Hoa\\Compiler\\Llk\\TreeNode',
        'aliasName' => NULL,
      ),
      'getId' => 
      array (
        'name' => 'getId',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get ID.
 *
 * @return  string
 */',
        'startLine' => 136,
        'endLine' => 139,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Hoa\\Compiler\\Llk',
        'declaringClassName' => 'Hoa\\Compiler\\Llk\\TreeNode',
        'implementingClassName' => 'Hoa\\Compiler\\Llk\\TreeNode',
        'currentClassName' => 'Hoa\\Compiler\\Llk\\TreeNode',
        'aliasName' => NULL,
      ),
      'setValue' => 
      array (
        'name' => 'setValue',
        'parameters' => 
        array (
          'value' => 
          array (
            'name' => 'value',
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
            'startLine' => 147,
            'endLine' => 147,
            'startColumn' => 30,
            'endColumn' => 41,
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
 * Set value.
 *
 * @param   array  $value    Value (token & value).
 * @return  array
 */',
        'startLine' => 147,
        'endLine' => 153,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Hoa\\Compiler\\Llk',
        'declaringClassName' => 'Hoa\\Compiler\\Llk\\TreeNode',
        'implementingClassName' => 'Hoa\\Compiler\\Llk\\TreeNode',
        'currentClassName' => 'Hoa\\Compiler\\Llk\\TreeNode',
        'aliasName' => NULL,
      ),
      'getValue' => 
      array (
        'name' => 'getValue',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get value.
 *
 * @return  array
 */',
        'startLine' => 160,
        'endLine' => 163,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Hoa\\Compiler\\Llk',
        'declaringClassName' => 'Hoa\\Compiler\\Llk\\TreeNode',
        'implementingClassName' => 'Hoa\\Compiler\\Llk\\TreeNode',
        'currentClassName' => 'Hoa\\Compiler\\Llk\\TreeNode',
        'aliasName' => NULL,
      ),
      'getValueToken' => 
      array (
        'name' => 'getValueToken',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get value token.
 *
 * @return  string
 */',
        'startLine' => 170,
        'endLine' => 176,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Hoa\\Compiler\\Llk',
        'declaringClassName' => 'Hoa\\Compiler\\Llk\\TreeNode',
        'implementingClassName' => 'Hoa\\Compiler\\Llk\\TreeNode',
        'currentClassName' => 'Hoa\\Compiler\\Llk\\TreeNode',
        'aliasName' => NULL,
      ),
      'getValueValue' => 
      array (
        'name' => 'getValueValue',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get value value.
 *
 * @return  string
 */',
        'startLine' => 183,
        'endLine' => 189,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Hoa\\Compiler\\Llk',
        'declaringClassName' => 'Hoa\\Compiler\\Llk\\TreeNode',
        'implementingClassName' => 'Hoa\\Compiler\\Llk\\TreeNode',
        'currentClassName' => 'Hoa\\Compiler\\Llk\\TreeNode',
        'aliasName' => NULL,
      ),
      'isToken' => 
      array (
        'name' => 'isToken',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Check if the node represents a token or not.
 *
 * @return  bool
 */',
        'startLine' => 196,
        'endLine' => 199,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Hoa\\Compiler\\Llk',
        'declaringClassName' => 'Hoa\\Compiler\\Llk\\TreeNode',
        'implementingClassName' => 'Hoa\\Compiler\\Llk\\TreeNode',
        'currentClassName' => 'Hoa\\Compiler\\Llk\\TreeNode',
        'aliasName' => NULL,
      ),
      'prependChild' => 
      array (
        'name' => 'prependChild',
        'parameters' => 
        array (
          'child' => 
          array (
            'name' => 'child',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Hoa\\Compiler\\Llk\\TreeNode',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 207,
            'endLine' => 207,
            'startColumn' => 34,
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
        'docComment' => '/**
 * Prepend a child.
 *
 * @param   \\Hoa\\Compiler\\Llk\\TreeNode  $child    Child.
 * @return  \\Hoa\\Compiler\\Llk\\TreeNode
 */',
        'startLine' => 207,
        'endLine' => 212,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Hoa\\Compiler\\Llk',
        'declaringClassName' => 'Hoa\\Compiler\\Llk\\TreeNode',
        'implementingClassName' => 'Hoa\\Compiler\\Llk\\TreeNode',
        'currentClassName' => 'Hoa\\Compiler\\Llk\\TreeNode',
        'aliasName' => NULL,
      ),
      'appendChild' => 
      array (
        'name' => 'appendChild',
        'parameters' => 
        array (
          'child' => 
          array (
            'name' => 'child',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Hoa\\Compiler\\Llk\\TreeNode',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 220,
            'endLine' => 220,
            'startColumn' => 33,
            'endColumn' => 47,
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
 * Append a child.
 *
 * @param   \\Hoa\\Compiler\\Llk\\TreeNode  $child    Child.
 * @return  \\Hoa\\Compiler\\Llk\\TreeNode
 */',
        'startLine' => 220,
        'endLine' => 225,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Hoa\\Compiler\\Llk',
        'declaringClassName' => 'Hoa\\Compiler\\Llk\\TreeNode',
        'implementingClassName' => 'Hoa\\Compiler\\Llk\\TreeNode',
        'currentClassName' => 'Hoa\\Compiler\\Llk\\TreeNode',
        'aliasName' => NULL,
      ),
      'setChildren' => 
      array (
        'name' => 'setChildren',
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
            'startLine' => 233,
            'endLine' => 233,
            'startColumn' => 33,
            'endColumn' => 47,
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
 * Set children.
 *
 * @param   array  $children    Children.
 * @return  array
 */',
        'startLine' => 233,
        'endLine' => 239,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Hoa\\Compiler\\Llk',
        'declaringClassName' => 'Hoa\\Compiler\\Llk\\TreeNode',
        'implementingClassName' => 'Hoa\\Compiler\\Llk\\TreeNode',
        'currentClassName' => 'Hoa\\Compiler\\Llk\\TreeNode',
        'aliasName' => NULL,
      ),
      'getChild' => 
      array (
        'name' => 'getChild',
        'parameters' => 
        array (
          'i' => 
          array (
            'name' => 'i',
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
            'startColumn' => 30,
            'endColumn' => 31,
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
 * Get child.
 *
 * @param   int  $i    Index.
 * @return  \\Hoa\\Compiler\\Llk\\TreeNode
 */',
        'startLine' => 247,
        'endLine' => 253,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Hoa\\Compiler\\Llk',
        'declaringClassName' => 'Hoa\\Compiler\\Llk\\TreeNode',
        'implementingClassName' => 'Hoa\\Compiler\\Llk\\TreeNode',
        'currentClassName' => 'Hoa\\Compiler\\Llk\\TreeNode',
        'aliasName' => NULL,
      ),
      'getChildren' => 
      array (
        'name' => 'getChildren',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get children.
 *
 * @return  array
 */',
        'startLine' => 260,
        'endLine' => 263,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Hoa\\Compiler\\Llk',
        'declaringClassName' => 'Hoa\\Compiler\\Llk\\TreeNode',
        'implementingClassName' => 'Hoa\\Compiler\\Llk\\TreeNode',
        'currentClassName' => 'Hoa\\Compiler\\Llk\\TreeNode',
        'aliasName' => NULL,
      ),
      'getChildrenNumber' => 
      array (
        'name' => 'getChildrenNumber',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get number of children.
 *
 * @return  int
 */',
        'startLine' => 270,
        'endLine' => 273,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Hoa\\Compiler\\Llk',
        'declaringClassName' => 'Hoa\\Compiler\\Llk\\TreeNode',
        'implementingClassName' => 'Hoa\\Compiler\\Llk\\TreeNode',
        'currentClassName' => 'Hoa\\Compiler\\Llk\\TreeNode',
        'aliasName' => NULL,
      ),
      'childExists' => 
      array (
        'name' => 'childExists',
        'parameters' => 
        array (
          'i' => 
          array (
            'name' => 'i',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 281,
            'endLine' => 281,
            'startColumn' => 33,
            'endColumn' => 34,
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
 * Check if a child exists.
 *
 * @param   int  $i    Index.
 * @return  bool
 */',
        'startLine' => 281,
        'endLine' => 284,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Hoa\\Compiler\\Llk',
        'declaringClassName' => 'Hoa\\Compiler\\Llk\\TreeNode',
        'implementingClassName' => 'Hoa\\Compiler\\Llk\\TreeNode',
        'currentClassName' => 'Hoa\\Compiler\\Llk\\TreeNode',
        'aliasName' => NULL,
      ),
      'setParent' => 
      array (
        'name' => 'setParent',
        'parameters' => 
        array (
          'parent' => 
          array (
            'name' => 'parent',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Hoa\\Compiler\\Llk\\TreeNode',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 292,
            'endLine' => 292,
            'startColumn' => 31,
            'endColumn' => 46,
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
 * Set parent.
 *
 * @param   \\Hoa\\Compiler\\Llk\\TreeNode  $parent    Parent.
 * @return  \\Hoa\\Compiler\\Llk\\TreeNode
 */',
        'startLine' => 292,
        'endLine' => 298,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Hoa\\Compiler\\Llk',
        'declaringClassName' => 'Hoa\\Compiler\\Llk\\TreeNode',
        'implementingClassName' => 'Hoa\\Compiler\\Llk\\TreeNode',
        'currentClassName' => 'Hoa\\Compiler\\Llk\\TreeNode',
        'aliasName' => NULL,
      ),
      'getParent' => 
      array (
        'name' => 'getParent',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get parent.
 *
 * @return  \\Hoa\\Compiler\\Llk\\TreeNode
 */',
        'startLine' => 305,
        'endLine' => 308,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Hoa\\Compiler\\Llk',
        'declaringClassName' => 'Hoa\\Compiler\\Llk\\TreeNode',
        'implementingClassName' => 'Hoa\\Compiler\\Llk\\TreeNode',
        'currentClassName' => 'Hoa\\Compiler\\Llk\\TreeNode',
        'aliasName' => NULL,
      ),
      'getData' => 
      array (
        'name' => 'getData',
        'parameters' => 
        array (
        ),
        'returnsReference' => true,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get data.
 *
 * @return  array
 */',
        'startLine' => 315,
        'endLine' => 318,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Hoa\\Compiler\\Llk',
        'declaringClassName' => 'Hoa\\Compiler\\Llk\\TreeNode',
        'implementingClassName' => 'Hoa\\Compiler\\Llk\\TreeNode',
        'currentClassName' => 'Hoa\\Compiler\\Llk\\TreeNode',
        'aliasName' => NULL,
      ),
      'accept' => 
      array (
        'name' => 'accept',
        'parameters' => 
        array (
          'visitor' => 
          array (
            'name' => 'visitor',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Hoa\\Visitor\\Visit',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 329,
            'endLine' => 329,
            'startColumn' => 9,
            'endColumn' => 30,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'handle' => 
          array (
            'name' => 'handle',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 330,
                'endLine' => 330,
                'startTokenPos' => 740,
                'startFilePos' => 7209,
                'endTokenPos' => 740,
                'endFilePos' => 7212,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => true,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 330,
            'endLine' => 330,
            'startColumn' => 9,
            'endColumn' => 23,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
          'eldnah' => 
          array (
            'name' => 'eldnah',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 331,
                'endLine' => 331,
                'startTokenPos' => 747,
                'startFilePos' => 7234,
                'endTokenPos' => 747,
                'endFilePos' => 7237,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 331,
            'endLine' => 331,
            'startColumn' => 9,
            'endColumn' => 23,
            'parameterIndex' => 2,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Accept a visitor.
 *
 * @param   \\Hoa\\Visitor\\Visit  $visitor    Visitor.
 * @param   mixed               &$handle    Handle (reference).
 * @param   mixed               $eldnah     Handle (no reference).
 * @return  mixed
 */',
        'startLine' => 328,
        'endLine' => 334,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Hoa\\Compiler\\Llk',
        'declaringClassName' => 'Hoa\\Compiler\\Llk\\TreeNode',
        'implementingClassName' => 'Hoa\\Compiler\\Llk\\TreeNode',
        'currentClassName' => 'Hoa\\Compiler\\Llk\\TreeNode',
        'aliasName' => NULL,
      ),
      '__destruct' => 
      array (
        'name' => '__destruct',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Remove circular reference to the parent (help the garbage collector).
 *
 * @return  void
 */',
        'startLine' => 341,
        'endLine' => 346,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Hoa\\Compiler\\Llk',
        'declaringClassName' => 'Hoa\\Compiler\\Llk\\TreeNode',
        'implementingClassName' => 'Hoa\\Compiler\\Llk\\TreeNode',
        'currentClassName' => 'Hoa\\Compiler\\Llk\\TreeNode',
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