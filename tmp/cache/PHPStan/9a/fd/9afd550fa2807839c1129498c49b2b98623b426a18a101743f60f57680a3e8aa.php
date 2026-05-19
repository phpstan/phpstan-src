<?php declare(strict_types = 1);

// osfsl-/home/runner/work/phpstan-src/phpstan-src/vendor/composer/../symfony/console/Input/InputInterface.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Symfony\Component\Console\Input\InputInterface
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-a34849fd5090bebc0e62918862d3a6ff301464bcccee02575f5f668ac880bb72-8.4.21-6.70.0.1',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Symfony\\Component\\Console\\Input\\InputInterface',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/vendor/composer/../symfony/console/Input/InputInterface.php',
      ),
    ),
    'namespace' => 'Symfony\\Component\\Console\\Input',
    'name' => 'Symfony\\Component\\Console\\Input\\InputInterface',
    'shortName' => 'InputInterface',
    'isInterface' => true,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * InputInterface is the interface implemented by all input classes.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 22,
    'endLine' => 151,
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
    ),
    'immediateMethods' => 
    array (
      'getFirstArgument' => 
      array (
        'name' => 'getFirstArgument',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Returns the first argument from the raw parameters (not parsed).
 *
 * @return string|null
 */',
        'startLine' => 29,
        'endLine' => 29,
        'startColumn' => 5,
        'endColumn' => 39,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Symfony\\Component\\Console\\Input',
        'declaringClassName' => 'Symfony\\Component\\Console\\Input\\InputInterface',
        'implementingClassName' => 'Symfony\\Component\\Console\\Input\\InputInterface',
        'currentClassName' => 'Symfony\\Component\\Console\\Input\\InputInterface',
        'aliasName' => NULL,
      ),
      'hasParameterOption' => 
      array (
        'name' => 'hasParameterOption',
        'parameters' => 
        array (
          'values' => 
          array (
            'name' => 'values',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 44,
            'endLine' => 44,
            'startColumn' => 40,
            'endColumn' => 46,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'onlyParams' => 
          array (
            'name' => 'onlyParams',
            'default' => 
            array (
              'code' => 'false',
              'attributes' => 
              array (
                'startLine' => 44,
                'endLine' => 44,
                'startTokenPos' => 55,
                'startFilePos' => 1409,
                'endTokenPos' => 55,
                'endFilePos' => 1413,
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
            'startLine' => 44,
            'endLine' => 44,
            'startColumn' => 49,
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
 * Returns true if the raw parameters (not parsed) contain a value.
 *
 * This method is to be used to introspect the input parameters
 * before they have been validated. It must be used carefully.
 * Does not necessarily return the correct result for short options
 * when multiple flags are combined in the same option.
 *
 * @param string|array $values     The values to look for in the raw parameters (can be an array)
 * @param bool         $onlyParams Only check real parameters, skip those following an end of options (--) signal
 *
 * @return bool
 */',
        'startLine' => 44,
        'endLine' => 44,
        'startColumn' => 5,
        'endColumn' => 74,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Symfony\\Component\\Console\\Input',
        'declaringClassName' => 'Symfony\\Component\\Console\\Input\\InputInterface',
        'implementingClassName' => 'Symfony\\Component\\Console\\Input\\InputInterface',
        'currentClassName' => 'Symfony\\Component\\Console\\Input\\InputInterface',
        'aliasName' => NULL,
      ),
      'getParameterOption' => 
      array (
        'name' => 'getParameterOption',
        'parameters' => 
        array (
          'values' => 
          array (
            'name' => 'values',
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
            'startColumn' => 40,
            'endColumn' => 46,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'default' => 
          array (
            'name' => 'default',
            'default' => 
            array (
              'code' => 'false',
              'attributes' => 
              array (
                'startLine' => 60,
                'endLine' => 60,
                'startTokenPos' => 74,
                'startFilePos' => 2228,
                'endTokenPos' => 74,
                'endFilePos' => 2232,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 60,
            'endLine' => 60,
            'startColumn' => 49,
            'endColumn' => 64,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
          'onlyParams' => 
          array (
            'name' => 'onlyParams',
            'default' => 
            array (
              'code' => 'false',
              'attributes' => 
              array (
                'startLine' => 60,
                'endLine' => 60,
                'startTokenPos' => 83,
                'startFilePos' => 2254,
                'endTokenPos' => 83,
                'endFilePos' => 2258,
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
            'startLine' => 60,
            'endLine' => 60,
            'startColumn' => 67,
            'endColumn' => 90,
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
 * Returns the value of a raw option (not parsed).
 *
 * This method is to be used to introspect the input parameters
 * before they have been validated. It must be used carefully.
 * Does not necessarily return the correct result for short options
 * when multiple flags are combined in the same option.
 *
 * @param string|array                     $values     The value(s) to look for in the raw parameters (can be an array)
 * @param string|bool|int|float|array|null $default    The default value to return if no result is found
 * @param bool                             $onlyParams Only check real parameters, skip those following an end of options (--) signal
 *
 * @return mixed
 */',
        'startLine' => 60,
        'endLine' => 60,
        'startColumn' => 5,
        'endColumn' => 92,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Symfony\\Component\\Console\\Input',
        'declaringClassName' => 'Symfony\\Component\\Console\\Input\\InputInterface',
        'implementingClassName' => 'Symfony\\Component\\Console\\Input\\InputInterface',
        'currentClassName' => 'Symfony\\Component\\Console\\Input\\InputInterface',
        'aliasName' => NULL,
      ),
      'bind' => 
      array (
        'name' => 'bind',
        'parameters' => 
        array (
          'definition' => 
          array (
            'name' => 'definition',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Symfony\\Component\\Console\\Input\\InputDefinition',
                'isIdentifier' => false,
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
            'startColumn' => 26,
            'endColumn' => 52,
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
 * Binds the current Input instance with the given arguments and options.
 *
 * @throws RuntimeException
 */',
        'startLine' => 67,
        'endLine' => 67,
        'startColumn' => 5,
        'endColumn' => 54,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Symfony\\Component\\Console\\Input',
        'declaringClassName' => 'Symfony\\Component\\Console\\Input\\InputInterface',
        'implementingClassName' => 'Symfony\\Component\\Console\\Input\\InputInterface',
        'currentClassName' => 'Symfony\\Component\\Console\\Input\\InputInterface',
        'aliasName' => NULL,
      ),
      'validate' => 
      array (
        'name' => 'validate',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Validates the input.
 *
 * @throws RuntimeException When not enough arguments are given
 */',
        'startLine' => 74,
        'endLine' => 74,
        'startColumn' => 5,
        'endColumn' => 31,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Symfony\\Component\\Console\\Input',
        'declaringClassName' => 'Symfony\\Component\\Console\\Input\\InputInterface',
        'implementingClassName' => 'Symfony\\Component\\Console\\Input\\InputInterface',
        'currentClassName' => 'Symfony\\Component\\Console\\Input\\InputInterface',
        'aliasName' => NULL,
      ),
      'getArguments' => 
      array (
        'name' => 'getArguments',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Returns all the given arguments merged with the default values.
 *
 * @return array<string|bool|int|float|array|null>
 */',
        'startLine' => 81,
        'endLine' => 81,
        'startColumn' => 5,
        'endColumn' => 35,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Symfony\\Component\\Console\\Input',
        'declaringClassName' => 'Symfony\\Component\\Console\\Input\\InputInterface',
        'implementingClassName' => 'Symfony\\Component\\Console\\Input\\InputInterface',
        'currentClassName' => 'Symfony\\Component\\Console\\Input\\InputInterface',
        'aliasName' => NULL,
      ),
      'getArgument' => 
      array (
        'name' => 'getArgument',
        'parameters' => 
        array (
          'name' => 
          array (
            'name' => 'name',
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
            'startLine' => 90,
            'endLine' => 90,
            'startColumn' => 33,
            'endColumn' => 44,
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
 * Returns the argument value for a given argument name.
 *
 * @return mixed
 *
 * @throws InvalidArgumentException When argument given doesn\'t exist
 */',
        'startLine' => 90,
        'endLine' => 90,
        'startColumn' => 5,
        'endColumn' => 46,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Symfony\\Component\\Console\\Input',
        'declaringClassName' => 'Symfony\\Component\\Console\\Input\\InputInterface',
        'implementingClassName' => 'Symfony\\Component\\Console\\Input\\InputInterface',
        'currentClassName' => 'Symfony\\Component\\Console\\Input\\InputInterface',
        'aliasName' => NULL,
      ),
      'setArgument' => 
      array (
        'name' => 'setArgument',
        'parameters' => 
        array (
          'name' => 
          array (
            'name' => 'name',
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
            'startLine' => 99,
            'endLine' => 99,
            'startColumn' => 33,
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
            'startLine' => 99,
            'endLine' => 99,
            'startColumn' => 47,
            'endColumn' => 52,
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
 * Sets an argument value by name.
 *
 * @param mixed $value The argument value
 *
 * @throws InvalidArgumentException When argument given doesn\'t exist
 */',
        'startLine' => 99,
        'endLine' => 99,
        'startColumn' => 5,
        'endColumn' => 54,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Symfony\\Component\\Console\\Input',
        'declaringClassName' => 'Symfony\\Component\\Console\\Input\\InputInterface',
        'implementingClassName' => 'Symfony\\Component\\Console\\Input\\InputInterface',
        'currentClassName' => 'Symfony\\Component\\Console\\Input\\InputInterface',
        'aliasName' => NULL,
      ),
      'hasArgument' => 
      array (
        'name' => 'hasArgument',
        'parameters' => 
        array (
          'name' => 
          array (
            'name' => 'name',
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
            'startLine' => 106,
            'endLine' => 106,
            'startColumn' => 33,
            'endColumn' => 44,
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
 * Returns true if an InputArgument object exists by name or position.
 *
 * @return bool
 */',
        'startLine' => 106,
        'endLine' => 106,
        'startColumn' => 5,
        'endColumn' => 46,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Symfony\\Component\\Console\\Input',
        'declaringClassName' => 'Symfony\\Component\\Console\\Input\\InputInterface',
        'implementingClassName' => 'Symfony\\Component\\Console\\Input\\InputInterface',
        'currentClassName' => 'Symfony\\Component\\Console\\Input\\InputInterface',
        'aliasName' => NULL,
      ),
      'getOptions' => 
      array (
        'name' => 'getOptions',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Returns all the given options merged with the default values.
 *
 * @return array<string|bool|int|float|array|null>
 */',
        'startLine' => 113,
        'endLine' => 113,
        'startColumn' => 5,
        'endColumn' => 33,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Symfony\\Component\\Console\\Input',
        'declaringClassName' => 'Symfony\\Component\\Console\\Input\\InputInterface',
        'implementingClassName' => 'Symfony\\Component\\Console\\Input\\InputInterface',
        'currentClassName' => 'Symfony\\Component\\Console\\Input\\InputInterface',
        'aliasName' => NULL,
      ),
      'getOption' => 
      array (
        'name' => 'getOption',
        'parameters' => 
        array (
          'name' => 
          array (
            'name' => 'name',
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
            'startLine' => 122,
            'endLine' => 122,
            'startColumn' => 31,
            'endColumn' => 42,
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
 * Returns the option value for a given option name.
 *
 * @return mixed
 *
 * @throws InvalidArgumentException When option given doesn\'t exist
 */',
        'startLine' => 122,
        'endLine' => 122,
        'startColumn' => 5,
        'endColumn' => 44,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Symfony\\Component\\Console\\Input',
        'declaringClassName' => 'Symfony\\Component\\Console\\Input\\InputInterface',
        'implementingClassName' => 'Symfony\\Component\\Console\\Input\\InputInterface',
        'currentClassName' => 'Symfony\\Component\\Console\\Input\\InputInterface',
        'aliasName' => NULL,
      ),
      'setOption' => 
      array (
        'name' => 'setOption',
        'parameters' => 
        array (
          'name' => 
          array (
            'name' => 'name',
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
            'startLine' => 131,
            'endLine' => 131,
            'startColumn' => 31,
            'endColumn' => 42,
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
            'startLine' => 131,
            'endLine' => 131,
            'startColumn' => 45,
            'endColumn' => 50,
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
 * Sets an option value by name.
 *
 * @param mixed $value The option value
 *
 * @throws InvalidArgumentException When option given doesn\'t exist
 */',
        'startLine' => 131,
        'endLine' => 131,
        'startColumn' => 5,
        'endColumn' => 52,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Symfony\\Component\\Console\\Input',
        'declaringClassName' => 'Symfony\\Component\\Console\\Input\\InputInterface',
        'implementingClassName' => 'Symfony\\Component\\Console\\Input\\InputInterface',
        'currentClassName' => 'Symfony\\Component\\Console\\Input\\InputInterface',
        'aliasName' => NULL,
      ),
      'hasOption' => 
      array (
        'name' => 'hasOption',
        'parameters' => 
        array (
          'name' => 
          array (
            'name' => 'name',
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
            'startLine' => 138,
            'endLine' => 138,
            'startColumn' => 31,
            'endColumn' => 42,
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
 * Returns true if an InputOption object exists by name.
 *
 * @return bool
 */',
        'startLine' => 138,
        'endLine' => 138,
        'startColumn' => 5,
        'endColumn' => 44,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Symfony\\Component\\Console\\Input',
        'declaringClassName' => 'Symfony\\Component\\Console\\Input\\InputInterface',
        'implementingClassName' => 'Symfony\\Component\\Console\\Input\\InputInterface',
        'currentClassName' => 'Symfony\\Component\\Console\\Input\\InputInterface',
        'aliasName' => NULL,
      ),
      'isInteractive' => 
      array (
        'name' => 'isInteractive',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Is this input means interactive?
 *
 * @return bool
 */',
        'startLine' => 145,
        'endLine' => 145,
        'startColumn' => 5,
        'endColumn' => 36,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Symfony\\Component\\Console\\Input',
        'declaringClassName' => 'Symfony\\Component\\Console\\Input\\InputInterface',
        'implementingClassName' => 'Symfony\\Component\\Console\\Input\\InputInterface',
        'currentClassName' => 'Symfony\\Component\\Console\\Input\\InputInterface',
        'aliasName' => NULL,
      ),
      'setInteractive' => 
      array (
        'name' => 'setInteractive',
        'parameters' => 
        array (
          'interactive' => 
          array (
            'name' => 'interactive',
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
            'startLine' => 150,
            'endLine' => 150,
            'startColumn' => 36,
            'endColumn' => 52,
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
 * Sets the input interactivity.
 */',
        'startLine' => 150,
        'endLine' => 150,
        'startColumn' => 5,
        'endColumn' => 54,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Symfony\\Component\\Console\\Input',
        'declaringClassName' => 'Symfony\\Component\\Console\\Input\\InputInterface',
        'implementingClassName' => 'Symfony\\Component\\Console\\Input\\InputInterface',
        'currentClassName' => 'Symfony\\Component\\Console\\Input\\InputInterface',
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