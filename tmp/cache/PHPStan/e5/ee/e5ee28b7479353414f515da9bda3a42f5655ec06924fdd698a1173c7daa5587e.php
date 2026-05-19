<?php declare(strict_types = 1);

// osfsl-/home/runner/work/phpstan-src/phpstan-src/vendor/composer/../nikic/php-parser/lib/PhpParser/NodeVisitor.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PhpParser\NodeVisitor
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-8e851a8442287afa9ad7dbcf644d75f45dab8280efdd9fed8f346cf42578847a-8.4.21-6.70.0.1',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PhpParser\\NodeVisitor',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/vendor/composer/../nikic/php-parser/lib/PhpParser/NodeVisitor.php',
      ),
    ),
    'namespace' => 'PhpParser',
    'name' => 'PhpParser\\NodeVisitor',
    'shortName' => 'NodeVisitor',
    'isInterface' => true,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => NULL,
    'attributes' => 
    array (
    ),
    'startLine' => 5,
    'endLine' => 124,
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
        'declaringClassName' => 'PhpParser\\NodeVisitor',
        'implementingClassName' => 'PhpParser\\NodeVisitor',
        'name' => 'DONT_TRAVERSE_CHILDREN',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '1',
          'attributes' => 
          array (
            'startLine' => 13,
            'endLine' => 13,
            'startTokenPos' => 30,
            'startFilePos' => 439,
            'endTokenPos' => 30,
            'endFilePos' => 439,
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
        'startLine' => 13,
        'endLine' => 13,
        'startColumn' => 5,
        'endColumn' => 44,
      ),
      'STOP_TRAVERSAL' => 
      array (
        'declaringClassName' => 'PhpParser\\NodeVisitor',
        'implementingClassName' => 'PhpParser\\NodeVisitor',
        'name' => 'STOP_TRAVERSAL',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '2',
          'attributes' => 
          array (
            'startLine' => 21,
            'endLine' => 21,
            'startTokenPos' => 43,
            'startFilePos' => 673,
            'endTokenPos' => 43,
            'endFilePos' => 673,
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
        'startLine' => 21,
        'endLine' => 21,
        'startColumn' => 5,
        'endColumn' => 36,
      ),
      'REMOVE_NODE' => 
      array (
        'declaringClassName' => 'PhpParser\\NodeVisitor',
        'implementingClassName' => 'PhpParser\\NodeVisitor',
        'name' => 'REMOVE_NODE',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '3',
          'attributes' => 
          array (
            'startLine' => 30,
            'endLine' => 30,
            'startTokenPos' => 56,
            'startFilePos' => 958,
            'endTokenPos' => 56,
            'endFilePos' => 958,
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
        'startLine' => 30,
        'endLine' => 30,
        'startColumn' => 5,
        'endColumn' => 33,
      ),
      'DONT_TRAVERSE_CURRENT_AND_CHILDREN' => 
      array (
        'declaringClassName' => 'PhpParser\\NodeVisitor',
        'implementingClassName' => 'PhpParser\\NodeVisitor',
        'name' => 'DONT_TRAVERSE_CURRENT_AND_CHILDREN',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '4',
          'attributes' => 
          array (
            'startLine' => 39,
            'endLine' => 39,
            'startTokenPos' => 69,
            'startFilePos' => 1353,
            'endTokenPos' => 69,
            'endFilePos' => 1353,
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
        'startLine' => 39,
        'endLine' => 39,
        'startColumn' => 5,
        'endColumn' => 56,
      ),
      'REPLACE_WITH_NULL' => 
      array (
        'declaringClassName' => 'PhpParser\\NodeVisitor',
        'implementingClassName' => 'PhpParser\\NodeVisitor',
        'name' => 'REPLACE_WITH_NULL',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '5',
          'attributes' => 
          array (
            'startLine' => 46,
            'endLine' => 46,
            'startTokenPos' => 82,
            'startFilePos' => 1643,
            'endTokenPos' => 82,
            'endFilePos' => 1643,
          ),
        ),
        'docComment' => '/**
 * If NodeVisitor::enterNode() or NodeVisitor::leaveNode() returns REPLACE_WITH_NULL,
 * the node will be replaced with null. This is not a legal return value if the node is part
 * of an array, rather than another node.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 46,
        'endLine' => 46,
        'startColumn' => 5,
        'endColumn' => 39,
      ),
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
            'startLine' => 59,
            'endLine' => 59,
            'startColumn' => 36,
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
 * Called once before traversal.
 *
 * Return value semantics:
 *  * null:      $nodes stays as-is
 *  * otherwise: $nodes is set to the return value
 *
 * @param Node[] $nodes Array of nodes
 *
 * @return null|Node[] Array of nodes
 */',
        'startLine' => 59,
        'endLine' => 59,
        'startColumn' => 5,
        'endColumn' => 49,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpParser',
        'declaringClassName' => 'PhpParser\\NodeVisitor',
        'implementingClassName' => 'PhpParser\\NodeVisitor',
        'currentClassName' => 'PhpParser\\NodeVisitor',
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
            'startLine' => 87,
            'endLine' => 87,
            'startColumn' => 31,
            'endColumn' => 40,
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
 *  * NodeVisitor::REMOVE_NODE
 *        => $node is removed from the parent array
 *  * NodeVisitor::REPLACE_WITH_NULL
 *        => $node is replaced with null
 *  * NodeVisitor::DONT_TRAVERSE_CHILDREN
 *        => Children of $node are not traversed. $node stays as-is
 *  * NodeVisitor::DONT_TRAVERSE_CURRENT_AND_CHILDREN
 *        => Further visitors for the current node are skipped, and its children are not
 *           traversed. $node stays as-is.
 *  * NodeVisitor::STOP_TRAVERSAL
 *        => Traversal is aborted. $node stays as-is
 *  * otherwise
 *        => $node is set to the return value
 *
 * @param Node $node Node
 *
 * @return null|int|Node|Node[] Replacement node (or special return value)
 */',
        'startLine' => 87,
        'endLine' => 87,
        'startColumn' => 5,
        'endColumn' => 42,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpParser',
        'declaringClassName' => 'PhpParser\\NodeVisitor',
        'implementingClassName' => 'PhpParser\\NodeVisitor',
        'currentClassName' => 'PhpParser\\NodeVisitor',
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
            'startLine' => 110,
            'endLine' => 110,
            'startColumn' => 31,
            'endColumn' => 40,
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
 *  * NodeVisitor::REMOVE_NODE
 *        => $node is removed from the parent array
 *  * NodeVisitor::REPLACE_WITH_NULL
 *        => $node is replaced with null
 *  * NodeVisitor::STOP_TRAVERSAL
 *        => Traversal is aborted. $node stays as-is
 *  * array (of Nodes)
 *        => The return value is merged into the parent array (at the position of the $node)
 *  * otherwise
 *        => $node is set to the return value
 *
 * @param Node $node Node
 *
 * @return null|int|Node|Node[] Replacement node (or special return value)
 */',
        'startLine' => 110,
        'endLine' => 110,
        'startColumn' => 5,
        'endColumn' => 42,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpParser',
        'declaringClassName' => 'PhpParser\\NodeVisitor',
        'implementingClassName' => 'PhpParser\\NodeVisitor',
        'currentClassName' => 'PhpParser\\NodeVisitor',
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
            'startLine' => 123,
            'endLine' => 123,
            'startColumn' => 35,
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
 * Called once after traversal.
 *
 * Return value semantics:
 *  * null:      $nodes stays as-is
 *  * otherwise: $nodes is set to the return value
 *
 * @param Node[] $nodes Array of nodes
 *
 * @return null|Node[] Array of nodes
 */',
        'startLine' => 123,
        'endLine' => 123,
        'startColumn' => 5,
        'endColumn' => 48,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpParser',
        'declaringClassName' => 'PhpParser\\NodeVisitor',
        'implementingClassName' => 'PhpParser\\NodeVisitor',
        'currentClassName' => 'PhpParser\\NodeVisitor',
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