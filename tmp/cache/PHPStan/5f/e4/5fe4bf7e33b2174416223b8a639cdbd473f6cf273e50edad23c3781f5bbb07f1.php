<?php declare(strict_types = 1);

// osfsl-/home/runner/work/phpstan-src/phpstan-src/vendor/composer/../phpstan/phpdoc-parser/src/Ast/NodeTraverser.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\PhpDocParser\Ast\NodeTraverser
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-4547b970ffc5a9e0a6edaffd3c6a967fb098b6e12bf21205b01c23d29e1bf028-8.4.21-6.70.0.1',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\PhpDocParser\\Ast\\NodeTraverser',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/vendor/composer/../phpstan/phpdoc-parser/src/Ast/NodeTraverser.php',
      ),
    ),
    'namespace' => 'PHPStan\\PhpDocParser\\Ast',
    'name' => 'PHPStan\\PhpDocParser\\Ast\\NodeTraverser',
    'shortName' => 'NodeTraverser',
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
    'startLine' => 26,
    'endLine' => 312,
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
      'DONT_TRAVERSE_CHILDREN' => 
      array (
        'declaringClassName' => 'PHPStan\\PhpDocParser\\Ast\\NodeTraverser',
        'implementingClassName' => 'PHPStan\\PhpDocParser\\Ast\\NodeTraverser',
        'name' => 'DONT_TRAVERSE_CHILDREN',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '1',
          'attributes' => 
          array (
            'startLine' => 36,
            'endLine' => 36,
            'startTokenPos' => 124,
            'startFilePos' => 1050,
            'endTokenPos' => 124,
            'endFilePos' => 1050,
          ),
        ),
        'docComment' => '/**
 * If NodeVisitor::enterNode() returns DONT_TRAVERSE_CHILDREN, child nodes
 * of the current node will not be traversed for any visitors.
 *
 * For subsequent visitors enterNode() will still be called on the current
 * node and leaveNode() will also be invoked for the current node.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 36,
        'endLine' => 36,
        'startColumn' => 2,
        'endColumn' => 41,
      ),
      'STOP_TRAVERSAL' => 
      array (
        'declaringClassName' => 'PHPStan\\PhpDocParser\\Ast\\NodeTraverser',
        'implementingClassName' => 'PHPStan\\PhpDocParser\\Ast\\NodeTraverser',
        'name' => 'STOP_TRAVERSAL',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '2',
          'attributes' => 
          array (
            'startLine' => 44,
            'endLine' => 44,
            'startTokenPos' => 137,
            'startFilePos' => 1263,
            'endTokenPos' => 137,
            'endFilePos' => 1263,
          ),
        ),
        'docComment' => '/**
 * If NodeVisitor::enterNode() or NodeVisitor::leaveNode() returns
 * STOP_TRAVERSAL, traversal is aborted.
 *
 * The afterTraverse() method will still be invoked.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 44,
        'endLine' => 44,
        'startColumn' => 2,
        'endColumn' => 33,
      ),
      'REMOVE_NODE' => 
      array (
        'declaringClassName' => 'PHPStan\\PhpDocParser\\Ast\\NodeTraverser',
        'implementingClassName' => 'PHPStan\\PhpDocParser\\Ast\\NodeTraverser',
        'name' => 'REMOVE_NODE',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '3',
          'attributes' => 
          array (
            'startLine' => 53,
            'endLine' => 53,
            'startTokenPos' => 150,
            'startFilePos' => 1524,
            'endTokenPos' => 150,
            'endFilePos' => 1524,
          ),
        ),
        'docComment' => '/**
 * If NodeVisitor::leaveNode() returns REMOVE_NODE for a node that occurs
 * in an array, it will be removed from the array.
 *
 * For subsequent visitors leaveNode() will still be invoked for the
 * removed node.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 53,
        'endLine' => 53,
        'startColumn' => 2,
        'endColumn' => 30,
      ),
      'DONT_TRAVERSE_CURRENT_AND_CHILDREN' => 
      array (
        'declaringClassName' => 'PHPStan\\PhpDocParser\\Ast\\NodeTraverser',
        'implementingClassName' => 'PHPStan\\PhpDocParser\\Ast\\NodeTraverser',
        'name' => 'DONT_TRAVERSE_CURRENT_AND_CHILDREN',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '4',
          'attributes' => 
          array (
            'startLine' => 62,
            'endLine' => 62,
            'startTokenPos' => 163,
            'startFilePos' => 1895,
            'endTokenPos' => 163,
            'endFilePos' => 1895,
          ),
        ),
        'docComment' => '/**
 * If NodeVisitor::enterNode() returns DONT_TRAVERSE_CURRENT_AND_CHILDREN, child nodes
 * of the current node will not be traversed for any visitors.
 *
 * For subsequent visitors enterNode() will not be called as well.
 * leaveNode() will be invoked for visitors that has enterNode() method invoked.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 62,
        'endLine' => 62,
        'startColumn' => 2,
        'endColumn' => 53,
      ),
    ),
    'immediateProperties' => 
    array (
      'visitors' => 
      array (
        'declaringClassName' => 'PHPStan\\PhpDocParser\\Ast\\NodeTraverser',
        'implementingClassName' => 'PHPStan\\PhpDocParser\\Ast\\NodeTraverser',
        'name' => 'visitors',
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
          'code' => '[]',
          'attributes' => 
          array (
            'startLine' => 65,
            'endLine' => 65,
            'startTokenPos' => 176,
            'startFilePos' => 1966,
            'endTokenPos' => 177,
            'endFilePos' => 1967,
          ),
        ),
        'docComment' => '/** @var list<NodeVisitor> Visitors */',
        'attributes' => 
        array (
        ),
        'startLine' => 65,
        'endLine' => 65,
        'startColumn' => 2,
        'endColumn' => 30,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'stopTraversal' => 
      array (
        'declaringClassName' => 'PHPStan\\PhpDocParser\\Ast\\NodeTraverser',
        'implementingClassName' => 'PHPStan\\PhpDocParser\\Ast\\NodeTraverser',
        'name' => 'stopTraversal',
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
        'default' => NULL,
        'docComment' => '/** @var bool Whether traversal should be stopped */',
        'attributes' => 
        array (
        ),
        'startLine' => 68,
        'endLine' => 68,
        'startColumn' => 2,
        'endColumn' => 29,
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
          'visitors' => 
          array (
            'name' => 'visitors',
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
            'startLine' => 73,
            'endLine' => 73,
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
 * @param list<NodeVisitor> $visitors
 */',
        'startLine' => 73,
        'endLine' => 76,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\PhpDocParser\\Ast',
        'declaringClassName' => 'PHPStan\\PhpDocParser\\Ast\\NodeTraverser',
        'implementingClassName' => 'PHPStan\\PhpDocParser\\Ast\\NodeTraverser',
        'currentClassName' => 'PHPStan\\PhpDocParser\\Ast\\NodeTraverser',
        'aliasName' => NULL,
      ),
      'traverse' => 
      array (
        'name' => 'traverse',
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
            'startLine' => 85,
            'endLine' => 85,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Traverses an array of nodes using the registered visitors.
 *
 * @param Node[] $nodes Array of nodes
 *
 * @return Node[] Traversed array of nodes
 */',
        'startLine' => 85,
        'endLine' => 110,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\PhpDocParser\\Ast',
        'declaringClassName' => 'PHPStan\\PhpDocParser\\Ast\\NodeTraverser',
        'implementingClassName' => 'PHPStan\\PhpDocParser\\Ast\\NodeTraverser',
        'currentClassName' => 'PHPStan\\PhpDocParser\\Ast\\NodeTraverser',
        'aliasName' => NULL,
      ),
      'traverseNode' => 
      array (
        'name' => 'traverseNode',
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
            'startLine' => 119,
            'endLine' => 119,
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
            'name' => 'PHPStan\\PhpDocParser\\Ast\\Node',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Recursively traverse a node.
 *
 * @param Node $node Node to traverse.
 *
 * @return Node Result of traversal (may be original node or new one)
 */',
        'startLine' => 119,
        'endLine' => 196,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'PHPStan\\PhpDocParser\\Ast',
        'declaringClassName' => 'PHPStan\\PhpDocParser\\Ast\\NodeTraverser',
        'implementingClassName' => 'PHPStan\\PhpDocParser\\Ast\\NodeTraverser',
        'currentClassName' => 'PHPStan\\PhpDocParser\\Ast\\NodeTraverser',
        'aliasName' => NULL,
      ),
      'traverseArray' => 
      array (
        'name' => 'traverseArray',
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
            'startLine' => 205,
            'endLine' => 205,
            'startColumn' => 33,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Recursively traverse array (usually of nodes).
 *
 * @param mixed[] $nodes Array to traverse
 *
 * @return mixed[] Result of traversal (may be original array or changed one)
 */',
        'startLine' => 205,
        'endLine' => 291,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'PHPStan\\PhpDocParser\\Ast',
        'declaringClassName' => 'PHPStan\\PhpDocParser\\Ast\\NodeTraverser',
        'implementingClassName' => 'PHPStan\\PhpDocParser\\Ast\\NodeTraverser',
        'currentClassName' => 'PHPStan\\PhpDocParser\\Ast\\NodeTraverser',
        'aliasName' => NULL,
      ),
      'ensureReplacementReasonable' => 
      array (
        'name' => 'ensureReplacementReasonable',
        'parameters' => 
        array (
          'old' => 
          array (
            'name' => 'old',
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
            'startLine' => 293,
            'endLine' => 293,
            'startColumn' => 47,
            'endColumn' => 55,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'new' => 
          array (
            'name' => 'new',
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
            'startLine' => 293,
            'endLine' => 293,
            'startColumn' => 58,
            'endColumn' => 66,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 293,
        'endLine' => 310,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'PHPStan\\PhpDocParser\\Ast',
        'declaringClassName' => 'PHPStan\\PhpDocParser\\Ast\\NodeTraverser',
        'implementingClassName' => 'PHPStan\\PhpDocParser\\Ast\\NodeTraverser',
        'currentClassName' => 'PHPStan\\PhpDocParser\\Ast\\NodeTraverser',
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