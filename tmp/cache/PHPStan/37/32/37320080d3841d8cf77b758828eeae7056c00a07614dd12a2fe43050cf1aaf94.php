<?php declare(strict_types = 1);

// osfsl-/home/runner/work/phpstan-src/phpstan-src/vendor/composer/../nikic/php-parser/lib/PhpParser/Node/Expr/Exit_.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PhpParser\Node\Expr\Exit_
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-32cbba07044d3f5c0a4dfc6a0b524b14d60332bed605788335dcbfdca8df9671-8.4.21-6.70.0.1',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PhpParser\\Node\\Expr\\Exit_',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/vendor/composer/../nikic/php-parser/lib/PhpParser/Node/Expr/Exit_.php',
      ),
    ),
    'namespace' => 'PhpParser\\Node\\Expr',
    'name' => 'PhpParser\\Node\\Expr\\Exit_',
    'shortName' => 'Exit_',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => NULL,
    'attributes' => 
    array (
    ),
    'startLine' => 7,
    'endLine' => 33,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'PhpParser\\Node\\Expr',
    'implementsClassNames' => 
    array (
    ),
    'traitClassNames' => 
    array (
    ),
    'immediateConstants' => 
    array (
      'KIND_EXIT' => 
      array (
        'declaringClassName' => 'PhpParser\\Node\\Expr\\Exit_',
        'implementingClassName' => 'PhpParser\\Node\\Expr\\Exit_',
        'name' => 'KIND_EXIT',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '1',
          'attributes' => 
          array (
            'startLine' => 9,
            'endLine' => 9,
            'startTokenPos' => 39,
            'startFilePos' => 184,
            'endTokenPos' => 39,
            'endFilePos' => 184,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 9,
        'endLine' => 9,
        'startColumn' => 5,
        'endColumn' => 31,
      ),
      'KIND_DIE' => 
      array (
        'declaringClassName' => 'PhpParser\\Node\\Expr\\Exit_',
        'implementingClassName' => 'PhpParser\\Node\\Expr\\Exit_',
        'name' => 'KIND_DIE',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '2',
          'attributes' => 
          array (
            'startLine' => 10,
            'endLine' => 10,
            'startTokenPos' => 50,
            'startFilePos' => 215,
            'endTokenPos' => 50,
            'endFilePos' => 215,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 10,
        'endLine' => 10,
        'startColumn' => 5,
        'endColumn' => 30,
      ),
    ),
    'immediateProperties' => 
    array (
      'expr' => 
      array (
        'declaringClassName' => 'PhpParser\\Node\\Expr\\Exit_',
        'implementingClassName' => 'PhpParser\\Node\\Expr\\Exit_',
        'name' => 'expr',
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
                  'name' => 'PhpParser\\Node\\Expr',
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
        'default' => NULL,
        'docComment' => '/** @var null|Expr Expression */',
        'attributes' => 
        array (
        ),
        'startLine' => 13,
        'endLine' => 13,
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
          'expr' => 
          array (
            'name' => 'expr',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 21,
                'endLine' => 21,
                'startTokenPos' => 78,
                'startFilePos' => 494,
                'endTokenPos' => 78,
                'endFilePos' => 497,
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
                      'name' => 'PhpParser\\Node\\Expr',
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
            'startLine' => 21,
            'endLine' => 21,
            'startColumn' => 33,
            'endColumn' => 50,
            'parameterIndex' => 0,
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
                'startLine' => 21,
                'endLine' => 21,
                'startTokenPos' => 87,
                'startFilePos' => 520,
                'endTokenPos' => 88,
                'endFilePos' => 521,
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
            'startLine' => 21,
            'endLine' => 21,
            'startColumn' => 53,
            'endColumn' => 74,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Constructs an exit() node.
 *
 * @param null|Expr $expr Expression
 * @param array<string, mixed> $attributes Additional attributes
 */',
        'startLine' => 21,
        'endLine' => 24,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpParser\\Node\\Expr',
        'declaringClassName' => 'PhpParser\\Node\\Expr\\Exit_',
        'implementingClassName' => 'PhpParser\\Node\\Expr\\Exit_',
        'currentClassName' => 'PhpParser\\Node\\Expr\\Exit_',
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
        'startLine' => 26,
        'endLine' => 28,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpParser\\Node\\Expr',
        'declaringClassName' => 'PhpParser\\Node\\Expr\\Exit_',
        'implementingClassName' => 'PhpParser\\Node\\Expr\\Exit_',
        'currentClassName' => 'PhpParser\\Node\\Expr\\Exit_',
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
        'startLine' => 30,
        'endLine' => 32,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpParser\\Node\\Expr',
        'declaringClassName' => 'PhpParser\\Node\\Expr\\Exit_',
        'implementingClassName' => 'PhpParser\\Node\\Expr\\Exit_',
        'currentClassName' => 'PhpParser\\Node\\Expr\\Exit_',
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