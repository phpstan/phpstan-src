<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/src/Analyser/ResultCache/ResultCacheMetaExtension.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\Analyser\ResultCache\ResultCacheMetaExtension
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-1a9c2523c939a436bab9e59d4812650fc70e05ed3fa70f8535ba39f8a0cfd99e',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\Analyser\\ResultCache\\ResultCacheMetaExtension',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/src/Analyser/ResultCache/ResultCacheMetaExtension.php',
      ),
    ),
    'namespace' => 'PHPStan\\Analyser\\ResultCache',
    'name' => 'PHPStan\\Analyser\\ResultCache\\ResultCacheMetaExtension',
    'shortName' => 'ResultCacheMetaExtension',
    'isInterface' => true,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * ResultCacheMetaExtension can be used for extending PHPStan\'s built-in mechanism that is used for
 * calculating metadata for result cache. Using this extension you may add additional metadata that will
 * be used for determining if analysis must be run again, or can be re-used from result cache.
 *
 * @see https://github.com/phpstan/phpstan-symfony/issues/255 for the context.
 *
 * To register it in the configuration file use the `phpstan.resultCacheMetaExtension` service tag:
 *
 * ```
 * services:
 * 	-
 *		class: App\\PHPStan\\MyExtension
 *		tags:
 *			- phpstan.resultCacheMetaExtension
 * ```
 *
 * @api
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 24,
    'endLine' => 39,
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
        'declaringClassName' => 'PHPStan\\Analyser\\ResultCache\\ResultCacheMetaExtension',
        'implementingClassName' => 'PHPStan\\Analyser\\ResultCache\\ResultCacheMetaExtension',
        'name' => 'EXTENSION_TAG',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'phpstan.resultCacheMetaExtension\'',
          'attributes' => 
          array (
            'startLine' => 27,
            'endLine' => 27,
            'startTokenPos' => 32,
            'startFilePos' => 768,
            'endTokenPos' => 32,
            'endFilePos' => 801,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 27,
        'endLine' => 27,
        'startColumn' => 2,
        'endColumn' => 65,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'getKey' => 
      array (
        'name' => 'getKey',
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
        'docComment' => '/**
 * Returns unique key for this result cache meta entry. This describes the source of the metadata.
 */',
        'startLine' => 32,
        'endLine' => 32,
        'startColumn' => 2,
        'endColumn' => 34,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Analyser\\ResultCache',
        'declaringClassName' => 'PHPStan\\Analyser\\ResultCache\\ResultCacheMetaExtension',
        'implementingClassName' => 'PHPStan\\Analyser\\ResultCache\\ResultCacheMetaExtension',
        'currentClassName' => 'PHPStan\\Analyser\\ResultCache\\ResultCacheMetaExtension',
        'aliasName' => NULL,
      ),
      'getHash' => 
      array (
        'name' => 'getHash',
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
        'docComment' => '/**
 * Returns hash of the result cache meta entry. This represents the current state of the additional meta source.
 */',
        'startLine' => 37,
        'endLine' => 37,
        'startColumn' => 2,
        'endColumn' => 35,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Analyser\\ResultCache',
        'declaringClassName' => 'PHPStan\\Analyser\\ResultCache\\ResultCacheMetaExtension',
        'implementingClassName' => 'PHPStan\\Analyser\\ResultCache\\ResultCacheMetaExtension',
        'currentClassName' => 'PHPStan\\Analyser\\ResultCache\\ResultCacheMetaExtension',
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