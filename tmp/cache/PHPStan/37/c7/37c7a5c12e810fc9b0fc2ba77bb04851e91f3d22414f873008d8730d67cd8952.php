<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/src/Rules/Api/OldPhpParser4ClassRule.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\Rules\Api\OldPhpParser4ClassRule
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-f3d36b0a4bef701dc69e860083f1176b6a2503d304edde9ee0b6ac6428f47617',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\Rules\\Api\\OldPhpParser4ClassRule',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/src/Rules/Api/OldPhpParser4ClassRule.php',
      ),
    ),
    'namespace' => 'PHPStan\\Rules\\Api',
    'name' => 'PHPStan\\Rules\\Api\\OldPhpParser4ClassRule',
    'shortName' => 'OldPhpParser4ClassRule',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * @implements Rule<Name>
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
            'code' => '0',
            'attributes' => 
            array (
              'startLine' => 20,
              'endLine' => 20,
              'startTokenPos' => 89,
              'startFilePos' => 448,
              'endTokenPos' => 89,
              'endFilePos' => 448,
            ),
          ),
        ),
      ),
    ),
    'startLine' => 20,
    'endLine' => 81,
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
      'NAME_MAPPING' => 
      array (
        'declaringClassName' => 'PHPStan\\Rules\\Api\\OldPhpParser4ClassRule',
        'implementingClassName' => 'PHPStan\\Rules\\Api\\OldPhpParser4ClassRule',
        'name' => 'NAME_MAPPING',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[
    // from https://github.com/nikic/PHP-Parser/blob/master/UPGRADE-5.0.md#renamed-nodes
    \'PhpParser\\Node\\Scalar\\LNumber\' => \\PhpParser\\Node\\Scalar\\Int_::class,
    \'PhpParser\\Node\\Scalar\\DNumber\' => \\PhpParser\\Node\\Scalar\\Float_::class,
    \'PhpParser\\Node\\Scalar\\Encapsed\' => \\PhpParser\\Node\\Scalar\\InterpolatedString::class,
    \'PhpParser\\Node\\Scalar\\EncapsedStringPart\' => \\PhpParser\\Node\\InterpolatedStringPart::class,
    \'PhpParser\\Node\\Expr\\ArrayItem\' => \\PhpParser\\Node\\ArrayItem::class,
    \'PhpParser\\Node\\Expr\\ClosureUse\' => \\PhpParser\\Node\\ClosureUse::class,
    \'PhpParser\\Node\\Stmt\\DeclareDeclare\' => \\PhpParser\\Node\\DeclareItem::class,
    \'PhpParser\\Node\\Stmt\\PropertyProperty\' => \\PhpParser\\Node\\PropertyItem::class,
    \'PhpParser\\Node\\Stmt\\StaticVar\' => \\PhpParser\\Node\\StaticVar::class,
    \'PhpParser\\Node\\Stmt\\UseUse\' => \\PhpParser\\Node\\UseItem::class,
]',
          'attributes' => 
          array (
            'startLine' => 24,
            'endLine' => 36,
            'startTokenPos' => 113,
            'startFilePos' => 536,
            'endTokenPos' => 207,
            'endFilePos' => 1287,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 24,
        'endLine' => 36,
        'startColumn' => 2,
        'endColumn' => 3,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
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
        'startLine' => 38,
        'endLine' => 41,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Rules\\Api',
        'declaringClassName' => 'PHPStan\\Rules\\Api\\OldPhpParser4ClassRule',
        'implementingClassName' => 'PHPStan\\Rules\\Api\\OldPhpParser4ClassRule',
        'currentClassName' => 'PHPStan\\Rules\\Api\\OldPhpParser4ClassRule',
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
            'startLine' => 43,
            'endLine' => 43,
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
            'startLine' => 43,
            'endLine' => 43,
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
        'startLine' => 43,
        'endLine' => 79,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Rules\\Api',
        'declaringClassName' => 'PHPStan\\Rules\\Api\\OldPhpParser4ClassRule',
        'implementingClassName' => 'PHPStan\\Rules\\Api\\OldPhpParser4ClassRule',
        'currentClassName' => 'PHPStan\\Rules\\Api\\OldPhpParser4ClassRule',
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