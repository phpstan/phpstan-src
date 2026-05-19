<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/src/Rules/PhpDoc/InvalidPHPStanDocTagRule.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\Rules\PhpDoc\InvalidPHPStanDocTagRule
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-c25e638581fd9d2ec61c886172f561a51324d237d0721d638b10247ac1ac6670',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\Rules\\PhpDoc\\InvalidPHPStanDocTagRule',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/src/Rules/PhpDoc/InvalidPHPStanDocTagRule.php',
      ),
    ),
    'namespace' => 'PHPStan\\Rules\\PhpDoc',
    'name' => 'PHPStan\\Rules\\PhpDoc\\InvalidPHPStanDocTagRule',
    'shortName' => 'InvalidPHPStanDocTagRule',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * @implements Rule<NodeAbstract>
 */',
    'attributes' => 
    array (
      0 => 
      array (
        'name' => 'PHPStan\\DependencyInjection\\RegisteredRule',
        'isRepeated' => false,
        'arguments' => 
        array (
          'level' => 
          array (
            'code' => '2',
            'attributes' => 
            array (
              'startLine' => 23,
              'endLine' => 23,
              'startTokenPos' => 100,
              'startFilePos' => 606,
              'endTokenPos' => 100,
              'endFilePos' => 606,
            ),
          ),
        ),
      ),
      1 => 
      array (
        'name' => 'PHPStan\\DependencyInjection\\ValidatesStubFiles',
        'isRepeated' => false,
        'arguments' => 
        array (
        ),
      ),
    ),
    'startLine' => 23,
    'endLine' => 129,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
      0 => 'PHPStan\\Rules\\Rule',
    ),
    'traitClassNames' => 
    array (
    ),
    'immediateConstants' => 
    array (
      'POSSIBLE_PHPSTAN_TAGS' => 
      array (
        'declaringClassName' => 'PHPStan\\Rules\\PhpDoc\\InvalidPHPStanDocTagRule',
        'implementingClassName' => 'PHPStan\\Rules\\PhpDoc\\InvalidPHPStanDocTagRule',
        'name' => 'POSSIBLE_PHPSTAN_TAGS',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'@phpstan-param\', \'@phpstan-param-out\', \'@phpstan-var\', \'@phpstan-extends\', \'@phpstan-implements\', \'@phpstan-use\', \'@phpstan-template\', \'@phpstan-template-contravariant\', \'@phpstan-template-covariant\', \'@phpstan-return\', \'@phpstan-throws\', \'@phpstan-ignore\', \'@phpstan-ignore-next-line\', \'@phpstan-ignore-line\', \'@phpstan-method\', \'@phpstan-pure\', \'@phpstan-impure\', \'@phpstan-immutable\', \'@phpstan-type\', \'@phpstan-import-type\', \'@phpstan-property\', \'@phpstan-property-read\', \'@phpstan-property-write\', \'@phpstan-consistent-constructor\', \'@phpstan-assert\', \'@phpstan-assert-if-true\', \'@phpstan-assert-if-false\', \'@phpstan-self-out\', \'@phpstan-this-out\', \'@phpstan-allow-private-mutation\', \'@phpstan-readonly\', \'@phpstan-readonly-allow-private-mutation\', \'@phpstan-require-extends\', \'@phpstan-require-implements\', \'@phpstan-sealed\', \'@phpstan-param-immediately-invoked-callable\', \'@phpstan-param-later-invoked-callable\', \'@phpstan-param-closure-this\', \'@phpstan-all-methods-pure\', \'@phpstan-all-methods-impure\']',
          'attributes' => 
          array (
            'startLine' => 28,
            'endLine' => 69,
            'startTokenPos' => 128,
            'startFilePos' => 727,
            'endTokenPos' => 250,
            'endFilePos' => 1822,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 28,
        'endLine' => 69,
        'startColumn' => 2,
        'endColumn' => 3,
      ),
    ),
    'immediateProperties' => 
    array (
      'phpDocLexer' => 
      array (
        'declaringClassName' => 'PHPStan\\Rules\\PhpDoc\\InvalidPHPStanDocTagRule',
        'implementingClassName' => 'PHPStan\\Rules\\PhpDoc\\InvalidPHPStanDocTagRule',
        'name' => 'phpDocLexer',
        'modifiers' => 4,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPStan\\PhpDocParser\\Lexer\\Lexer',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 72,
        'endLine' => 72,
        'startColumn' => 3,
        'endColumn' => 28,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'phpDocParser' => 
      array (
        'declaringClassName' => 'PHPStan\\Rules\\PhpDoc\\InvalidPHPStanDocTagRule',
        'implementingClassName' => 'PHPStan\\Rules\\PhpDoc\\InvalidPHPStanDocTagRule',
        'name' => 'phpDocParser',
        'modifiers' => 4,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPStan\\PhpDocParser\\Parser\\PhpDocParser',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 73,
        'endLine' => 73,
        'startColumn' => 3,
        'endColumn' => 36,
        'isPromoted' => true,
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
          'phpDocLexer' => 
          array (
            'name' => 'phpDocLexer',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\PhpDocParser\\Lexer\\Lexer',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 72,
            'endLine' => 72,
            'startColumn' => 3,
            'endColumn' => 28,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'phpDocParser' => 
          array (
            'name' => 'phpDocParser',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\PhpDocParser\\Parser\\PhpDocParser',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 73,
            'endLine' => 73,
            'startColumn' => 3,
            'endColumn' => 36,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 71,
        'endLine' => 76,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Rules\\PhpDoc',
        'declaringClassName' => 'PHPStan\\Rules\\PhpDoc\\InvalidPHPStanDocTagRule',
        'implementingClassName' => 'PHPStan\\Rules\\PhpDoc\\InvalidPHPStanDocTagRule',
        'currentClassName' => 'PHPStan\\Rules\\PhpDoc\\InvalidPHPStanDocTagRule',
        'aliasName' => NULL,
      ),
      'getNodeType' => 
      array (
        'name' => 'getNodeType',
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
        'startLine' => 78,
        'endLine' => 81,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Rules\\PhpDoc',
        'declaringClassName' => 'PHPStan\\Rules\\PhpDoc\\InvalidPHPStanDocTagRule',
        'implementingClassName' => 'PHPStan\\Rules\\PhpDoc\\InvalidPHPStanDocTagRule',
        'currentClassName' => 'PHPStan\\Rules\\PhpDoc\\InvalidPHPStanDocTagRule',
        'aliasName' => NULL,
      ),
      'processNode' => 
      array (
        'name' => 'processNode',
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
            'startLine' => 83,
            'endLine' => 83,
            'startColumn' => 30,
            'endColumn' => 39,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'scope' => 
          array (
            'name' => 'scope',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\Analyser\\Scope',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 83,
            'endLine' => 83,
            'startColumn' => 42,
            'endColumn' => 53,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 83,
        'endLine' => 127,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Rules\\PhpDoc',
        'declaringClassName' => 'PHPStan\\Rules\\PhpDoc\\InvalidPHPStanDocTagRule',
        'implementingClassName' => 'PHPStan\\Rules\\PhpDoc\\InvalidPHPStanDocTagRule',
        'currentClassName' => 'PHPStan\\Rules\\PhpDoc\\InvalidPHPStanDocTagRule',
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