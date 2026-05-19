<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/src/PhpDoc/StubFilesExtension.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\PhpDoc\StubFilesExtension
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-ec57e2b85e2f5258d006ffcf771cb58964df5a18d8617d9913b13d8e3a902469',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\PhpDoc\\StubFilesExtension',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/src/PhpDoc/StubFilesExtension.php',
      ),
    ),
    'namespace' => 'PHPStan\\PhpDoc',
    'name' => 'PHPStan\\PhpDoc\\StubFilesExtension',
    'shortName' => 'StubFilesExtension',
    'isInterface' => true,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * This is the extension interface to implement if you want to dynamically
 * load stub files based on your logic. As opposed to simply list them in the configuration file.
 *
 * To register it in the configuration file use the `phpstan.stubFilesExtension` service tag:
 *
 * ```
 * services:
 * 	-
 *		class: App\\PHPStan\\MyExtension
 *		tags:
 *			- phpstan.stubFilesExtension
 * ```
 *
 * @api
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 21,
    'endLine' => 29,
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
      'EXTENSION_TAG' => 
      array (
        'declaringClassName' => 'PHPStan\\PhpDoc\\StubFilesExtension',
        'implementingClassName' => 'PHPStan\\PhpDoc\\StubFilesExtension',
        'name' => 'EXTENSION_TAG',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'phpstan.stubFilesExtension\'',
          'attributes' => 
          array (
            'startLine' => 24,
            'endLine' => 24,
            'startTokenPos' => 32,
            'startFilePos' => 527,
            'endTokenPos' => 32,
            'endFilePos' => 554,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 24,
        'endLine' => 24,
        'startColumn' => 2,
        'endColumn' => 59,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'getFiles' => 
      array (
        'name' => 'getFiles',
        'parameters' => 
        array (
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
        'docComment' => '/** @return string[] */',
        'startLine' => 27,
        'endLine' => 27,
        'startColumn' => 2,
        'endColumn' => 35,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\PhpDoc',
        'declaringClassName' => 'PHPStan\\PhpDoc\\StubFilesExtension',
        'implementingClassName' => 'PHPStan\\PhpDoc\\StubFilesExtension',
        'currentClassName' => 'PHPStan\\PhpDoc\\StubFilesExtension',
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