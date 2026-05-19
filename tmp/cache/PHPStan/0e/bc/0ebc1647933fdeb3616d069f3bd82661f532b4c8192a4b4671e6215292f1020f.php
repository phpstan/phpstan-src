<?php declare(strict_types = 1);

// osfsl-/home/runner/work/phpstan-src/phpstan-src/vendor/composer/../hoa/compiler/./Llk/Parser.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Hoa\Compiler\Llk\Parser
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-2fa087b98516c8a8082c48a85849e6c5dd56cf83256dee06687afb9c8b440321-8.4.21-6.70.0.1',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Hoa\\Compiler\\Llk\\Parser',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/vendor/composer/../hoa/compiler/./Llk/Parser.php',
      ),
    ),
    'namespace' => 'Hoa\\Compiler\\Llk',
    'name' => 'Hoa\\Compiler\\Llk\\Parser',
    'shortName' => 'Parser',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Class \\Hoa\\Compiler\\Llk\\Parser.
 *
 * LL(k) parser.
 *
 * @copyright  Copyright © 2007-2017 Hoa community
 * @license    New BSD License
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 50,
    'endLine' => 777,
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
      '_pragmas' => 
      array (
        'declaringClassName' => 'Hoa\\Compiler\\Llk\\Parser',
        'implementingClassName' => 'Hoa\\Compiler\\Llk\\Parser',
        'name' => '_pragmas',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => 'null',
          'attributes' => 
          array (
            'startLine' => 57,
            'endLine' => 57,
            'startTokenPos' => 35,
            'startFilePos' => 1977,
            'endTokenPos' => 35,
            'endFilePos' => 1980,
          ),
        ),
        'docComment' => '/**
 * List of pragmas.
 *
 * @var array
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 57,
        'endLine' => 57,
        'startColumn' => 5,
        'endColumn' => 37,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      '_skip' => 
      array (
        'declaringClassName' => 'Hoa\\Compiler\\Llk\\Parser',
        'implementingClassName' => 'Hoa\\Compiler\\Llk\\Parser',
        'name' => '_skip',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => 'null',
          'attributes' => 
          array (
            'startLine' => 64,
            'endLine' => 64,
            'startTokenPos' => 46,
            'startFilePos' => 2088,
            'endTokenPos' => 46,
            'endFilePos' => 2091,
          ),
        ),
        'docComment' => '/**
 * List of skipped tokens.
 *
 * @var array
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 64,
        'endLine' => 64,
        'startColumn' => 5,
        'endColumn' => 37,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      '_tokens' => 
      array (
        'declaringClassName' => 'Hoa\\Compiler\\Llk\\Parser',
        'implementingClassName' => 'Hoa\\Compiler\\Llk\\Parser',
        'name' => '_tokens',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => 'null',
          'attributes' => 
          array (
            'startLine' => 72,
            'endLine' => 72,
            'startTokenPos' => 57,
            'startFilePos' => 2264,
            'endTokenPos' => 57,
            'endFilePos' => 2267,
          ),
        ),
        'docComment' => '/**
 * Associative array (token name => token regex), to be defined in
 * precedence order.
 *
 * @var array
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 72,
        'endLine' => 72,
        'startColumn' => 5,
        'endColumn' => 37,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      '_rules' => 
      array (
        'declaringClassName' => 'Hoa\\Compiler\\Llk\\Parser',
        'implementingClassName' => 'Hoa\\Compiler\\Llk\\Parser',
        'name' => '_rules',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => 'null',
          'attributes' => 
          array (
            'startLine' => 79,
            'endLine' => 79,
            'startTokenPos' => 68,
            'startFilePos' => 2415,
            'endTokenPos' => 68,
            'endFilePos' => 2418,
          ),
        ),
        'docComment' => '/**
 * Rules, to be defined as associative array, name => Rule object.
 *
 * @var array
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 79,
        'endLine' => 79,
        'startColumn' => 5,
        'endColumn' => 37,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      '_tokenSequence' => 
      array (
        'declaringClassName' => 'Hoa\\Compiler\\Llk\\Parser',
        'implementingClassName' => 'Hoa\\Compiler\\Llk\\Parser',
        'name' => '_tokenSequence',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => 'null',
          'attributes' => 
          array (
            'startLine' => 86,
            'endLine' => 86,
            'startTokenPos' => 79,
            'startFilePos' => 2536,
            'endTokenPos' => 79,
            'endFilePos' => 2539,
          ),
        ),
        'docComment' => '/**
 * Lexer iterator.
 *
 * @var \\Hoa\\Iterator\\Lookahead
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 86,
        'endLine' => 86,
        'startColumn' => 5,
        'endColumn' => 37,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      '_errorToken' => 
      array (
        'declaringClassName' => 'Hoa\\Compiler\\Llk\\Parser',
        'implementingClassName' => 'Hoa\\Compiler\\Llk\\Parser',
        'name' => '_errorToken',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => 'null',
          'attributes' => 
          array (
            'startLine' => 93,
            'endLine' => 93,
            'startTokenPos' => 90,
            'startFilePos' => 2656,
            'endTokenPos' => 90,
            'endFilePos' => 2659,
          ),
        ),
        'docComment' => '/**
 * Possible token causing an error.
 *
 * @var array
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 93,
        'endLine' => 93,
        'startColumn' => 5,
        'endColumn' => 37,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      '_trace' => 
      array (
        'declaringClassName' => 'Hoa\\Compiler\\Llk\\Parser',
        'implementingClassName' => 'Hoa\\Compiler\\Llk\\Parser',
        'name' => '_trace',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[]',
          'attributes' => 
          array (
            'startLine' => 100,
            'endLine' => 100,
            'startTokenPos' => 101,
            'startFilePos' => 2769,
            'endTokenPos' => 102,
            'endFilePos' => 2770,
          ),
        ),
        'docComment' => '/**
 * Trace of activated rules.
 *
 * @var array
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 100,
        'endLine' => 100,
        'startColumn' => 5,
        'endColumn' => 35,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      '_todo' => 
      array (
        'declaringClassName' => 'Hoa\\Compiler\\Llk\\Parser',
        'implementingClassName' => 'Hoa\\Compiler\\Llk\\Parser',
        'name' => '_todo',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => 'null',
          'attributes' => 
          array (
            'startLine' => 107,
            'endLine' => 107,
            'startTokenPos' => 113,
            'startFilePos' => 2874,
            'endTokenPos' => 113,
            'endFilePos' => 2877,
          ),
        ),
        'docComment' => '/**
 * Stack of todo list.
 *
 * @var array
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 107,
        'endLine' => 107,
        'startColumn' => 5,
        'endColumn' => 37,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      '_tree' => 
      array (
        'declaringClassName' => 'Hoa\\Compiler\\Llk\\Parser',
        'implementingClassName' => 'Hoa\\Compiler\\Llk\\Parser',
        'name' => '_tree',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => 'null',
          'attributes' => 
          array (
            'startLine' => 114,
            'endLine' => 114,
            'startTokenPos' => 124,
            'startFilePos' => 2987,
            'endTokenPos' => 124,
            'endFilePos' => 2990,
          ),
        ),
        'docComment' => '/**
 * AST.
 *
 * @var \\Hoa\\Compiler\\Llk\\TreeNode
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 114,
        'endLine' => 114,
        'startColumn' => 5,
        'endColumn' => 37,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      '_depth' => 
      array (
        'declaringClassName' => 'Hoa\\Compiler\\Llk\\Parser',
        'implementingClassName' => 'Hoa\\Compiler\\Llk\\Parser',
        'name' => '_depth',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '-1',
          'attributes' => 
          array (
            'startLine' => 121,
            'endLine' => 121,
            'startTokenPos' => 135,
            'startFilePos' => 3112,
            'endTokenPos' => 136,
            'endFilePos' => 3113,
          ),
        ),
        'docComment' => '/**
 * Current depth while building the trace.
 *
 * @var int
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 121,
        'endLine' => 121,
        'startColumn' => 5,
        'endColumn' => 35,
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
          'tokens' => 
          array (
            'name' => 'tokens',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 133,
                'endLine' => 133,
                'startTokenPos' => 154,
                'startFilePos' => 3358,
                'endTokenPos' => 155,
                'endFilePos' => 3359,
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
            'startLine' => 133,
            'endLine' => 133,
            'startColumn' => 9,
            'endColumn' => 27,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
          'rules' => 
          array (
            'name' => 'rules',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 134,
                'endLine' => 134,
                'startTokenPos' => 164,
                'startFilePos' => 3387,
                'endTokenPos' => 165,
                'endFilePos' => 3388,
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
            'startLine' => 134,
            'endLine' => 134,
            'startColumn' => 9,
            'endColumn' => 27,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
          'pragmas' => 
          array (
            'name' => 'pragmas',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 135,
                'endLine' => 135,
                'startTokenPos' => 174,
                'startFilePos' => 3416,
                'endTokenPos' => 175,
                'endFilePos' => 3417,
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
            'startLine' => 135,
            'endLine' => 135,
            'startColumn' => 9,
            'endColumn' => 27,
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
 * Construct the parser.
 *
 * @param   array  $tokens     Tokens.
 * @param   array  $rules      Rules.
 * @param   array  $pragmas    Pragmas.
 */',
        'startLine' => 132,
        'endLine' => 142,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Hoa\\Compiler\\Llk',
        'declaringClassName' => 'Hoa\\Compiler\\Llk\\Parser',
        'implementingClassName' => 'Hoa\\Compiler\\Llk\\Parser',
        'currentClassName' => 'Hoa\\Compiler\\Llk\\Parser',
        'aliasName' => NULL,
      ),
      'parse' => 
      array (
        'name' => 'parse',
        'parameters' => 
        array (
          'text' => 
          array (
            'name' => 'text',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 153,
            'endLine' => 153,
            'startColumn' => 27,
            'endColumn' => 31,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'rule' => 
          array (
            'name' => 'rule',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 153,
                'endLine' => 153,
                'startTokenPos' => 228,
                'startFilePos' => 3884,
                'endTokenPos' => 228,
                'endFilePos' => 3887,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 153,
            'endLine' => 153,
            'startColumn' => 34,
            'endColumn' => 45,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
          'tree' => 
          array (
            'name' => 'tree',
            'default' => 
            array (
              'code' => 'true',
              'attributes' => 
              array (
                'startLine' => 153,
                'endLine' => 153,
                'startTokenPos' => 235,
                'startFilePos' => 3898,
                'endTokenPos' => 235,
                'endFilePos' => 3901,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 153,
            'endLine' => 153,
            'startColumn' => 48,
            'endColumn' => 59,
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
 * Parse :-).
 *
 * @param   string  $text    Text to parse.
 * @param   string  $rule    The axiom, i.e. root rule.
 * @param   bool    $tree    Whether build tree or not.
 * @return  mixed
 * @throws  \\Hoa\\Compiler\\Exception\\UnexpectedToken
 */',
        'startLine' => 153,
        'endLine' => 246,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Hoa\\Compiler\\Llk',
        'declaringClassName' => 'Hoa\\Compiler\\Llk\\Parser',
        'implementingClassName' => 'Hoa\\Compiler\\Llk\\Parser',
        'currentClassName' => 'Hoa\\Compiler\\Llk\\Parser',
        'aliasName' => NULL,
      ),
      'unfold' => 
      array (
        'name' => 'unfold',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Unfold trace.
 *
 * @return  mixed
 */',
        'startLine' => 253,
        'endLine' => 278,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Hoa\\Compiler\\Llk',
        'declaringClassName' => 'Hoa\\Compiler\\Llk\\Parser',
        'implementingClassName' => 'Hoa\\Compiler\\Llk\\Parser',
        'currentClassName' => 'Hoa\\Compiler\\Llk\\Parser',
        'aliasName' => NULL,
      ),
      '_parse' => 
      array (
        'name' => '_parse',
        'parameters' => 
        array (
          'zeRule' => 
          array (
            'name' => 'zeRule',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Hoa\\Compiler\\Llk\\Rule',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 287,
            'endLine' => 287,
            'startColumn' => 31,
            'endColumn' => 42,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'next' => 
          array (
            'name' => 'next',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 287,
            'endLine' => 287,
            'startColumn' => 45,
            'endColumn' => 49,
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
 * Parse current rule.
 *
 * @param   \\Hoa\\Compiler\\Llk\\Rule  $zeRule    Current rule.
 * @param   int                     $next      Next rule index.
 * @return  bool
 */',
        'startLine' => 287,
        'endLine' => 448,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Hoa\\Compiler\\Llk',
        'declaringClassName' => 'Hoa\\Compiler\\Llk\\Parser',
        'implementingClassName' => 'Hoa\\Compiler\\Llk\\Parser',
        'currentClassName' => 'Hoa\\Compiler\\Llk\\Parser',
        'aliasName' => NULL,
      ),
      'backtrack' => 
      array (
        'name' => 'backtrack',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Backtrack the trace.
 *
 * @return  bool
 */',
        'startLine' => 455,
        'endLine' => 488,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Hoa\\Compiler\\Llk',
        'declaringClassName' => 'Hoa\\Compiler\\Llk\\Parser',
        'implementingClassName' => 'Hoa\\Compiler\\Llk\\Parser',
        'currentClassName' => 'Hoa\\Compiler\\Llk\\Parser',
        'aliasName' => NULL,
      ),
      '_buildTree' => 
      array (
        'name' => '_buildTree',
        'parameters' => 
        array (
          'i' => 
          array (
            'name' => 'i',
            'default' => 
            array (
              'code' => '0',
              'attributes' => 
              array (
                'startLine' => 498,
                'endLine' => 498,
                'startTokenPos' => 2631,
                'startFilePos' => 14585,
                'endTokenPos' => 2631,
                'endFilePos' => 14585,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 498,
            'endLine' => 498,
            'startColumn' => 35,
            'endColumn' => 40,
            'parameterIndex' => 0,
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
                'startLine' => 498,
                'endLine' => 498,
                'startTokenPos' => 2639,
                'startFilePos' => 14601,
                'endTokenPos' => 2640,
                'endFilePos' => 14602,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => true,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 498,
            'endLine' => 498,
            'startColumn' => 43,
            'endColumn' => 57,
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
 * Build AST from trace.
 * Walk through the trace iteratively and recursively.
 *
 * @param   int      $i            Current trace index.
 * @param   array    &$children    Collected children.
 * @return  \\Hoa\\Compiler\\Llk\\TreeNode
 */',
        'startLine' => 498,
        'endLine' => 605,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Hoa\\Compiler\\Llk',
        'declaringClassName' => 'Hoa\\Compiler\\Llk\\Parser',
        'implementingClassName' => 'Hoa\\Compiler\\Llk\\Parser',
        'currentClassName' => 'Hoa\\Compiler\\Llk\\Parser',
        'aliasName' => NULL,
      ),
      'mergeTree' => 
      array (
        'name' => 'mergeTree',
        'parameters' => 
        array (
          'children' => 
          array (
            'name' => 'children',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => true,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 618,
            'endLine' => 618,
            'startColumn' => 9,
            'endColumn' => 18,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'handle' => 
          array (
            'name' => 'handle',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => true,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 619,
            'endLine' => 619,
            'startColumn' => 9,
            'endColumn' => 16,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'cId' => 
          array (
            'name' => 'cId',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 620,
            'endLine' => 620,
            'startColumn' => 9,
            'endColumn' => 12,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'recursive' => 
          array (
            'name' => 'recursive',
            'default' => 
            array (
              'code' => 'false',
              'attributes' => 
              array (
                'startLine' => 621,
                'endLine' => 621,
                'startTokenPos' => 3430,
                'startFilePos' => 18474,
                'endTokenPos' => 3430,
                'endFilePos' => 18478,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 621,
            'endLine' => 621,
            'startColumn' => 9,
            'endColumn' => 26,
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
 * Try to merge directly children into an existing node.
 *
 * @param   array   &$children    Current children being gathering.
 * @param   array   &$handle      Children of the new node.
 * @param   string  $cId          Node ID.
 * @param   bool    $recursive    Whether we should merge recursively or
 *                                not.
 * @return  bool
 */',
        'startLine' => 617,
        'endLine' => 648,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Hoa\\Compiler\\Llk',
        'declaringClassName' => 'Hoa\\Compiler\\Llk\\Parser',
        'implementingClassName' => 'Hoa\\Compiler\\Llk\\Parser',
        'currentClassName' => 'Hoa\\Compiler\\Llk\\Parser',
        'aliasName' => NULL,
      ),
      'mergeTreeRecursive' => 
      array (
        'name' => 'mergeTreeRecursive',
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
            'startLine' => 658,
            'endLine' => 658,
            'startColumn' => 43,
            'endColumn' => 56,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'newNode' => 
          array (
            'name' => 'newNode',
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
            'startLine' => 658,
            'endLine' => 658,
            'startColumn' => 59,
            'endColumn' => 75,
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
 * Merge recursively.
 * Please, see self::mergeTree() to know the context.
 *
 * @param   \\Hoa\\Compiler\\Llk\\TreeNode  $node       Node that receives.
 * @param   \\Hoa\\Compiler\\Llk\\TreeNode  $newNode    Node to merge.
 * @return  void
 */',
        'startLine' => 658,
        'endLine' => 685,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Hoa\\Compiler\\Llk',
        'declaringClassName' => 'Hoa\\Compiler\\Llk\\Parser',
        'implementingClassName' => 'Hoa\\Compiler\\Llk\\Parser',
        'currentClassName' => 'Hoa\\Compiler\\Llk\\Parser',
        'aliasName' => NULL,
      ),
      'getTree' => 
      array (
        'name' => 'getTree',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get AST.
 *
 * @return  \\Hoa\\Compiler\\Llk\\TreeNode
 */',
        'startLine' => 692,
        'endLine' => 695,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Hoa\\Compiler\\Llk',
        'declaringClassName' => 'Hoa\\Compiler\\Llk\\Parser',
        'implementingClassName' => 'Hoa\\Compiler\\Llk\\Parser',
        'currentClassName' => 'Hoa\\Compiler\\Llk\\Parser',
        'aliasName' => NULL,
      ),
      'getTrace' => 
      array (
        'name' => 'getTrace',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get trace.
 *
 * @return  array
 */',
        'startLine' => 702,
        'endLine' => 705,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Hoa\\Compiler\\Llk',
        'declaringClassName' => 'Hoa\\Compiler\\Llk\\Parser',
        'implementingClassName' => 'Hoa\\Compiler\\Llk\\Parser',
        'currentClassName' => 'Hoa\\Compiler\\Llk\\Parser',
        'aliasName' => NULL,
      ),
      'getPragmas' => 
      array (
        'name' => 'getPragmas',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get pragmas.
 *
 * @return  array
 */',
        'startLine' => 712,
        'endLine' => 715,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Hoa\\Compiler\\Llk',
        'declaringClassName' => 'Hoa\\Compiler\\Llk\\Parser',
        'implementingClassName' => 'Hoa\\Compiler\\Llk\\Parser',
        'currentClassName' => 'Hoa\\Compiler\\Llk\\Parser',
        'aliasName' => NULL,
      ),
      'getTokens' => 
      array (
        'name' => 'getTokens',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get tokens.
 *
 * @return  array
 */',
        'startLine' => 722,
        'endLine' => 725,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Hoa\\Compiler\\Llk',
        'declaringClassName' => 'Hoa\\Compiler\\Llk\\Parser',
        'implementingClassName' => 'Hoa\\Compiler\\Llk\\Parser',
        'currentClassName' => 'Hoa\\Compiler\\Llk\\Parser',
        'aliasName' => NULL,
      ),
      'getTokenSequence' => 
      array (
        'name' => 'getTokenSequence',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the lexer iterator.
 *
 * @return  \\Hoa\\Iterator\\Buffer
 */',
        'startLine' => 732,
        'endLine' => 735,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Hoa\\Compiler\\Llk',
        'declaringClassName' => 'Hoa\\Compiler\\Llk\\Parser',
        'implementingClassName' => 'Hoa\\Compiler\\Llk\\Parser',
        'currentClassName' => 'Hoa\\Compiler\\Llk\\Parser',
        'aliasName' => NULL,
      ),
      'getRule' => 
      array (
        'name' => 'getRule',
        'parameters' => 
        array (
          'name' => 
          array (
            'name' => 'name',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 743,
            'endLine' => 743,
            'startColumn' => 29,
            'endColumn' => 33,
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
 * Get rule by name.
 *
 * @param   string  $name    Rule name.
 * @return  \\Hoa\\Compiler\\Llk\\Rule
 */',
        'startLine' => 743,
        'endLine' => 750,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Hoa\\Compiler\\Llk',
        'declaringClassName' => 'Hoa\\Compiler\\Llk\\Parser',
        'implementingClassName' => 'Hoa\\Compiler\\Llk\\Parser',
        'currentClassName' => 'Hoa\\Compiler\\Llk\\Parser',
        'aliasName' => NULL,
      ),
      'getRules' => 
      array (
        'name' => 'getRules',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get rules.
 *
 * @return  array
 */',
        'startLine' => 757,
        'endLine' => 760,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Hoa\\Compiler\\Llk',
        'declaringClassName' => 'Hoa\\Compiler\\Llk\\Parser',
        'implementingClassName' => 'Hoa\\Compiler\\Llk\\Parser',
        'currentClassName' => 'Hoa\\Compiler\\Llk\\Parser',
        'aliasName' => NULL,
      ),
      'getRootRule' => 
      array (
        'name' => 'getRootRule',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get root rule.
 *
 * @return  string
 */',
        'startLine' => 767,
        'endLine' => 776,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Hoa\\Compiler\\Llk',
        'declaringClassName' => 'Hoa\\Compiler\\Llk\\Parser',
        'implementingClassName' => 'Hoa\\Compiler\\Llk\\Parser',
        'currentClassName' => 'Hoa\\Compiler\\Llk\\Parser',
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