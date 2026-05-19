<?php declare(strict_types = 1);

// osfsl-/home/runner/work/phpstan-src/phpstan-src/vendor/composer/../symfony/console/Output/Output.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Symfony\Component\Console\Output\Output
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-a4a199c6bc589875d17012bf6df6d52042f120a40349157bb067c2284ec8df25-8.4.21-6.70.0.1',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Symfony\\Component\\Console\\Output\\Output',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/vendor/composer/../symfony/console/Output/Output.php',
      ),
    ),
    'namespace' => 'Symfony\\Component\\Console\\Output',
    'name' => 'Symfony\\Component\\Console\\Output\\Output',
    'shortName' => 'Output',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 64,
    'docComment' => '/**
 * Base class for output classes.
 *
 * There are five levels of verbosity:
 *
 *  * normal: no option passed (normal output)
 *  * verbose: -v (more output)
 *  * very verbose: -vv (highly extended output)
 *  * debug: -vvv (all debug output)
 *  * quiet: -q (no output)
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 30,
    'endLine' => 174,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
      0 => 'Symfony\\Component\\Console\\Output\\OutputInterface',
    ),
    'traitClassNames' => 
    array (
    ),
    'immediateConstants' => 
    array (
    ),
    'immediateProperties' => 
    array (
      'verbosity' => 
      array (
        'declaringClassName' => 'Symfony\\Component\\Console\\Output\\Output',
        'implementingClassName' => 'Symfony\\Component\\Console\\Output\\Output',
        'name' => 'verbosity',
        'modifiers' => 4,
        'type' => NULL,
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 32,
        'endLine' => 32,
        'startColumn' => 5,
        'endColumn' => 23,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'formatter' => 
      array (
        'declaringClassName' => 'Symfony\\Component\\Console\\Output\\Output',
        'implementingClassName' => 'Symfony\\Component\\Console\\Output\\Output',
        'name' => 'formatter',
        'modifiers' => 4,
        'type' => NULL,
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 33,
        'endLine' => 33,
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
          'verbosity' => 
          array (
            'name' => 'verbosity',
            'default' => 
            array (
              'code' => 'self::VERBOSITY_NORMAL',
              'attributes' => 
              array (
                'startLine' => 40,
                'endLine' => 40,
                'startTokenPos' => 58,
                'startFilePos' => 1235,
                'endTokenPos' => 60,
                'endFilePos' => 1256,
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
                      'name' => 'int',
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
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 40,
            'endLine' => 40,
            'startColumn' => 33,
            'endColumn' => 72,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
          'decorated' => 
          array (
            'name' => 'decorated',
            'default' => 
            array (
              'code' => 'false',
              'attributes' => 
              array (
                'startLine' => 40,
                'endLine' => 40,
                'startTokenPos' => 69,
                'startFilePos' => 1277,
                'endTokenPos' => 69,
                'endFilePos' => 1281,
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
            'startLine' => 40,
            'endLine' => 40,
            'startColumn' => 75,
            'endColumn' => 97,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
          'formatter' => 
          array (
            'name' => 'formatter',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 40,
                'endLine' => 40,
                'startTokenPos' => 79,
                'startFilePos' => 1323,
                'endTokenPos' => 79,
                'endFilePos' => 1326,
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
                      'name' => 'Symfony\\Component\\Console\\Formatter\\OutputFormatterInterface',
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
            'startLine' => 40,
            'endLine' => 40,
            'startColumn' => 100,
            'endColumn' => 142,
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
 * @param int|null                      $verbosity The verbosity level (one of the VERBOSITY constants in OutputInterface)
 * @param bool                          $decorated Whether to decorate messages
 * @param OutputFormatterInterface|null $formatter Output formatter instance (null to use default OutputFormatter)
 */',
        'startLine' => 40,
        'endLine' => 45,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Symfony\\Component\\Console\\Output',
        'declaringClassName' => 'Symfony\\Component\\Console\\Output\\Output',
        'implementingClassName' => 'Symfony\\Component\\Console\\Output\\Output',
        'currentClassName' => 'Symfony\\Component\\Console\\Output\\Output',
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
            'startLine' => 50,
            'endLine' => 50,
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
        'startLine' => 50,
        'endLine' => 53,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Symfony\\Component\\Console\\Output',
        'declaringClassName' => 'Symfony\\Component\\Console\\Output\\Output',
        'implementingClassName' => 'Symfony\\Component\\Console\\Output\\Output',
        'currentClassName' => 'Symfony\\Component\\Console\\Output\\Output',
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
        'startLine' => 58,
        'endLine' => 61,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Symfony\\Component\\Console\\Output',
        'declaringClassName' => 'Symfony\\Component\\Console\\Output\\Output',
        'implementingClassName' => 'Symfony\\Component\\Console\\Output\\Output',
        'currentClassName' => 'Symfony\\Component\\Console\\Output\\Output',
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
            'startLine' => 66,
            'endLine' => 66,
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
        'startLine' => 66,
        'endLine' => 69,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Symfony\\Component\\Console\\Output',
        'declaringClassName' => 'Symfony\\Component\\Console\\Output\\Output',
        'implementingClassName' => 'Symfony\\Component\\Console\\Output\\Output',
        'currentClassName' => 'Symfony\\Component\\Console\\Output\\Output',
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
        'startLine' => 74,
        'endLine' => 77,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Symfony\\Component\\Console\\Output',
        'declaringClassName' => 'Symfony\\Component\\Console\\Output\\Output',
        'implementingClassName' => 'Symfony\\Component\\Console\\Output\\Output',
        'currentClassName' => 'Symfony\\Component\\Console\\Output\\Output',
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
            'startLine' => 82,
            'endLine' => 82,
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
        'startLine' => 82,
        'endLine' => 85,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Symfony\\Component\\Console\\Output',
        'declaringClassName' => 'Symfony\\Component\\Console\\Output\\Output',
        'implementingClassName' => 'Symfony\\Component\\Console\\Output\\Output',
        'currentClassName' => 'Symfony\\Component\\Console\\Output\\Output',
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
        'startLine' => 90,
        'endLine' => 93,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Symfony\\Component\\Console\\Output',
        'declaringClassName' => 'Symfony\\Component\\Console\\Output\\Output',
        'implementingClassName' => 'Symfony\\Component\\Console\\Output\\Output',
        'currentClassName' => 'Symfony\\Component\\Console\\Output\\Output',
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
        'startLine' => 98,
        'endLine' => 101,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Symfony\\Component\\Console\\Output',
        'declaringClassName' => 'Symfony\\Component\\Console\\Output\\Output',
        'implementingClassName' => 'Symfony\\Component\\Console\\Output\\Output',
        'currentClassName' => 'Symfony\\Component\\Console\\Output\\Output',
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
        'startLine' => 106,
        'endLine' => 109,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Symfony\\Component\\Console\\Output',
        'declaringClassName' => 'Symfony\\Component\\Console\\Output\\Output',
        'implementingClassName' => 'Symfony\\Component\\Console\\Output\\Output',
        'currentClassName' => 'Symfony\\Component\\Console\\Output\\Output',
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
        'startLine' => 114,
        'endLine' => 117,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Symfony\\Component\\Console\\Output',
        'declaringClassName' => 'Symfony\\Component\\Console\\Output\\Output',
        'implementingClassName' => 'Symfony\\Component\\Console\\Output\\Output',
        'currentClassName' => 'Symfony\\Component\\Console\\Output\\Output',
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
        'startLine' => 122,
        'endLine' => 125,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Symfony\\Component\\Console\\Output',
        'declaringClassName' => 'Symfony\\Component\\Console\\Output\\Output',
        'implementingClassName' => 'Symfony\\Component\\Console\\Output\\Output',
        'currentClassName' => 'Symfony\\Component\\Console\\Output\\Output',
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
            'startLine' => 130,
            'endLine' => 130,
            'startColumn' => 29,
            'endColumn' => 37,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'options' => 
          array (
            'name' => 'options',
            'default' => 
            array (
              'code' => 'self::OUTPUT_NORMAL',
              'attributes' => 
              array (
                'startLine' => 130,
                'endLine' => 130,
                'startTokenPos' => 399,
                'startFilePos' => 2993,
                'endTokenPos' => 401,
                'endFilePos' => 3011,
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
            'startLine' => 130,
            'endLine' => 130,
            'startColumn' => 40,
            'endColumn' => 73,
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
        'startLine' => 130,
        'endLine' => 133,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Symfony\\Component\\Console\\Output',
        'declaringClassName' => 'Symfony\\Component\\Console\\Output\\Output',
        'implementingClassName' => 'Symfony\\Component\\Console\\Output\\Output',
        'currentClassName' => 'Symfony\\Component\\Console\\Output\\Output',
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
            'startLine' => 138,
            'endLine' => 138,
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
                'startLine' => 138,
                'endLine' => 138,
                'startTokenPos' => 439,
                'startFilePos' => 3166,
                'endTokenPos' => 439,
                'endFilePos' => 3170,
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
            'startLine' => 138,
            'endLine' => 138,
            'startColumn' => 38,
            'endColumn' => 58,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
          'options' => 
          array (
            'name' => 'options',
            'default' => 
            array (
              'code' => 'self::OUTPUT_NORMAL',
              'attributes' => 
              array (
                'startLine' => 138,
                'endLine' => 138,
                'startTokenPos' => 448,
                'startFilePos' => 3188,
                'endTokenPos' => 450,
                'endFilePos' => 3206,
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
            'startLine' => 138,
            'endLine' => 138,
            'startColumn' => 61,
            'endColumn' => 94,
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
        'startLine' => 138,
        'endLine' => 168,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Symfony\\Component\\Console\\Output',
        'declaringClassName' => 'Symfony\\Component\\Console\\Output\\Output',
        'implementingClassName' => 'Symfony\\Component\\Console\\Output\\Output',
        'currentClassName' => 'Symfony\\Component\\Console\\Output\\Output',
        'aliasName' => NULL,
      ),
      'doWrite' => 
      array (
        'name' => 'doWrite',
        'parameters' => 
        array (
          'message' => 
          array (
            'name' => 'message',
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
            'startLine' => 173,
            'endLine' => 173,
            'startColumn' => 41,
            'endColumn' => 55,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'newline' => 
          array (
            'name' => 'newline',
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
            'startLine' => 173,
            'endLine' => 173,
            'startColumn' => 58,
            'endColumn' => 70,
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
 * Writes a message to the output.
 */',
        'startLine' => 173,
        'endLine' => 173,
        'startColumn' => 5,
        'endColumn' => 72,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 66,
        'namespace' => 'Symfony\\Component\\Console\\Output',
        'declaringClassName' => 'Symfony\\Component\\Console\\Output\\Output',
        'implementingClassName' => 'Symfony\\Component\\Console\\Output\\Output',
        'currentClassName' => 'Symfony\\Component\\Console\\Output\\Output',
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