<?php declare(strict_types = 1);

// osfsl-/home/runner/work/phpstan-src/phpstan-src/vendor/nikic/php-parser/lib/PhpParser/NodeAbstract.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PhpParser\NodeAbstract
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-b5a9bbcfb2e85eb01d566d3eaf831029b066ced2679d98adabe8c8776b3a86ae-8.4.21-6.70.0.1',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PhpParser\\NodeAbstract',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/vendor/nikic/php-parser/lib/PhpParser/NodeAbstract.php',
      ),
    ),
    'namespace' => 'PhpParser',
    'name' => 'PhpParser\\NodeAbstract',
    'shortName' => 'NodeAbstract',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 64,
    'docComment' => NULL,
    'attributes' => 
    array (
    ),
    'startLine' => 5,
    'endLine' => 181,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
      0 => 'PhpParser\\Node',
      1 => 'JsonSerializable',
    ),
    'traitClassNames' => 
    array (
    ),
    'immediateConstants' => 
    array (
    ),
    'immediateProperties' => 
    array (
      'attributes' => 
      array (
        'declaringClassName' => 'PhpParser\\NodeAbstract',
        'implementingClassName' => 'PhpParser\\NodeAbstract',
        'name' => 'attributes',
        'modifiers' => 2,
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
        'docComment' => '/** @var array<string, mixed> Attributes */',
        'attributes' => 
        array (
        ),
        'startLine' => 7,
        'endLine' => 7,
        'startColumn' => 5,
        'endColumn' => 32,
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
          'attributes' => 
          array (
            'name' => 'attributes',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 14,
                'endLine' => 14,
                'startTokenPos' => 52,
                'startFilePos' => 366,
                'endTokenPos' => 53,
                'endFilePos' => 367,
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
            'startLine' => 14,
            'endLine' => 14,
            'startColumn' => 33,
            'endColumn' => 54,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Creates a Node.
 *
 * @param array<string, mixed> $attributes Array of attributes
 */',
        'startLine' => 14,
        'endLine' => 16,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpParser',
        'declaringClassName' => 'PhpParser\\NodeAbstract',
        'implementingClassName' => 'PhpParser\\NodeAbstract',
        'currentClassName' => 'PhpParser\\NodeAbstract',
        'aliasName' => NULL,
      ),
      'getLine' => 
      array (
        'name' => 'getLine',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'int',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Gets line the node started in (alias of getStartLine).
 *
 * @return int Start line (or -1 if not available)
 * @phpstan-return -1|positive-int
 */',
        'startLine' => 24,
        'endLine' => 26,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpParser',
        'declaringClassName' => 'PhpParser\\NodeAbstract',
        'implementingClassName' => 'PhpParser\\NodeAbstract',
        'currentClassName' => 'PhpParser\\NodeAbstract',
        'aliasName' => NULL,
      ),
      'getStartLine' => 
      array (
        'name' => 'getStartLine',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'int',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Gets line the node started in.
 *
 * Requires the \'startLine\' attribute to be enabled in the lexer (enabled by default).
 *
 * @return int Start line (or -1 if not available)
 * @phpstan-return -1|positive-int
 */',
        'startLine' => 36,
        'endLine' => 38,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpParser',
        'declaringClassName' => 'PhpParser\\NodeAbstract',
        'implementingClassName' => 'PhpParser\\NodeAbstract',
        'currentClassName' => 'PhpParser\\NodeAbstract',
        'aliasName' => NULL,
      ),
      'getEndLine' => 
      array (
        'name' => 'getEndLine',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'int',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Gets the line the node ended in.
 *
 * Requires the \'endLine\' attribute to be enabled in the lexer (enabled by default).
 *
 * @return int End line (or -1 if not available)
 * @phpstan-return -1|positive-int
 */',
        'startLine' => 48,
        'endLine' => 50,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpParser',
        'declaringClassName' => 'PhpParser\\NodeAbstract',
        'implementingClassName' => 'PhpParser\\NodeAbstract',
        'currentClassName' => 'PhpParser\\NodeAbstract',
        'aliasName' => NULL,
      ),
      'getStartTokenPos' => 
      array (
        'name' => 'getStartTokenPos',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'int',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Gets the token offset of the first token that is part of this node.
 *
 * The offset is an index into the array returned by Lexer::getTokens().
 *
 * Requires the \'startTokenPos\' attribute to be enabled in the lexer (DISABLED by default).
 *
 * @return int Token start position (or -1 if not available)
 */',
        'startLine' => 61,
        'endLine' => 63,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpParser',
        'declaringClassName' => 'PhpParser\\NodeAbstract',
        'implementingClassName' => 'PhpParser\\NodeAbstract',
        'currentClassName' => 'PhpParser\\NodeAbstract',
        'aliasName' => NULL,
      ),
      'getEndTokenPos' => 
      array (
        'name' => 'getEndTokenPos',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'int',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Gets the token offset of the last token that is part of this node.
 *
 * The offset is an index into the array returned by Lexer::getTokens().
 *
 * Requires the \'endTokenPos\' attribute to be enabled in the lexer (DISABLED by default).
 *
 * @return int Token end position (or -1 if not available)
 */',
        'startLine' => 74,
        'endLine' => 76,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpParser',
        'declaringClassName' => 'PhpParser\\NodeAbstract',
        'implementingClassName' => 'PhpParser\\NodeAbstract',
        'currentClassName' => 'PhpParser\\NodeAbstract',
        'aliasName' => NULL,
      ),
      'getStartFilePos' => 
      array (
        'name' => 'getStartFilePos',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'int',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Gets the file offset of the first character that is part of this node.
 *
 * Requires the \'startFilePos\' attribute to be enabled in the lexer (DISABLED by default).
 *
 * @return int File start position (or -1 if not available)
 */',
        'startLine' => 85,
        'endLine' => 87,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpParser',
        'declaringClassName' => 'PhpParser\\NodeAbstract',
        'implementingClassName' => 'PhpParser\\NodeAbstract',
        'currentClassName' => 'PhpParser\\NodeAbstract',
        'aliasName' => NULL,
      ),
      'getEndFilePos' => 
      array (
        'name' => 'getEndFilePos',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'int',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Gets the file offset of the last character that is part of this node.
 *
 * Requires the \'endFilePos\' attribute to be enabled in the lexer (DISABLED by default).
 *
 * @return int File end position (or -1 if not available)
 */',
        'startLine' => 96,
        'endLine' => 98,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpParser',
        'declaringClassName' => 'PhpParser\\NodeAbstract',
        'implementingClassName' => 'PhpParser\\NodeAbstract',
        'currentClassName' => 'PhpParser\\NodeAbstract',
        'aliasName' => NULL,
      ),
      'getComments' => 
      array (
        'name' => 'getComments',
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
        'docComment' => '/**
 * Gets all comments directly preceding this node.
 *
 * The comments are also available through the "comments" attribute.
 *
 * @return Comment[]
 */',
        'startLine' => 107,
        'endLine' => 109,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpParser',
        'declaringClassName' => 'PhpParser\\NodeAbstract',
        'implementingClassName' => 'PhpParser\\NodeAbstract',
        'currentClassName' => 'PhpParser\\NodeAbstract',
        'aliasName' => NULL,
      ),
      'getDocComment' => 
      array (
        'name' => 'getDocComment',
        'parameters' => 
        array (
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
                  'name' => 'PhpParser\\Comment\\Doc',
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
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Gets the doc comment of the node.
 *
 * @return null|Comment\\Doc Doc comment object or null
 */',
        'startLine' => 116,
        'endLine' => 126,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpParser',
        'declaringClassName' => 'PhpParser\\NodeAbstract',
        'implementingClassName' => 'PhpParser\\NodeAbstract',
        'currentClassName' => 'PhpParser\\NodeAbstract',
        'aliasName' => NULL,
      ),
      'setDocComment' => 
      array (
        'name' => 'setDocComment',
        'parameters' => 
        array (
          'docComment' => 
          array (
            'name' => 'docComment',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PhpParser\\Comment\\Doc',
                'isIdentifier' => false,
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
            'startColumn' => 35,
            'endColumn' => 57,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Sets the doc comment of the node.
 *
 * This will either replace an existing doc comment or add it to the comments array.
 *
 * @param Comment\\Doc $docComment Doc comment to set
 */',
        'startLine' => 135,
        'endLine' => 149,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpParser',
        'declaringClassName' => 'PhpParser\\NodeAbstract',
        'implementingClassName' => 'PhpParser\\NodeAbstract',
        'currentClassName' => 'PhpParser\\NodeAbstract',
        'aliasName' => NULL,
      ),
      'setAttribute' => 
      array (
        'name' => 'setAttribute',
        'parameters' => 
        array (
          'key' => 
          array (
            'name' => 'key',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'string',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 151,
            'endLine' => 151,
            'startColumn' => 34,
            'endColumn' => 44,
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
            'startLine' => 151,
            'endLine' => 151,
            'startColumn' => 47,
            'endColumn' => 52,
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
        'startLine' => 151,
        'endLine' => 153,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpParser',
        'declaringClassName' => 'PhpParser\\NodeAbstract',
        'implementingClassName' => 'PhpParser\\NodeAbstract',
        'currentClassName' => 'PhpParser\\NodeAbstract',
        'aliasName' => NULL,
      ),
      'hasAttribute' => 
      array (
        'name' => 'hasAttribute',
        'parameters' => 
        array (
          'key' => 
          array (
            'name' => 'key',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'string',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 155,
            'endLine' => 155,
            'startColumn' => 34,
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
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 155,
        'endLine' => 157,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpParser',
        'declaringClassName' => 'PhpParser\\NodeAbstract',
        'implementingClassName' => 'PhpParser\\NodeAbstract',
        'currentClassName' => 'PhpParser\\NodeAbstract',
        'aliasName' => NULL,
      ),
      'getAttribute' => 
      array (
        'name' => 'getAttribute',
        'parameters' => 
        array (
          'key' => 
          array (
            'name' => 'key',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'string',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 159,
            'endLine' => 159,
            'startColumn' => 34,
            'endColumn' => 44,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'default' => 
          array (
            'name' => 'default',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 159,
                'endLine' => 159,
                'startTokenPos' => 627,
                'startFilePos' => 4844,
                'endTokenPos' => 627,
                'endFilePos' => 4847,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 159,
            'endLine' => 159,
            'startColumn' => 47,
            'endColumn' => 61,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 159,
        'endLine' => 165,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpParser',
        'declaringClassName' => 'PhpParser\\NodeAbstract',
        'implementingClassName' => 'PhpParser\\NodeAbstract',
        'currentClassName' => 'PhpParser\\NodeAbstract',
        'aliasName' => NULL,
      ),
      'getAttributes' => 
      array (
        'name' => 'getAttributes',
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
        'startLine' => 167,
        'endLine' => 169,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpParser',
        'declaringClassName' => 'PhpParser\\NodeAbstract',
        'implementingClassName' => 'PhpParser\\NodeAbstract',
        'currentClassName' => 'PhpParser\\NodeAbstract',
        'aliasName' => NULL,
      ),
      'setAttributes' => 
      array (
        'name' => 'setAttributes',
        'parameters' => 
        array (
          'attributes' => 
          array (
            'name' => 'attributes',
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
            'startLine' => 171,
            'endLine' => 171,
            'startColumn' => 35,
            'endColumn' => 51,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 171,
        'endLine' => 173,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpParser',
        'declaringClassName' => 'PhpParser\\NodeAbstract',
        'implementingClassName' => 'PhpParser\\NodeAbstract',
        'currentClassName' => 'PhpParser\\NodeAbstract',
        'aliasName' => NULL,
      ),
      'jsonSerialize' => 
      array (
        'name' => 'jsonSerialize',
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
        'docComment' => '/**
 * @return array<string, mixed>
 */',
        'startLine' => 178,
        'endLine' => 180,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpParser',
        'declaringClassName' => 'PhpParser\\NodeAbstract',
        'implementingClassName' => 'PhpParser\\NodeAbstract',
        'currentClassName' => 'PhpParser\\NodeAbstract',
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