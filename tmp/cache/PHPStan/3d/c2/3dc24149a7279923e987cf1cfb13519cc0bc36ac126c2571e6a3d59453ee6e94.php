<?php declare(strict_types = 1);

// osfsl-/home/runner/work/phpstan-src/phpstan-src/vendor/composer/../symfony/console/Formatter/OutputFormatterInterface.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Symfony\Component\Console\Formatter\OutputFormatterInterface
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-39158d586c9a3e3f1b12223ba48858078baaa347d071ed03e02f25f5c6a94d35-8.4.21-6.70.0.1',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Symfony\\Component\\Console\\Formatter\\OutputFormatterInterface',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/vendor/composer/../symfony/console/Formatter/OutputFormatterInterface.php',
      ),
    ),
    'namespace' => 'Symfony\\Component\\Console\\Formatter',
    'name' => 'Symfony\\Component\\Console\\Formatter\\OutputFormatterInterface',
    'shortName' => 'OutputFormatterInterface',
    'isInterface' => true,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Formatter interface for console output.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 19,
    'endLine' => 60,
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
            'startLine' => 24,
            'endLine' => 24,
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
 * Sets the decorated flag.
 */',
        'startLine' => 24,
        'endLine' => 24,
        'startColumn' => 5,
        'endColumn' => 50,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Symfony\\Component\\Console\\Formatter',
        'declaringClassName' => 'Symfony\\Component\\Console\\Formatter\\OutputFormatterInterface',
        'implementingClassName' => 'Symfony\\Component\\Console\\Formatter\\OutputFormatterInterface',
        'currentClassName' => 'Symfony\\Component\\Console\\Formatter\\OutputFormatterInterface',
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
 * Whether the output will decorate messages.
 *
 * @return bool
 */',
        'startLine' => 31,
        'endLine' => 31,
        'startColumn' => 5,
        'endColumn' => 34,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Symfony\\Component\\Console\\Formatter',
        'declaringClassName' => 'Symfony\\Component\\Console\\Formatter\\OutputFormatterInterface',
        'implementingClassName' => 'Symfony\\Component\\Console\\Formatter\\OutputFormatterInterface',
        'currentClassName' => 'Symfony\\Component\\Console\\Formatter\\OutputFormatterInterface',
        'aliasName' => NULL,
      ),
      'setStyle' => 
      array (
        'name' => 'setStyle',
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
            'startLine' => 36,
            'endLine' => 36,
            'startColumn' => 30,
            'endColumn' => 41,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'style' => 
          array (
            'name' => 'style',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Symfony\\Component\\Console\\Formatter\\OutputFormatterStyleInterface',
                'isIdentifier' => false,
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
            'startColumn' => 44,
            'endColumn' => 79,
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
 * Sets a new style.
 */',
        'startLine' => 36,
        'endLine' => 36,
        'startColumn' => 5,
        'endColumn' => 81,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Symfony\\Component\\Console\\Formatter',
        'declaringClassName' => 'Symfony\\Component\\Console\\Formatter\\OutputFormatterInterface',
        'implementingClassName' => 'Symfony\\Component\\Console\\Formatter\\OutputFormatterInterface',
        'currentClassName' => 'Symfony\\Component\\Console\\Formatter\\OutputFormatterInterface',
        'aliasName' => NULL,
      ),
      'hasStyle' => 
      array (
        'name' => 'hasStyle',
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
            'startLine' => 43,
            'endLine' => 43,
            'startColumn' => 30,
            'endColumn' => 41,
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
 * Checks if output formatter has style with specified name.
 *
 * @return bool
 */',
        'startLine' => 43,
        'endLine' => 43,
        'startColumn' => 5,
        'endColumn' => 43,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Symfony\\Component\\Console\\Formatter',
        'declaringClassName' => 'Symfony\\Component\\Console\\Formatter\\OutputFormatterInterface',
        'implementingClassName' => 'Symfony\\Component\\Console\\Formatter\\OutputFormatterInterface',
        'currentClassName' => 'Symfony\\Component\\Console\\Formatter\\OutputFormatterInterface',
        'aliasName' => NULL,
      ),
      'getStyle' => 
      array (
        'name' => 'getStyle',
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
            'startLine' => 52,
            'endLine' => 52,
            'startColumn' => 30,
            'endColumn' => 41,
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
 * Gets style options from style with specified name.
 *
 * @return OutputFormatterStyleInterface
 *
 * @throws \\InvalidArgumentException When style isn\'t defined
 */',
        'startLine' => 52,
        'endLine' => 52,
        'startColumn' => 5,
        'endColumn' => 43,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Symfony\\Component\\Console\\Formatter',
        'declaringClassName' => 'Symfony\\Component\\Console\\Formatter\\OutputFormatterInterface',
        'implementingClassName' => 'Symfony\\Component\\Console\\Formatter\\OutputFormatterInterface',
        'currentClassName' => 'Symfony\\Component\\Console\\Formatter\\OutputFormatterInterface',
        'aliasName' => NULL,
      ),
      'format' => 
      array (
        'name' => 'format',
        'parameters' => 
        array (
          'message' => 
          array (
            'name' => 'message',
            'default' => NULL,
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
                      'name' => 'string',
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
            'startLine' => 59,
            'endLine' => 59,
            'startColumn' => 28,
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
 * Formats a message according to the given styles.
 *
 * @return string|null
 */',
        'startLine' => 59,
        'endLine' => 59,
        'startColumn' => 5,
        'endColumn' => 45,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Symfony\\Component\\Console\\Formatter',
        'declaringClassName' => 'Symfony\\Component\\Console\\Formatter\\OutputFormatterInterface',
        'implementingClassName' => 'Symfony\\Component\\Console\\Formatter\\OutputFormatterInterface',
        'currentClassName' => 'Symfony\\Component\\Console\\Formatter\\OutputFormatterInterface',
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