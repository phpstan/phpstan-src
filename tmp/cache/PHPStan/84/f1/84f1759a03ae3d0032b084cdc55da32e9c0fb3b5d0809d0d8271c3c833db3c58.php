<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/src/Analyser/ResultCache/ResultCacheClearer.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\Analyser\ResultCache\ResultCacheClearer
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-0df8b5b8eb03cc69761bf6e8acc161139f55046bdedeba10e4bfc9f993a86a28',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\Analyser\\ResultCache\\ResultCacheClearer',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/src/Analyser/ResultCache/ResultCacheClearer.php',
      ),
    ),
    'namespace' => 'PHPStan\\Analyser\\ResultCache',
    'name' => 'PHPStan\\Analyser\\ResultCache\\ResultCacheClearer',
    'shortName' => 'ResultCacheClearer',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => NULL,
    'attributes' => 
    array (
      0 => 
      array (
        'name' => 'PHPStan\\DependencyInjection\\AutowiredService',
        'isRepeated' => false,
        'arguments' => 
        array (
        ),
      ),
    ),
    'startLine' => 11,
    'endLine' => 34,
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
      'cacheFilePath' => 
      array (
        'declaringClassName' => 'PHPStan\\Analyser\\ResultCache\\ResultCacheClearer',
        'implementingClassName' => 'PHPStan\\Analyser\\ResultCache\\ResultCacheClearer',
        'name' => 'cacheFilePath',
        'modifiers' => 4,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
          0 => 
          array (
            'name' => 'PHPStan\\DependencyInjection\\AutowiredParameter',
            'isRepeated' => false,
            'arguments' => 
            array (
              'ref' => 
              array (
                'code' => '\'%resultCachePath%\'',
                'attributes' => 
                array (
                  'startLine' => 16,
                  'endLine' => 16,
                  'startTokenPos' => 72,
                  'startFilePos' => 355,
                  'endTokenPos' => 72,
                  'endFilePos' => 373,
                ),
              ),
            ),
          ),
        ),
        'startLine' => 16,
        'endLine' => 17,
        'startColumn' => 3,
        'endColumn' => 31,
        'isPromoted' => true,
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
          'cacheFilePath' => 
          array (
            'name' => 'cacheFilePath',
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
            'isPromoted' => true,
            'attributes' => 
            array (
              0 => 
              array (
                'name' => 'PHPStan\\DependencyInjection\\AutowiredParameter',
                'isRepeated' => false,
                'arguments' => 
                array (
                  'ref' => 
                  array (
                    'code' => '\'%resultCachePath%\'',
                    'attributes' => 
                    array (
                      'startLine' => 16,
                      'endLine' => 16,
                      'startTokenPos' => 72,
                      'startFilePos' => 355,
                      'endTokenPos' => 72,
                      'endFilePos' => 373,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 16,
            'endLine' => 17,
            'startColumn' => 3,
            'endColumn' => 31,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 15,
        'endLine' => 20,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Analyser\\ResultCache',
        'declaringClassName' => 'PHPStan\\Analyser\\ResultCache\\ResultCacheClearer',
        'implementingClassName' => 'PHPStan\\Analyser\\ResultCache\\ResultCacheClearer',
        'currentClassName' => 'PHPStan\\Analyser\\ResultCache\\ResultCacheClearer',
        'aliasName' => NULL,
      ),
      'clear' => 
      array (
        'name' => 'clear',
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
        'startLine' => 22,
        'endLine' => 32,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Analyser\\ResultCache',
        'declaringClassName' => 'PHPStan\\Analyser\\ResultCache\\ResultCacheClearer',
        'implementingClassName' => 'PHPStan\\Analyser\\ResultCache\\ResultCacheClearer',
        'currentClassName' => 'PHPStan\\Analyser\\ResultCache\\ResultCacheClearer',
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