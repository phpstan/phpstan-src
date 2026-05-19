<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/src/Reflection/BetterReflection/SourceLocator/FileNodesFetcher.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\Reflection\BetterReflection\SourceLocator\FileNodesFetcher
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-136d52b09e1d4b0d5dc56fb097dee4ee3e75029a270f18a5202f39b87375c152',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator\\FileNodesFetcher',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/src/Reflection/BetterReflection/SourceLocator/FileNodesFetcher.php',
      ),
    ),
    'namespace' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator',
    'name' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator\\FileNodesFetcher',
    'shortName' => 'FileNodesFetcher',
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
    'startLine' => 12,
    'endLine' => 50,
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
      'cachingVisitor' => 
      array (
        'declaringClassName' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator\\FileNodesFetcher',
        'implementingClassName' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator\\FileNodesFetcher',
        'name' => 'cachingVisitor',
        'modifiers' => 4,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator\\CachingVisitor',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 17,
        'endLine' => 17,
        'startColumn' => 3,
        'endColumn' => 40,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'parser' => 
      array (
        'declaringClassName' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator\\FileNodesFetcher',
        'implementingClassName' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator\\FileNodesFetcher',
        'name' => 'parser',
        'modifiers' => 4,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPStan\\Parser\\Parser',
            'isIdentifier' => false,
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
                'code' => '\'@defaultAnalysisParser\'',
                'attributes' => 
                array (
                  'startLine' => 18,
                  'endLine' => 18,
                  'startTokenPos' => 78,
                  'startFilePos' => 478,
                  'endTokenPos' => 78,
                  'endFilePos' => 501,
                ),
              ),
            ),
          ),
        ),
        'startLine' => 18,
        'endLine' => 19,
        'startColumn' => 3,
        'endColumn' => 24,
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
          'cachingVisitor' => 
          array (
            'name' => 'cachingVisitor',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator\\CachingVisitor',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 17,
            'endLine' => 17,
            'startColumn' => 3,
            'endColumn' => 40,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'parser' => 
          array (
            'name' => 'parser',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\Parser\\Parser',
                'isIdentifier' => false,
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
                    'code' => '\'@defaultAnalysisParser\'',
                    'attributes' => 
                    array (
                      'startLine' => 18,
                      'endLine' => 18,
                      'startTokenPos' => 78,
                      'startFilePos' => 478,
                      'endTokenPos' => 78,
                      'endFilePos' => 501,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 18,
            'endLine' => 19,
            'startColumn' => 3,
            'endColumn' => 24,
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
        'startLine' => 16,
        'endLine' => 22,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator',
        'declaringClassName' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator\\FileNodesFetcher',
        'implementingClassName' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator\\FileNodesFetcher',
        'currentClassName' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator\\FileNodesFetcher',
        'aliasName' => NULL,
      ),
      'fetchNodes' => 
      array (
        'name' => 'fetchNodes',
        'parameters' => 
        array (
          'fileName' => 
          array (
            'name' => 'fileName',
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
            'startLine' => 24,
            'endLine' => 24,
            'startColumn' => 29,
            'endColumn' => 44,
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
            'name' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator\\FetchedNodesResult',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 24,
        'endLine' => 48,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator',
        'declaringClassName' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator\\FileNodesFetcher',
        'implementingClassName' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator\\FileNodesFetcher',
        'currentClassName' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator\\FileNodesFetcher',
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