<?php declare(strict_types = 1);

// osfsl-/home/runner/work/phpstan-src/phpstan-src/vendor/composer/../nikic/php-parser/lib/PhpParser/Node/Stmt/Use_.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PhpParser\Node\Stmt\Use_
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-b8306106f1eb953a3e855e544aa510cb0d4da4838ee0394cb723180f1de39385-8.4.21-6.70.0.1',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PhpParser\\Node\\Stmt\\Use_',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/vendor/composer/../nikic/php-parser/lib/PhpParser/Node/Stmt/Use_.php',
      ),
    ),
    'namespace' => 'PhpParser\\Node\\Stmt',
    'name' => 'PhpParser\\Node\\Stmt\\Use_',
    'shortName' => 'Use_',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => NULL,
    'attributes' => 
    array (
    ),
    'startLine' => 8,
    'endLine' => 47,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'PhpParser\\Node\\Stmt',
    'implementsClassNames' => 
    array (
    ),
    'traitClassNames' => 
    array (
    ),
    'immediateConstants' => 
    array (
      'TYPE_UNKNOWN' => 
      array (
        'declaringClassName' => 'PhpParser\\Node\\Stmt\\Use_',
        'implementingClassName' => 'PhpParser\\Node\\Stmt\\Use_',
        'name' => 'TYPE_UNKNOWN',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '0',
          'attributes' => 
          array (
            'startLine' => 14,
            'endLine' => 14,
            'startTokenPos' => 44,
            'startFilePos' => 527,
            'endTokenPos' => 44,
            'endFilePos' => 527,
          ),
        ),
        'docComment' => '/**
 * Unknown type. Both Stmt\\Use_ / Stmt\\GroupUse and Stmt\\UseUse have a $type property, one of them will always be
 * TYPE_UNKNOWN while the other has one of the three other possible types. For normal use statements the type on the
 * Stmt\\UseUse is unknown. It\'s only the other way around for mixed group use declarations.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 14,
        'endLine' => 14,
        'startColumn' => 5,
        'endColumn' => 34,
      ),
      'TYPE_NORMAL' => 
      array (
        'declaringClassName' => 'PhpParser\\Node\\Stmt\\Use_',
        'implementingClassName' => 'PhpParser\\Node\\Stmt\\Use_',
        'name' => 'TYPE_NORMAL',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '1',
          'attributes' => 
          array (
            'startLine' => 16,
            'endLine' => 16,
            'startTokenPos' => 57,
            'startFilePos' => 598,
            'endTokenPos' => 57,
            'endFilePos' => 598,
          ),
        ),
        'docComment' => '/** Class or namespace import */',
        'attributes' => 
        array (
        ),
        'startLine' => 16,
        'endLine' => 16,
        'startColumn' => 5,
        'endColumn' => 33,
      ),
      'TYPE_FUNCTION' => 
      array (
        'declaringClassName' => 'PhpParser\\Node\\Stmt\\Use_',
        'implementingClassName' => 'PhpParser\\Node\\Stmt\\Use_',
        'name' => 'TYPE_FUNCTION',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '2',
          'attributes' => 
          array (
            'startLine' => 18,
            'endLine' => 18,
            'startTokenPos' => 70,
            'startFilePos' => 661,
            'endTokenPos' => 70,
            'endFilePos' => 661,
          ),
        ),
        'docComment' => '/** Function import */',
        'attributes' => 
        array (
        ),
        'startLine' => 18,
        'endLine' => 18,
        'startColumn' => 5,
        'endColumn' => 35,
      ),
      'TYPE_CONSTANT' => 
      array (
        'declaringClassName' => 'PhpParser\\Node\\Stmt\\Use_',
        'implementingClassName' => 'PhpParser\\Node\\Stmt\\Use_',
        'name' => 'TYPE_CONSTANT',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '3',
          'attributes' => 
          array (
            'startLine' => 20,
            'endLine' => 20,
            'startTokenPos' => 83,
            'startFilePos' => 724,
            'endTokenPos' => 83,
            'endFilePos' => 724,
          ),
        ),
        'docComment' => '/** Constant import */',
        'attributes' => 
        array (
        ),
        'startLine' => 20,
        'endLine' => 20,
        'startColumn' => 5,
        'endColumn' => 35,
      ),
    ),
    'immediateProperties' => 
    array (
      'type' => 
      array (
        'declaringClassName' => 'PhpParser\\Node\\Stmt\\Use_',
        'implementingClassName' => 'PhpParser\\Node\\Stmt\\Use_',
        'name' => 'type',
        'modifiers' => 1,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'int',
            'isIdentifier' => true,
          ),
        ),
        'default' => NULL,
        'docComment' => '/** @var self::TYPE_* Type of alias */',
        'attributes' => 
        array (
        ),
        'startLine' => 23,
        'endLine' => 23,
        'startColumn' => 5,
        'endColumn' => 21,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'uses' => 
      array (
        'declaringClassName' => 'PhpParser\\Node\\Stmt\\Use_',
        'implementingClassName' => 'PhpParser\\Node\\Stmt\\Use_',
        'name' => 'uses',
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
        'docComment' => '/** @var UseItem[] Aliases */',
        'attributes' => 
        array (
        ),
        'startLine' => 25,
        'endLine' => 25,
        'startColumn' => 5,
        'endColumn' => 23,
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
          'uses' => 
          array (
            'name' => 'uses',
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
            'startLine' => 34,
            'endLine' => 34,
            'startColumn' => 33,
            'endColumn' => 43,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'type' => 
          array (
            'name' => 'type',
            'default' => 
            array (
              'code' => 'self::TYPE_NORMAL',
              'attributes' => 
              array (
                'startLine' => 34,
                'endLine' => 34,
                'startTokenPos' => 123,
                'startFilePos' => 1135,
                'endTokenPos' => 125,
                'endFilePos' => 1151,
              ),
            ),
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
            'startLine' => 34,
            'endLine' => 34,
            'startColumn' => 46,
            'endColumn' => 74,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
          'attributes' => 
          array (
            'name' => 'attributes',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 34,
                'endLine' => 34,
                'startTokenPos' => 134,
                'startFilePos' => 1174,
                'endTokenPos' => 135,
                'endFilePos' => 1175,
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
            'startLine' => 34,
            'endLine' => 34,
            'startColumn' => 77,
            'endColumn' => 98,
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
 * Constructs an alias (use) list node.
 *
 * @param UseItem[] $uses Aliases
 * @param Stmt\\Use_::TYPE_* $type Type of alias
 * @param array<string, mixed> $attributes Additional attributes
 */',
        'startLine' => 34,
        'endLine' => 38,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpParser\\Node\\Stmt',
        'declaringClassName' => 'PhpParser\\Node\\Stmt\\Use_',
        'implementingClassName' => 'PhpParser\\Node\\Stmt\\Use_',
        'currentClassName' => 'PhpParser\\Node\\Stmt\\Use_',
        'aliasName' => NULL,
      ),
      'getSubNodeNames' => 
      array (
        'name' => 'getSubNodeNames',
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
        'docComment' => NULL,
        'startLine' => 40,
        'endLine' => 42,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpParser\\Node\\Stmt',
        'declaringClassName' => 'PhpParser\\Node\\Stmt\\Use_',
        'implementingClassName' => 'PhpParser\\Node\\Stmt\\Use_',
        'currentClassName' => 'PhpParser\\Node\\Stmt\\Use_',
        'aliasName' => NULL,
      ),
      'getType' => 
      array (
        'name' => 'getType',
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
        'startLine' => 44,
        'endLine' => 46,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpParser\\Node\\Stmt',
        'declaringClassName' => 'PhpParser\\Node\\Stmt\\Use_',
        'implementingClassName' => 'PhpParser\\Node\\Stmt\\Use_',
        'currentClassName' => 'PhpParser\\Node\\Stmt\\Use_',
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