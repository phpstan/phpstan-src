<?php declare(strict_types = 1);

// osfsl-/home/runner/work/phpstan-src/phpstan-src/vendor/composer/../hoa/compiler/./Llk/Llk.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Hoa\Compiler\Llk\Llk
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-7cafa1d8a8a81a14a70d3fa604f308ff207f0cd76296c9d97ae03ff7eebe6335-8.4.21-6.70.0.1',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Hoa\\Compiler\\Llk\\Llk',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/vendor/composer/../hoa/compiler/./Llk/Llk.php',
      ),
    ),
    'namespace' => 'Hoa\\Compiler\\Llk',
    'name' => 'Hoa\\Compiler\\Llk\\Llk',
    'shortName' => 'Llk',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 64,
    'docComment' => '/**
 * Class \\Hoa\\Compiler\\Llk.
 *
 * This class provides a set of static helpers to manipulate (load and save) a
 * compiler more easily.
 *
 * @copyright  Copyright © 2007-2017 Hoa community
 * @license    New BSD License
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 52,
    'endLine' => 374,
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
      'load' => 
      array (
        'name' => 'load',
        'parameters' => 
        array (
          'stream' => 
          array (
            'name' => 'stream',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Hoa\\Stream\\IStream\\In',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 63,
            'endLine' => 63,
            'startColumn' => 33,
            'endColumn' => 57,
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
 * Load in-memory parser from a grammar description file.
 * The grammar description language is PP. See
 * `hoa://Library/Compiler/Llk/Llk.pp` for an example, or the documentation.
 *
 * @param   \\Hoa\\Stream\\IStream\\In  $stream    Stream to read to grammar.
 * @return  \\Hoa\\Compiler\\Llk\\Parser
 * @throws  \\Hoa\\Compiler\\Exception
 */',
        'startLine' => 63,
        'endLine' => 92,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Hoa\\Compiler\\Llk',
        'declaringClassName' => 'Hoa\\Compiler\\Llk\\Llk',
        'implementingClassName' => 'Hoa\\Compiler\\Llk\\Llk',
        'currentClassName' => 'Hoa\\Compiler\\Llk\\Llk',
        'aliasName' => NULL,
      ),
      'save' => 
      array (
        'name' => 'save',
        'parameters' => 
        array (
          'parser' => 
          array (
            'name' => 'parser',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Hoa\\Compiler\\Llk\\Parser',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 104,
            'endLine' => 104,
            'startColumn' => 33,
            'endColumn' => 46,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'className' => 
          array (
            'name' => 'className',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 104,
            'endLine' => 104,
            'startColumn' => 49,
            'endColumn' => 58,
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
 * Save in-memory parser to PHP code.
 * The generated PHP code will load the same in-memory parser. The state
 * will be reset. The parser will be saved as a class, named after
 * `$className`. To retrieve the parser, one must instanciate this class.
 *
 * @param   \\Hoa\\Compiler\\Llk\\Parser  $parser       Parser to save.
 * @param   string                    $className    Parser classname.
 * @return  string
 */',
        'startLine' => 104,
        'endLine' => 250,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Hoa\\Compiler\\Llk',
        'declaringClassName' => 'Hoa\\Compiler\\Llk\\Llk',
        'implementingClassName' => 'Hoa\\Compiler\\Llk\\Llk',
        'currentClassName' => 'Hoa\\Compiler\\Llk\\Llk',
        'aliasName' => NULL,
      ),
      'parsePP' => 
      array (
        'name' => 'parsePP',
        'parameters' => 
        array (
          'pp' => 
          array (
            'name' => 'pp',
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
            'endColumn' => 38,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'tokens' => 
          array (
            'name' => 'tokens',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => true,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 263,
            'endLine' => 263,
            'startColumn' => 41,
            'endColumn' => 48,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'rules' => 
          array (
            'name' => 'rules',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => true,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 263,
            'endLine' => 263,
            'startColumn' => 51,
            'endColumn' => 57,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'pragmas' => 
          array (
            'name' => 'pragmas',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => true,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 263,
            'endLine' => 263,
            'startColumn' => 60,
            'endColumn' => 68,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
          'streamName' => 
          array (
            'name' => 'streamName',
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
            'startColumn' => 71,
            'endColumn' => 81,
            'parameterIndex' => 4,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Parse the grammar description language.
 *
 * @param   string  $pp            Grammar description.
 * @param   array   $tokens        Extracted tokens.
 * @param   array   $rules         Extracted raw rules.
 * @param   array   $pragmas       Extracted raw pragmas.
 * @param   string  $streamName    The name of the stream containing the grammar.
 * @return  void
 * @throws  \\Hoa\\Compiler\\Exception
 */',
        'startLine' => 263,
        'endLine' => 373,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Hoa\\Compiler\\Llk',
        'declaringClassName' => 'Hoa\\Compiler\\Llk\\Llk',
        'implementingClassName' => 'Hoa\\Compiler\\Llk\\Llk',
        'currentClassName' => 'Hoa\\Compiler\\Llk\\Llk',
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