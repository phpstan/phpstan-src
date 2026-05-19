<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/src/Rules/PhpDoc/PhpDocLineHelper.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\Rules\PhpDoc\PhpDocLineHelper
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-51fca07e4bded2411b043595b16f9e5f41f491e591c84915bce4def8652ac59a',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\Rules\\PhpDoc\\PhpDocLineHelper',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/src/Rules/PhpDoc/PhpDocLineHelper.php',
      ),
    ),
    'namespace' => 'PHPStan\\Rules\\PhpDoc',
    'name' => 'PHPStan\\Rules\\PhpDoc\\PhpDocLineHelper',
    'shortName' => 'PhpDocLineHelper',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => NULL,
    'attributes' => 
    array (
    ),
    'startLine' => 8,
    'endLine' => 28,
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
      'detectLine' => 
      array (
        'name' => 'detectLine',
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
            'startLine' => 16,
            'endLine' => 16,
            'startColumn' => 36,
            'endColumn' => 54,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'phpDocNode' => 
          array (
            'name' => 'phpDocNode',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\PhpDocParser\\Ast\\Node',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 16,
            'endLine' => 16,
            'startColumn' => 57,
            'endColumn' => 78,
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
            'name' => 'int',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * This method returns exact line of e.g. `@param` tag in PHPDoc so that it can be used for precise error reporting
 * - exact position is available only when bleedingEdge is enabled
 * - otherwise, it falls back to given node start line
 */',
        'startLine' => 16,
        'endLine' => 26,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'PHPStan\\Rules\\PhpDoc',
        'declaringClassName' => 'PHPStan\\Rules\\PhpDoc\\PhpDocLineHelper',
        'implementingClassName' => 'PHPStan\\Rules\\PhpDoc\\PhpDocLineHelper',
        'currentClassName' => 'PHPStan\\Rules\\PhpDoc\\PhpDocLineHelper',
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