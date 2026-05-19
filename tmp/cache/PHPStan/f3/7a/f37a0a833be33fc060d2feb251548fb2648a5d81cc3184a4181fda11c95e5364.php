<?php declare(strict_types = 1);

// osfsl-/home/runner/work/phpstan-src/phpstan-src/vendor/composer/../symfony/console/Input/StringInput.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Symfony\Component\Console\Input\StringInput
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-bc7aa333e78acf19afbe79b9af82c477e047ea5b85069b9fce6d36a496ff18a3-8.4.21-6.70.0.1',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Symfony\\Component\\Console\\Input\\StringInput',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/vendor/composer/../symfony/console/Input/StringInput.php',
      ),
    ),
    'namespace' => 'Symfony\\Component\\Console\\Input',
    'name' => 'Symfony\\Component\\Console\\Input\\StringInput',
    'shortName' => 'StringInput',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * StringInput represents an input provided as a string.
 *
 * Usage:
 *
 *     $input = new StringInput(\'foo --bar="foobar"\');
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 25,
    'endLine' => 84,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'Symfony\\Component\\Console\\Input\\ArgvInput',
    'implementsClassNames' => 
    array (
    ),
    'traitClassNames' => 
    array (
    ),
    'immediateConstants' => 
    array (
      'REGEX_STRING' => 
      array (
        'declaringClassName' => 'Symfony\\Component\\Console\\Input\\StringInput',
        'implementingClassName' => 'Symfony\\Component\\Console\\Input\\StringInput',
        'name' => 'REGEX_STRING',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'([^\\s]+?)(?:\\s|(?<!\\\\\\\\)"|(?<!\\\\\\\\)\\\'|$)\'',
          'attributes' => 
          array (
            'startLine' => 27,
            'endLine' => 27,
            'startTokenPos' => 34,
            'startFilePos' => 607,
            'endTokenPos' => 34,
            'endFilePos' => 648,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 27,
        'endLine' => 27,
        'startColumn' => 5,
        'endColumn' => 75,
      ),
      'REGEX_UNQUOTED_STRING' => 
      array (
        'declaringClassName' => 'Symfony\\Component\\Console\\Input\\StringInput',
        'implementingClassName' => 'Symfony\\Component\\Console\\Input\\StringInput',
        'name' => 'REGEX_UNQUOTED_STRING',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'([^\\s\\\\\\\\]+?)\'',
          'attributes' => 
          array (
            'startLine' => 28,
            'endLine' => 28,
            'startTokenPos' => 45,
            'startFilePos' => 692,
            'endTokenPos' => 45,
            'endFilePos' => 706,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 28,
        'endLine' => 28,
        'startColumn' => 5,
        'endColumn' => 57,
      ),
      'REGEX_QUOTED_STRING' => 
      array (
        'declaringClassName' => 'Symfony\\Component\\Console\\Input\\StringInput',
        'implementingClassName' => 'Symfony\\Component\\Console\\Input\\StringInput',
        'name' => 'REGEX_QUOTED_STRING',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'(?:"([^"\\\\\\\\]*(?:\\\\\\\\.[^"\\\\\\\\]*)*)"|\\\'([^\\\'\\\\\\\\]*(?:\\\\\\\\.[^\\\'\\\\\\\\]*)*)\\\')\'',
          'attributes' => 
          array (
            'startLine' => 29,
            'endLine' => 29,
            'startTokenPos' => 56,
            'startFilePos' => 748,
            'endTokenPos' => 56,
            'endFilePos' => 822,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 29,
        'endLine' => 29,
        'startColumn' => 5,
        'endColumn' => 115,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      '__construct' => 
      array (
        'name' => '__construct',
        'parameters' => 
        array (
          'input' => 
          array (
            'name' => 'input',
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
            'startLine' => 34,
            'endLine' => 34,
            'startColumn' => 33,
            'endColumn' => 45,
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
 * @param string $input A string representing the parameters from the CLI
 */',
        'startLine' => 34,
        'endLine' => 39,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Symfony\\Component\\Console\\Input',
        'declaringClassName' => 'Symfony\\Component\\Console\\Input\\StringInput',
        'implementingClassName' => 'Symfony\\Component\\Console\\Input\\StringInput',
        'currentClassName' => 'Symfony\\Component\\Console\\Input\\StringInput',
        'aliasName' => NULL,
      ),
      'tokenize' => 
      array (
        'name' => 'tokenize',
        'parameters' => 
        array (
          'input' => 
          array (
            'name' => 'input',
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
            'startLine' => 46,
            'endLine' => 46,
            'startColumn' => 31,
            'endColumn' => 43,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Tokenizes a string.
 *
 * @throws InvalidArgumentException When unable to parse input (should never happen)
 */',
        'startLine' => 46,
        'endLine' => 83,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'Symfony\\Component\\Console\\Input',
        'declaringClassName' => 'Symfony\\Component\\Console\\Input\\StringInput',
        'implementingClassName' => 'Symfony\\Component\\Console\\Input\\StringInput',
        'currentClassName' => 'Symfony\\Component\\Console\\Input\\StringInput',
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