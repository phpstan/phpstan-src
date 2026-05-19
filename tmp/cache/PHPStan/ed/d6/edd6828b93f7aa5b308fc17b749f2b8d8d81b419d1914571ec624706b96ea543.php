<?php declare(strict_types = 1);

// osfsl-/home/runner/work/phpstan-src/phpstan-src/vendor/composer/../symfony/console/Output/StreamOutput.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Symfony\Component\Console\Output\StreamOutput
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-499a4b559708d615fbc18e51758294b3e29a2a48b59a934d7109cbd247a12522-8.4.21-6.70.0.1',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Symfony\\Component\\Console\\Output\\StreamOutput',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/vendor/composer/../symfony/console/Output/StreamOutput.php',
      ),
    ),
    'namespace' => 'Symfony\\Component\\Console\\Output',
    'name' => 'Symfony\\Component\\Console\\Output\\StreamOutput',
    'shortName' => 'StreamOutput',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * StreamOutput writes the output to a given stream.
 *
 * Usage:
 *
 *     $output = new StreamOutput(fopen(\'php://stdout\', \'w\'));
 *
 * As `StreamOutput` can use any stream, you can also use a file:
 *
 *     $output = new StreamOutput(fopen(\'/path/to/output.log\', \'a\', false));
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 30,
    'endLine' => 123,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'Symfony\\Component\\Console\\Output\\Output',
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
      'stream' => 
      array (
        'declaringClassName' => 'Symfony\\Component\\Console\\Output\\StreamOutput',
        'implementingClassName' => 'Symfony\\Component\\Console\\Output\\StreamOutput',
        'name' => 'stream',
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
          'stream' => 
          array (
            'name' => 'stream',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 42,
            'endLine' => 42,
            'startColumn' => 33,
            'endColumn' => 39,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'verbosity' => 
          array (
            'name' => 'verbosity',
            'default' => 
            array (
              'code' => 'self::VERBOSITY_NORMAL',
              'attributes' => 
              array (
                'startLine' => 42,
                'endLine' => 42,
                'startTokenPos' => 53,
                'startFilePos' => 1405,
                'endTokenPos' => 55,
                'endFilePos' => 1426,
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
            'startLine' => 42,
            'endLine' => 42,
            'startColumn' => 42,
            'endColumn' => 80,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
          'decorated' => 
          array (
            'name' => 'decorated',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 42,
                'endLine' => 42,
                'startTokenPos' => 65,
                'startFilePos' => 1448,
                'endTokenPos' => 65,
                'endFilePos' => 1451,
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
                      'name' => 'bool',
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
            'startLine' => 42,
            'endLine' => 42,
            'startColumn' => 83,
            'endColumn' => 105,
            'parameterIndex' => 2,
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
                'startLine' => 42,
                'endLine' => 42,
                'startTokenPos' => 75,
                'startFilePos' => 1493,
                'endTokenPos' => 75,
                'endFilePos' => 1496,
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
            'startLine' => 42,
            'endLine' => 42,
            'startColumn' => 108,
            'endColumn' => 150,
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
 * @param resource                      $stream    A stream resource
 * @param int                           $verbosity The verbosity level (one of the VERBOSITY constants in OutputInterface)
 * @param bool|null                     $decorated Whether to decorate messages (null for auto-guessing)
 * @param OutputFormatterInterface|null $formatter Output formatter instance (null to use default OutputFormatter)
 *
 * @throws InvalidArgumentException When first argument is not a real stream
 */',
        'startLine' => 42,
        'endLine' => 55,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Symfony\\Component\\Console\\Output',
        'declaringClassName' => 'Symfony\\Component\\Console\\Output\\StreamOutput',
        'implementingClassName' => 'Symfony\\Component\\Console\\Output\\StreamOutput',
        'currentClassName' => 'Symfony\\Component\\Console\\Output\\StreamOutput',
        'aliasName' => NULL,
      ),
      'getStream' => 
      array (
        'name' => 'getStream',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Gets the stream attached to this StreamOutput instance.
 *
 * @return resource
 */',
        'startLine' => 62,
        'endLine' => 65,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Symfony\\Component\\Console\\Output',
        'declaringClassName' => 'Symfony\\Component\\Console\\Output\\StreamOutput',
        'implementingClassName' => 'Symfony\\Component\\Console\\Output\\StreamOutput',
        'currentClassName' => 'Symfony\\Component\\Console\\Output\\StreamOutput',
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
            'startLine' => 67,
            'endLine' => 67,
            'startColumn' => 32,
            'endColumn' => 46,
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
            'startLine' => 67,
            'endLine' => 67,
            'startColumn' => 49,
            'endColumn' => 61,
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
        'startLine' => 67,
        'endLine' => 76,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Symfony\\Component\\Console\\Output',
        'declaringClassName' => 'Symfony\\Component\\Console\\Output\\StreamOutput',
        'implementingClassName' => 'Symfony\\Component\\Console\\Output\\StreamOutput',
        'currentClassName' => 'Symfony\\Component\\Console\\Output\\StreamOutput',
        'aliasName' => NULL,
      ),
      'hasColorSupport' => 
      array (
        'name' => 'hasColorSupport',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Returns true if the stream supports colorization.
 *
 * Colorization is disabled if not supported by the stream:
 *
 * This is tricky on Windows, because Cygwin, Msys2 etc emulate pseudo
 * terminals via named pipes, so we can only check the environment.
 *
 * Reference: Composer\\XdebugHandler\\Process::supportsColor
 * https://github.com/composer/xdebug-handler
 *
 * @return bool true if the stream supports colorization, false otherwise
 */',
        'startLine' => 91,
        'endLine' => 122,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Symfony\\Component\\Console\\Output',
        'declaringClassName' => 'Symfony\\Component\\Console\\Output\\StreamOutput',
        'implementingClassName' => 'Symfony\\Component\\Console\\Output\\StreamOutput',
        'currentClassName' => 'Symfony\\Component\\Console\\Output\\StreamOutput',
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