<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/src/Reflection/BetterReflection/SourceLocator/FileReadTrapStreamWrapper.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\Reflection\BetterReflection\SourceLocator\FileReadTrapStreamWrapper
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-92129306d63404f77bb65fe78c16fc479158c0d11507486bf573677bf4137b14',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator\\FileReadTrapStreamWrapper',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/src/Reflection/BetterReflection/SourceLocator/FileReadTrapStreamWrapper.php',
      ),
    ),
    'namespace' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator',
    'name' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator\\FileReadTrapStreamWrapper',
    'shortName' => 'FileReadTrapStreamWrapper',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * This class will operate as a stream wrapper, intercepting any access to a file while
 * in operation.
 *
 * @internal DO NOT USE: this is an implementation detail of
 *           the {@see \\PHPStan\\BetterReflection\\SourceLocator\\Type\\AutoloadSourceLocator}
 *
 * phpcs:disable SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
 * phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
 * phpcs:disable Squiz.NamingConventions.ValidVariableName.NotCamelCaps
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 29,
    'endLine' => 278,
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
      'DEFAULT_STREAM_WRAPPER_PROTOCOLS' => 
      array (
        'declaringClassName' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator\\FileReadTrapStreamWrapper',
        'implementingClassName' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator\\FileReadTrapStreamWrapper',
        'name' => 'DEFAULT_STREAM_WRAPPER_PROTOCOLS',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'file\', \'phar\']',
          'attributes' => 
          array (
            'startLine' => 32,
            'endLine' => 35,
            'startTokenPos' => 116,
            'startFilePos' => 1034,
            'endTokenPos' => 124,
            'endFilePos' => 1057,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 32,
        'endLine' => 35,
        'startColumn' => 2,
        'endColumn' => 3,
      ),
    ),
    'immediateProperties' => 
    array (
      'registeredStreamWrapperProtocols' => 
      array (
        'declaringClassName' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator\\FileReadTrapStreamWrapper',
        'implementingClassName' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator\\FileReadTrapStreamWrapper',
        'name' => 'registeredStreamWrapperProtocols',
        'modifiers' => 20,
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
                  'name' => 'array',
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
        'default' => NULL,
        'docComment' => '/** @var string[]|null */',
        'attributes' => 
        array (
        ),
        'startLine' => 38,
        'endLine' => 38,
        'startColumn' => 2,
        'endColumn' => 57,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'autoloadLocatedFiles' => 
      array (
        'declaringClassName' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator\\FileReadTrapStreamWrapper',
        'implementingClassName' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator\\FileReadTrapStreamWrapper',
        'name' => 'autoloadLocatedFiles',
        'modifiers' => 17,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'default' => 
        array (
          'code' => '[]',
          'attributes' => 
          array (
            'startLine' => 41,
            'endLine' => 41,
            'startTokenPos' => 151,
            'startFilePos' => 1214,
            'endTokenPos' => 152,
            'endFilePos' => 1215,
          ),
        ),
        'docComment' => '/** @var string[] */',
        'attributes' => 
        array (
        ),
        'startLine' => 41,
        'endLine' => 41,
        'startColumn' => 2,
        'endColumn' => 48,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'readFromFile' => 
      array (
        'declaringClassName' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator\\FileReadTrapStreamWrapper',
        'implementingClassName' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator\\FileReadTrapStreamWrapper',
        'name' => 'readFromFile',
        'modifiers' => 4,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'default' => 
        array (
          'code' => 'false',
          'attributes' => 
          array (
            'startLine' => 43,
            'endLine' => 43,
            'startTokenPos' => 163,
            'startFilePos' => 1249,
            'endTokenPos' => 163,
            'endFilePos' => 1253,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 43,
        'endLine' => 43,
        'startColumn' => 2,
        'endColumn' => 36,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'seekPosition' => 
      array (
        'declaringClassName' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator\\FileReadTrapStreamWrapper',
        'implementingClassName' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator\\FileReadTrapStreamWrapper',
        'name' => 'seekPosition',
        'modifiers' => 4,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'int',
            'isIdentifier' => true,
          ),
        ),
        'default' => 
        array (
          'code' => '0',
          'attributes' => 
          array (
            'startLine' => 45,
            'endLine' => 45,
            'startTokenPos' => 174,
            'startFilePos' => 1286,
            'endTokenPos' => 174,
            'endFilePos' => 1286,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 45,
        'endLine' => 45,
        'startColumn' => 2,
        'endColumn' => 31,
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
      'withStreamWrapperOverride' => 
      array (
        'name' => 'withStreamWrapperOverride',
        'parameters' => 
        array (
          'executeMeWithinStreamWrapperOverride' => 
          array (
            'name' => 'executeMeWithinStreamWrapperOverride',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'callable',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 57,
            'endLine' => 57,
            'startColumn' => 3,
            'endColumn' => 48,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'streamWrapperProtocols' => 
          array (
            'name' => 'streamWrapperProtocols',
            'default' => 
            array (
              'code' => 'self::DEFAULT_STREAM_WRAPPER_PROTOCOLS',
              'attributes' => 
              array (
                'startLine' => 58,
                'endLine' => 58,
                'startTokenPos' => 199,
                'startFilePos' => 1695,
                'endTokenPos' => 201,
                'endFilePos' => 1732,
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
            'startLine' => 58,
            'endLine' => 58,
            'startColumn' => 3,
            'endColumn' => 72,
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
 * @param string[] $streamWrapperProtocols
 *
 * @return mixed
 *
 * @psalm-template ExecutedMethodReturnType of mixed
 * @psalm-param callable() : ExecutedMethodReturnType $executeMeWithinStreamWrapperOverride
 * @psalm-return ExecutedMethodReturnType
 */',
        'startLine' => 56,
        'endLine' => 81,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator',
        'declaringClassName' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator\\FileReadTrapStreamWrapper',
        'implementingClassName' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator\\FileReadTrapStreamWrapper',
        'currentClassName' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator\\FileReadTrapStreamWrapper',
        'aliasName' => NULL,
      ),
      'stream_open' => 
      array (
        'name' => 'stream_open',
        'parameters' => 
        array (
          'path' => 
          array (
            'name' => 'path',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 98,
            'endLine' => 98,
            'startColumn' => 30,
            'endColumn' => 34,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'mode' => 
          array (
            'name' => 'mode',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 98,
            'endLine' => 98,
            'startColumn' => 37,
            'endColumn' => 41,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'options' => 
          array (
            'name' => 'options',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 98,
            'endLine' => 98,
            'startColumn' => 44,
            'endColumn' => 51,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'openedPath' => 
          array (
            'name' => 'openedPath',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => true,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 98,
            'endLine' => 98,
            'startColumn' => 54,
            'endColumn' => 65,
            'parameterIndex' => 3,
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
        'docComment' => '/**
 * Our wrapper simply records which file we tried to load and returns
 * boolean false indicating failure.
 *
 * @internal do not call this method directly! This is stream wrapper
 *           voodoo logic that you **DO NOT** want to touch!
 *
 * @see https://php.net/manual/en/class.streamwrapper.php
 * @see https://php.net/manual/en/streamwrapper.stream-open.php
 *
 * @param string $path
 * @param string $mode
 * @param int    $options
 * @param string $openedPath
 */',
        'startLine' => 98,
        'endLine' => 109,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator',
        'declaringClassName' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator\\FileReadTrapStreamWrapper',
        'implementingClassName' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator\\FileReadTrapStreamWrapper',
        'currentClassName' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator\\FileReadTrapStreamWrapper',
        'aliasName' => NULL,
      ),
      'stream_read' => 
      array (
        'name' => 'stream_read',
        'parameters' => 
        array (
          'count' => 
          array (
            'name' => 'count',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 118,
            'endLine' => 118,
            'startColumn' => 30,
            'endColumn' => 35,
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
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Since we allow our wrapper\'s stream_open() to succeed, we need to
 * simulate a successful read so autoloaders with require() don\'t explode.
 *
 * @param int $count
 *
 */',
        'startLine' => 118,
        'endLine' => 126,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator',
        'declaringClassName' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator\\FileReadTrapStreamWrapper',
        'implementingClassName' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator\\FileReadTrapStreamWrapper',
        'currentClassName' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator\\FileReadTrapStreamWrapper',
        'aliasName' => NULL,
      ),
      'stream_close' => 
      array (
        'name' => 'stream_close',
        'parameters' => 
        array (
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
 * Since we allowed the open to succeed, we should allow the close to occur
 * as well.
 *
 */',
        'startLine' => 133,
        'endLine' => 136,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator',
        'declaringClassName' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator\\FileReadTrapStreamWrapper',
        'implementingClassName' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator\\FileReadTrapStreamWrapper',
        'currentClassName' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator\\FileReadTrapStreamWrapper',
        'aliasName' => NULL,
      ),
      'stream_stat' => 
      array (
        'name' => 'stream_stat',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Required for `require_once` and `include_once` to work per PHP.net
 * comment referenced below. We delegate to url_stat().
 *
 * @see https://www.php.net/manual/en/function.stream-wrapper-register.php#51855
 *
 * @return mixed[]|bool
 */',
        'startLine' => 146,
        'endLine' => 153,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator',
        'declaringClassName' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator\\FileReadTrapStreamWrapper',
        'implementingClassName' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator\\FileReadTrapStreamWrapper',
        'currentClassName' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator\\FileReadTrapStreamWrapper',
        'aliasName' => NULL,
      ),
      'url_stat' => 
      array (
        'name' => 'url_stat',
        'parameters' => 
        array (
          'path' => 
          array (
            'name' => 'path',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 171,
            'endLine' => 171,
            'startColumn' => 27,
            'endColumn' => 31,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'flags' => 
          array (
            'name' => 'flags',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 171,
            'endLine' => 171,
            'startColumn' => 34,
            'endColumn' => 39,
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
 * url_stat is triggered by calls like "file_exists". The call to "file_exists" must not be overloaded.
 * This function restores the original "file" stream, issues a call to "stat" to get the real results,
 * and then re-registers the AutoloadSourceLocator stream wrapper.
 *
 * @internal do not call this method directly! This is stream wrapper
 *           voodoo logic that you **DO NOT** want to touch!
 *
 * @see https://php.net/manual/en/class.streamwrapper.php
 * @see https://php.net/manual/en/streamwrapper.url-stat.php
 *
 * @param string $path
 * @param int    $flags
 *
 * @return mixed[]|bool
 */',
        'startLine' => 171,
        'endLine' => 180,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator',
        'declaringClassName' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator\\FileReadTrapStreamWrapper',
        'implementingClassName' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator\\FileReadTrapStreamWrapper',
        'currentClassName' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator\\FileReadTrapStreamWrapper',
        'aliasName' => NULL,
      ),
      'invokeWithRealFileStreamWrapper' => 
      array (
        'name' => 'invokeWithRealFileStreamWrapper',
        'parameters' => 
        array (
          'cb' => 
          array (
            'name' => 'cb',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'callable',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 186,
            'endLine' => 186,
            'startColumn' => 51,
            'endColumn' => 62,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'args' => 
          array (
            'name' => 'args',
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
            'startLine' => 186,
            'endLine' => 186,
            'startColumn' => 65,
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
 * @param mixed[] $args
 * @return mixed
 */',
        'startLine' => 186,
        'endLine' => 204,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator',
        'declaringClassName' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator\\FileReadTrapStreamWrapper',
        'implementingClassName' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator\\FileReadTrapStreamWrapper',
        'currentClassName' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator\\FileReadTrapStreamWrapper',
        'aliasName' => NULL,
      ),
      'stream_eof' => 
      array (
        'name' => 'stream_eof',
        'parameters' => 
        array (
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
        'docComment' => '/**
 * Simulates behavior of reading from an empty file.
 *
 */',
        'startLine' => 210,
        'endLine' => 213,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator',
        'declaringClassName' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator\\FileReadTrapStreamWrapper',
        'implementingClassName' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator\\FileReadTrapStreamWrapper',
        'currentClassName' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator\\FileReadTrapStreamWrapper',
        'aliasName' => NULL,
      ),
      'stream_flush' => 
      array (
        'name' => 'stream_flush',
        'parameters' => 
        array (
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
        'docComment' => '/**
 * @return true
 */',
        'startLine' => 218,
        'endLine' => 221,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator',
        'declaringClassName' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator\\FileReadTrapStreamWrapper',
        'implementingClassName' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator\\FileReadTrapStreamWrapper',
        'currentClassName' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator\\FileReadTrapStreamWrapper',
        'aliasName' => NULL,
      ),
      'stream_tell' => 
      array (
        'name' => 'stream_tell',
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
        'docComment' => NULL,
        'startLine' => 223,
        'endLine' => 226,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator',
        'declaringClassName' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator\\FileReadTrapStreamWrapper',
        'implementingClassName' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator\\FileReadTrapStreamWrapper',
        'currentClassName' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator\\FileReadTrapStreamWrapper',
        'aliasName' => NULL,
      ),
      'stream_seek' => 
      array (
        'name' => 'stream_seek',
        'parameters' => 
        array (
          'offset' => 
          array (
            'name' => 'offset',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 232,
            'endLine' => 232,
            'startColumn' => 30,
            'endColumn' => 36,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'whence' => 
          array (
            'name' => 'whence',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 232,
            'endLine' => 232,
            'startColumn' => 39,
            'endColumn' => 45,
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
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @param   int  $offset
 * @param   int  $whence
 */',
        'startLine' => 232,
        'endLine' => 254,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator',
        'declaringClassName' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator\\FileReadTrapStreamWrapper',
        'implementingClassName' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator\\FileReadTrapStreamWrapper',
        'currentClassName' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator\\FileReadTrapStreamWrapper',
        'aliasName' => NULL,
      ),
      'stream_set_option' => 
      array (
        'name' => 'stream_set_option',
        'parameters' => 
        array (
          'option' => 
          array (
            'name' => 'option',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 263,
            'endLine' => 263,
            'startColumn' => 36,
            'endColumn' => 42,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'arg1' => 
          array (
            'name' => 'arg1',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 263,
            'endLine' => 263,
            'startColumn' => 45,
            'endColumn' => 49,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'arg2' => 
          array (
            'name' => 'arg2',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 263,
            'endLine' => 263,
            'startColumn' => 52,
            'endColumn' => 56,
            'parameterIndex' => 2,
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
        'docComment' => '/**
 * @param int  $option
 * @param int  $arg1
 * @param int  $arg2
 *
 * @return false
 */',
        'startLine' => 263,
        'endLine' => 266,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator',
        'declaringClassName' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator\\FileReadTrapStreamWrapper',
        'implementingClassName' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator\\FileReadTrapStreamWrapper',
        'currentClassName' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator\\FileReadTrapStreamWrapper',
        'aliasName' => NULL,
      ),
      'dir_opendir' => 
      array (
        'name' => 'dir_opendir',
        'parameters' => 
        array (
          'path' => 
          array (
            'name' => 'path',
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
            'startLine' => 268,
            'endLine' => 268,
            'startColumn' => 30,
            'endColumn' => 41,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'options' => 
          array (
            'name' => 'options',
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
            'startLine' => 268,
            'endLine' => 268,
            'startColumn' => 44,
            'endColumn' => 55,
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
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 268,
        'endLine' => 271,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator',
        'declaringClassName' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator\\FileReadTrapStreamWrapper',
        'implementingClassName' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator\\FileReadTrapStreamWrapper',
        'currentClassName' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator\\FileReadTrapStreamWrapper',
        'aliasName' => NULL,
      ),
      'dir_readdir' => 
      array (
        'name' => 'dir_readdir',
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
        'startLine' => 273,
        'endLine' => 276,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator',
        'declaringClassName' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator\\FileReadTrapStreamWrapper',
        'implementingClassName' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator\\FileReadTrapStreamWrapper',
        'currentClassName' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator\\FileReadTrapStreamWrapper',
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