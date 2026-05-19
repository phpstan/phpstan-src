<?php declare(strict_types = 1);

// ftm-/home/runner/work/phpstan-src/phpstan-src/tests/PHPStan/Type/data/TestIntersectionTypeIsSupertypeOfCollection.php
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v5-2.3.2',
   'data' => 
  array (
    0 => 
    array (
      '9b1d1dcdf5a79667dffa44782c46afbc' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'TestIntersectionTypeIsSupertypeOf',
         'uses' => 
        array (
          'arrayaccess' => 'ArrayAccess',
          'countable' => 'Countable',
          'iteratoraggregate' => 'IteratorAggregate',
        ),
         'className' => 'TestIntersectionTypeIsSupertypeOf\\Collection',
         'functionName' => NULL,
         'templatePhpDocNodes' => 
        array (
          'TKey' => 
          array (
            0 => '@psalm-template',
            1 => 
            \PHPStan\PhpDocParser\Ast\PhpDoc\TemplateTagValueNode::__set_state(array(
               'name' => 'TKey',
               'bound' => 
              \PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode::__set_state(array(
                 'name' => 'array-key',
                 'attributes' => 
                array (
                  'startLine' => 2,
                  'endLine' => 2,
                ),
              )),
               'default' => NULL,
               'lowerBound' => NULL,
               'description' => '',
               'attributes' => 
              array (
                'startLine' => 2,
                'endLine' => 2,
              ),
            )),
          ),
          'T' => 
          array (
            0 => '@psalm-template',
            1 => 
            \PHPStan\PhpDocParser\Ast\PhpDoc\TemplateTagValueNode::__set_state(array(
               'name' => 'T',
               'bound' => NULL,
               'default' => NULL,
               'lowerBound' => NULL,
               'description' => '',
               'attributes' => 
              array (
                'startLine' => 3,
                'endLine' => 3,
              ),
            )),
          ),
        ),
         'parent' => NULL,
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
    ),
    1 => 
    array (
      '/home/runner/work/phpstan-src/phpstan-src/tests/PHPStan/Type/data/TestIntersectionTypeIsSupertypeOfCollection.php' => '37486b54bd2b547463e4c8e9643ecdbf34807cb2489414cb987584f3326167b4',
    ),
  ),
));