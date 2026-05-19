<?php declare(strict_types = 1);

// osfsl-/home/runner/work/phpstan-src/phpstan-src/vendor/composer/../symfony/console/Style/OutputStyle.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Symfony\Component\Console\Style\OutputStyle
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-f6c4a29039c875abb208c0e7623cb841fa7ddca8cb7f37bcf784a5bde6a9ecae-8.4.21-6.70.0.1',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Symfony\\Component\\Console\\Style\\OutputStyle',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/vendor/composer/../symfony/console/Style/OutputStyle.php',
      ),
    ),
    'namespace' => 'Symfony\\Component\\Console\\Style',
    'name' => 'Symfony\\Component\\Console\\Style\\OutputStyle',
    'shortName' => 'OutputStyle',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 64,
    'docComment' => '/**
 * Decorates output to add console style guide helpers.
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 24,
    'endLine' => 153,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
      0 => 'Symfony\\Component\\Console\\Output\\OutputInterface',
      1 => 'Symfony\\Component\\Console\\Style\\StyleInterface',
    ),
    'traitClassNames' => 
    array (
    ),
    'immediateConstants' => 
    array (
    ),
    'immediateProperties' => 
    array (
      'output' => 
      array (
        'declaringClassName' => 'Symfony\\Component\\Console\\Style\\OutputStyle',
        'implementingClassName' => 'Symfony\\Component\\Console\\Style\\OutputStyle',
        'name' => 'output',
        'modifiers' => 4,
        'type' => NULL,
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 26,
        'endLine' => 26,
        'startColumn' => 5,
        'endColumn' => 20,
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
          'output' => 
          array (
            'name' => 'output',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Symfony\\Component\\Console\\Output\\OutputInterface',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 28,
            'endLine' => 28,
            'startColumn' => 33,
            'endColumn' => 55,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 28,
        'endLine' => 31,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Symfony\\Component\\Console\\Style',
        'declaringClassName' => 'Symfony\\Component\\Console\\Style\\OutputStyle',
        'implementingClassName' => 'Symfony\\Component\\Console\\Style\\OutputStyle',
        'currentClassName' => 'Symfony\\Component\\Console\\Style\\OutputStyle',
        'aliasName' => NULL,
      ),
      'newLine' => 
      array (
        'name' => 'newLine',
        'parameters' => 
        array (
          'count' => 
          array (
            'name' => 'count',
            'default' => 
            array (
              'code' => '1',
              'attributes' => 
              array (
                'startLine' => 36,
                'endLine' => 36,
                'startTokenPos' => 89,
                'startFilePos' => 900,
                'endTokenPos' => 89,
                'endFilePos' => 900,
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
            'startLine' => 36,
            'endLine' => 36,
            'startColumn' => 29,
            'endColumn' => 42,
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
 * {@inheritdoc}
 */',
        'startLine' => 36,
        'endLine' => 39,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Symfony\\Component\\Console\\Style',
        'declaringClassName' => 'Symfony\\Component\\Console\\Style\\OutputStyle',
        'implementingClassName' => 'Symfony\\Component\\Console\\Style\\OutputStyle',
        'currentClassName' => 'Symfony\\Component\\Console\\Style\\OutputStyle',
        'aliasName' => NULL,
      ),
      'createProgressBar' => 
      array (
        'name' => 'createProgressBar',
        'parameters' => 
        array (
          'max' => 
          array (
            'name' => 'max',
            'default' => 
            array (
              'code' => '0',
              'attributes' => 
              array (
                'startLine' => 44,
                'endLine' => 44,
                'startTokenPos' => 126,
                'startFilePos' => 1068,
                'endTokenPos' => 126,
                'endFilePos' => 1068,
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
            'startLine' => 44,
            'endLine' => 44,
            'startColumn' => 39,
            'endColumn' => 50,
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
 * @return ProgressBar
 */',
        'startLine' => 44,
        'endLine' => 47,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Symfony\\Component\\Console\\Style',
        'declaringClassName' => 'Symfony\\Component\\Console\\Style\\OutputStyle',
        'implementingClassName' => 'Symfony\\Component\\Console\\Style\\OutputStyle',
        'currentClassName' => 'Symfony\\Component\\Console\\Style\\OutputStyle',
        'aliasName' => NULL,
      ),
      'write' => 
      array (
        'name' => 'write',
        'parameters' => 
        array (
          'messages' => 
          array (
            'name' => 'messages',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 52,
            'endLine' => 52,
            'startColumn' => 27,
            'endColumn' => 35,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'newline' => 
          array (
            'name' => 'newline',
            'default' => 
            array (
              'code' => 'false',
              'attributes' => 
              array (
                'startLine' => 52,
                'endLine' => 52,
                'startTokenPos' => 165,
                'startFilePos' => 1227,
                'endTokenPos' => 165,
                'endFilePos' => 1231,
              ),
            ),
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'bool',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 52,
            'endLine' => 52,
            'startColumn' => 38,
            'endColumn' => 58,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
          'type' => 
          array (
            'name' => 'type',
            'default' => 
            array (
              'code' => 'self::OUTPUT_NORMAL',
              'attributes' => 
              array (
                'startLine' => 52,
                'endLine' => 52,
                'startTokenPos' => 174,
                'startFilePos' => 1246,
                'endTokenPos' => 176,
                'endFilePos' => 1264,
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
            'startLine' => 52,
            'endLine' => 52,
            'startColumn' => 61,
            'endColumn' => 91,
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
 * {@inheritdoc}
 */',
        'startLine' => 52,
        'endLine' => 55,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Symfony\\Component\\Console\\Style',
        'declaringClassName' => 'Symfony\\Component\\Console\\Style\\OutputStyle',
        'implementingClassName' => 'Symfony\\Component\\Console\\Style\\OutputStyle',
        'currentClassName' => 'Symfony\\Component\\Console\\Style\\OutputStyle',
        'aliasName' => NULL,
      ),
      'writeln' => 
      array (
        'name' => 'writeln',
        'parameters' => 
        array (
          'messages' => 
          array (
            'name' => 'messages',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 60,
            'endLine' => 60,
            'startColumn' => 29,
            'endColumn' => 37,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'type' => 
          array (
            'name' => 'type',
            'default' => 
            array (
              'code' => 'self::OUTPUT_NORMAL',
              'attributes' => 
              array (
                'startLine' => 60,
                'endLine' => 60,
                'startTokenPos' => 216,
                'startFilePos' => 1426,
                'endTokenPos' => 218,
                'endFilePos' => 1444,
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
            'startLine' => 60,
            'endLine' => 60,
            'startColumn' => 40,
            'endColumn' => 70,
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
 * {@inheritdoc}
 */',
        'startLine' => 60,
        'endLine' => 63,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Symfony\\Component\\Console\\Style',
        'declaringClassName' => 'Symfony\\Component\\Console\\Style\\OutputStyle',
        'implementingClassName' => 'Symfony\\Component\\Console\\Style\\OutputStyle',
        'currentClassName' => 'Symfony\\Component\\Console\\Style\\OutputStyle',
        'aliasName' => NULL,
      ),
      'setVerbosity' => 
      array (
        'name' => 'setVerbosity',
        'parameters' => 
        array (
          'level' => 
          array (
            'name' => 'level',
            'default' => NULL,
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
            'startLine' => 68,
            'endLine' => 68,
            'startColumn' => 34,
            'endColumn' => 43,
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
 * {@inheritdoc}
 */',
        'startLine' => 68,
        'endLine' => 71,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Symfony\\Component\\Console\\Style',
        'declaringClassName' => 'Symfony\\Component\\Console\\Style\\OutputStyle',
        'implementingClassName' => 'Symfony\\Component\\Console\\Style\\OutputStyle',
        'currentClassName' => 'Symfony\\Component\\Console\\Style\\OutputStyle',
        'aliasName' => NULL,
      ),
      'getVerbosity' => 
      array (
        'name' => 'getVerbosity',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * {@inheritdoc}
 */',
        'startLine' => 76,
        'endLine' => 79,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Symfony\\Component\\Console\\Style',
        'declaringClassName' => 'Symfony\\Component\\Console\\Style\\OutputStyle',
        'implementingClassName' => 'Symfony\\Component\\Console\\Style\\OutputStyle',
        'currentClassName' => 'Symfony\\Component\\Console\\Style\\OutputStyle',
        'aliasName' => NULL,
      ),
      'setDecorated' => 
      array (
        'name' => 'setDecorated',
        'parameters' => 
        array (
          'decorated' => 
          array (
            'name' => 'decorated',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'bool',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 84,
            'endLine' => 84,
            'startColumn' => 34,
            'endColumn' => 48,
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
 * {@inheritdoc}
 */',
        'startLine' => 84,
        'endLine' => 87,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Symfony\\Component\\Console\\Style',
        'declaringClassName' => 'Symfony\\Component\\Console\\Style\\OutputStyle',
        'implementingClassName' => 'Symfony\\Component\\Console\\Style\\OutputStyle',
        'currentClassName' => 'Symfony\\Component\\Console\\Style\\OutputStyle',
        'aliasName' => NULL,
      ),
      'isDecorated' => 
      array (
        'name' => 'isDecorated',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * {@inheritdoc}
 */',
        'startLine' => 92,
        'endLine' => 95,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Symfony\\Component\\Console\\Style',
        'declaringClassName' => 'Symfony\\Component\\Console\\Style\\OutputStyle',
        'implementingClassName' => 'Symfony\\Component\\Console\\Style\\OutputStyle',
        'currentClassName' => 'Symfony\\Component\\Console\\Style\\OutputStyle',
        'aliasName' => NULL,
      ),
      'setFormatter' => 
      array (
        'name' => 'setFormatter',
        'parameters' => 
        array (
          'formatter' => 
          array (
            'name' => 'formatter',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Symfony\\Component\\Console\\Formatter\\OutputFormatterInterface',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 100,
            'endLine' => 100,
            'startColumn' => 34,
            'endColumn' => 68,
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
 * {@inheritdoc}
 */',
        'startLine' => 100,
        'endLine' => 103,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Symfony\\Component\\Console\\Style',
        'declaringClassName' => 'Symfony\\Component\\Console\\Style\\OutputStyle',
        'implementingClassName' => 'Symfony\\Component\\Console\\Style\\OutputStyle',
        'currentClassName' => 'Symfony\\Component\\Console\\Style\\OutputStyle',
        'aliasName' => NULL,
      ),
      'getFormatter' => 
      array (
        'name' => 'getFormatter',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * {@inheritdoc}
 */',
        'startLine' => 108,
        'endLine' => 111,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Symfony\\Component\\Console\\Style',
        'declaringClassName' => 'Symfony\\Component\\Console\\Style\\OutputStyle',
        'implementingClassName' => 'Symfony\\Component\\Console\\Style\\OutputStyle',
        'currentClassName' => 'Symfony\\Component\\Console\\Style\\OutputStyle',
        'aliasName' => NULL,
      ),
      'isQuiet' => 
      array (
        'name' => 'isQuiet',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * {@inheritdoc}
 */',
        'startLine' => 116,
        'endLine' => 119,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Symfony\\Component\\Console\\Style',
        'declaringClassName' => 'Symfony\\Component\\Console\\Style\\OutputStyle',
        'implementingClassName' => 'Symfony\\Component\\Console\\Style\\OutputStyle',
        'currentClassName' => 'Symfony\\Component\\Console\\Style\\OutputStyle',
        'aliasName' => NULL,
      ),
      'isVerbose' => 
      array (
        'name' => 'isVerbose',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * {@inheritdoc}
 */',
        'startLine' => 124,
        'endLine' => 127,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Symfony\\Component\\Console\\Style',
        'declaringClassName' => 'Symfony\\Component\\Console\\Style\\OutputStyle',
        'implementingClassName' => 'Symfony\\Component\\Console\\Style\\OutputStyle',
        'currentClassName' => 'Symfony\\Component\\Console\\Style\\OutputStyle',
        'aliasName' => NULL,
      ),
      'isVeryVerbose' => 
      array (
        'name' => 'isVeryVerbose',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * {@inheritdoc}
 */',
        'startLine' => 132,
        'endLine' => 135,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Symfony\\Component\\Console\\Style',
        'declaringClassName' => 'Symfony\\Component\\Console\\Style\\OutputStyle',
        'implementingClassName' => 'Symfony\\Component\\Console\\Style\\OutputStyle',
        'currentClassName' => 'Symfony\\Component\\Console\\Style\\OutputStyle',
        'aliasName' => NULL,
      ),
      'isDebug' => 
      array (
        'name' => 'isDebug',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * {@inheritdoc}
 */',
        'startLine' => 140,
        'endLine' => 143,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Symfony\\Component\\Console\\Style',
        'declaringClassName' => 'Symfony\\Component\\Console\\Style\\OutputStyle',
        'implementingClassName' => 'Symfony\\Component\\Console\\Style\\OutputStyle',
        'currentClassName' => 'Symfony\\Component\\Console\\Style\\OutputStyle',
        'aliasName' => NULL,
      ),
      'getErrorOutput' => 
      array (
        'name' => 'getErrorOutput',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 145,
        'endLine' => 152,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Symfony\\Component\\Console\\Style',
        'declaringClassName' => 'Symfony\\Component\\Console\\Style\\OutputStyle',
        'implementingClassName' => 'Symfony\\Component\\Console\\Style\\OutputStyle',
        'currentClassName' => 'Symfony\\Component\\Console\\Style\\OutputStyle',
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