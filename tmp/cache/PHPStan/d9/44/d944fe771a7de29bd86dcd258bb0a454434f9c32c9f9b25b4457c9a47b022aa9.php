<?php declare(strict_types = 1);

// osfsl-/home/runner/work/phpstan-src/phpstan-src/vendor/composer/../phpstan/phpdoc-parser/src/Ast/NodeVisitor.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\PhpDocParser\Ast\NodeVisitor
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-2b5d1a1961e0fd7f9de0007ce9015ebae2ecf8d00f9d7c5a9bc88cb17eabbe11-8.4.21-6.70.0.1',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\PhpDocParser\\Ast\\NodeVisitor',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/vendor/composer/../phpstan/phpdoc-parser/src/Ast/NodeVisitor.php',
      ),
    ),
    'namespace' => 'PHPStan\\PhpDocParser\\Ast',
    'name' => 'PHPStan\\PhpDocParser\\Ast\\NodeVisitor',
    'shortName' => 'NodeVisitor',
    'isInterface' => true,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Inspired by https://github.com/nikic/PHP-Parser/tree/36a6dcd04e7b0285e8f0868f44bd4927802f7df1
 *
 * Copyright (c) 2011, Nikita Popov
 * All rights reserved.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 11,
    'endLine' => 87,
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
    ),
    'immediateMethods' => 
    array (
      'beforeTraverse' => 
      array (
        'name' => 'beforeTraverse',
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
            'startLine' => 25,
            'endLine' => 25,
            'startColumn' => 33,
            'endColumn' => 44,
            'parameterIndex' => 0,
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
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Called once before traversal.
 *
 * Return value semantics:
 *  * null:      $nodes stays as-is
 *  * otherwise: $nodes is set to the return value
 *
 * @param Node[] $nodes Array of nodes
 *
 * @return Node[]|null Array of nodes
 */',
        'startLine' => 25,
        'endLine' => 25,
        'startColumn' => 2,
        'endColumn' => 54,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\PhpDocParser\\Ast',
        'declaringClassName' => 'PHPStan\\PhpDocParser\\Ast\\NodeVisitor',
        'implementingClassName' => 'PHPStan\\PhpDocParser\\Ast\\NodeVisitor',
        'currentClassName' => 'PHPStan\\PhpDocParser\\Ast\\NodeVisitor',
        'aliasName' => NULL,
      ),
      'enterNode' => 
      array (
        'name' => 'enterNode',
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
            'startLine' => 51,
            'endLine' => 51,
            'startColumn' => 28,
            'endColumn' => 37,
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
 * Called when entering a node.
 *
 * Return value semantics:
 *  * null
 *        => $node stays as-is
 *  * array (of Nodes)
 *        => The return value is merged into the parent array (at the position of the $node)
 *  * NodeTraverser::REMOVE_NODE
 *        => $node is removed from the parent array
 *  * NodeTraverser::DONT_TRAVERSE_CHILDREN
 *        => Children of $node are not traversed. $node stays as-is
 *  * NodeTraverser::DONT_TRAVERSE_CURRENT_AND_CHILDREN
 *        => Further visitors for the current node are skipped, and its children are not
 *           traversed. $node stays as-is.
 *  * NodeTraverser::STOP_TRAVERSAL
 *        => Traversal is aborted. $node stays as-is
 *  * otherwise
 *        => $node is set to the return value
 *
 * @param Node $node Node
 *
 * @return Node|Node[]|NodeTraverser::*|null Replacement node (or special return value)
 */',
        'startLine' => 51,
        'endLine' => 51,
        'startColumn' => 2,
        'endColumn' => 39,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\PhpDocParser\\Ast',
        'declaringClassName' => 'PHPStan\\PhpDocParser\\Ast\\NodeVisitor',
        'implementingClassName' => 'PHPStan\\PhpDocParser\\Ast\\NodeVisitor',
        'currentClassName' => 'PHPStan\\PhpDocParser\\Ast\\NodeVisitor',
        'aliasName' => NULL,
      ),
      'leaveNode' => 
      array (
        'name' => 'leaveNode',
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
            'startLine' => 72,
            'endLine' => 72,
            'startColumn' => 28,
            'endColumn' => 37,
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
 * Called when leaving a node.
 *
 * Return value semantics:
 *  * null
 *        => $node stays as-is
 *  * NodeTraverser::REMOVE_NODE
 *        => $node is removed from the parent array
 *  * NodeTraverser::STOP_TRAVERSAL
 *        => Traversal is aborted. $node stays as-is
 *  * array (of Nodes)
 *        => The return value is merged into the parent array (at the position of the $node)
 *  * otherwise
 *        => $node is set to the return value
 *
 * @param Node $node Node
 *
 * @return Node|Node[]|NodeTraverser::REMOVE_NODE|NodeTraverser::STOP_TRAVERSAL|null Replacement node (or special return value)
 */',
        'startLine' => 72,
        'endLine' => 72,
        'startColumn' => 2,
        'endColumn' => 39,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\PhpDocParser\\Ast',
        'declaringClassName' => 'PHPStan\\PhpDocParser\\Ast\\NodeVisitor',
        'implementingClassName' => 'PHPStan\\PhpDocParser\\Ast\\NodeVisitor',
        'currentClassName' => 'PHPStan\\PhpDocParser\\Ast\\NodeVisitor',
        'aliasName' => NULL,
      ),
      'afterTraverse' => 
      array (
        'name' => 'afterTraverse',
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
            'startColumn' => 32,
            'endColumn' => 43,
            'parameterIndex' => 0,
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
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Called once after traversal.
 *
 * Return value semantics:
 *  * null:      $nodes stays as-is
 *  * otherwise: $nodes is set to the return value
 *
 * @param Node[] $nodes Array of nodes
 *
 * @return Node[]|null Array of nodes
 */',
        'startLine' => 85,
        'endLine' => 85,
        'startColumn' => 2,
        'endColumn' => 53,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\PhpDocParser\\Ast',
        'declaringClassName' => 'PHPStan\\PhpDocParser\\Ast\\NodeVisitor',
        'implementingClassName' => 'PHPStan\\PhpDocParser\\Ast\\NodeVisitor',
        'currentClassName' => 'PHPStan\\PhpDocParser\\Ast\\NodeVisitor',
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