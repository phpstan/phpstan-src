<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/src/File/FileMonitor.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\File\FileMonitor
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-4f17cdaa61580038c8a5bcb73915d697c41a458a2c02de75cf2b53f694a5dbbf',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\File\\FileMonitor',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/src/File/FileMonitor.php',
      ),
    ),
    'namespace' => 'PHPStan\\File',
    'name' => 'PHPStan\\File\\FileMonitor',
    'shortName' => 'FileMonitor',
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
    'startLine' => 17,
    'endLine' => 147,
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
      'fileHashes' => 
      array (
        'declaringClassName' => 'PHPStan\\File\\FileMonitor',
        'implementingClassName' => 'PHPStan\\File\\FileMonitor',
        'name' => 'fileHashes',
        'modifiers' => 4,
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
        'default' => 
        array (
          'code' => 'null',
          'attributes' => 
          array (
            'startLine' => 22,
            'endLine' => 22,
            'startTokenPos' => 110,
            'startFilePos' => 518,
            'endTokenPos' => 110,
            'endFilePos' => 521,
          ),
        ),
        'docComment' => '/** @var array<string, string>|null */',
        'attributes' => 
        array (
        ),
        'startLine' => 22,
        'endLine' => 22,
        'startColumn' => 2,
        'endColumn' => 35,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'filePaths' => 
      array (
        'declaringClassName' => 'PHPStan\\File\\FileMonitor',
        'implementingClassName' => 'PHPStan\\File\\FileMonitor',
        'name' => 'filePaths',
        'modifiers' => 4,
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
        'default' => 
        array (
          'code' => 'null',
          'attributes' => 
          array (
            'startLine' => 25,
            'endLine' => 25,
            'startTokenPos' => 124,
            'startFilePos' => 586,
            'endTokenPos' => 124,
            'endFilePos' => 589,
          ),
        ),
        'docComment' => '/** @var array<string>|null */',
        'attributes' => 
        array (
        ),
        'startLine' => 25,
        'endLine' => 25,
        'startColumn' => 2,
        'endColumn' => 34,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'analyseFileFinder' => 
      array (
        'declaringClassName' => 'PHPStan\\File\\FileMonitor',
        'implementingClassName' => 'PHPStan\\File\\FileMonitor',
        'name' => 'analyseFileFinder',
        'modifiers' => 4,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPStan\\File\\FileFinder',
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
                'code' => '\'@fileFinderAnalyse\'',
                'attributes' => 
                array (
                  'startLine' => 34,
                  'endLine' => 34,
                  'startTokenPos' => 142,
                  'startFilePos' => 809,
                  'endTokenPos' => 142,
                  'endFilePos' => 828,
                ),
              ),
            ),
          ),
        ),
        'startLine' => 34,
        'endLine' => 35,
        'startColumn' => 3,
        'endColumn' => 39,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'scanFileFinder' => 
      array (
        'declaringClassName' => 'PHPStan\\File\\FileMonitor',
        'implementingClassName' => 'PHPStan\\File\\FileMonitor',
        'name' => 'scanFileFinder',
        'modifiers' => 4,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPStan\\File\\FileFinder',
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
                'code' => '\'@fileFinderScan\'',
                'attributes' => 
                array (
                  'startLine' => 36,
                  'endLine' => 36,
                  'startTokenPos' => 159,
                  'startFilePos' => 901,
                  'endTokenPos' => 159,
                  'endFilePos' => 917,
                ),
              ),
            ),
          ),
        ),
        'startLine' => 36,
        'endLine' => 37,
        'startColumn' => 3,
        'endColumn' => 36,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'analysedPaths' => 
      array (
        'declaringClassName' => 'PHPStan\\File\\FileMonitor',
        'implementingClassName' => 'PHPStan\\File\\FileMonitor',
        'name' => 'analysedPaths',
        'modifiers' => 4,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'array',
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
            ),
          ),
        ),
        'startLine' => 38,
        'endLine' => 39,
        'startColumn' => 3,
        'endColumn' => 30,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'analysedPathsFromConfig' => 
      array (
        'declaringClassName' => 'PHPStan\\File\\FileMonitor',
        'implementingClassName' => 'PHPStan\\File\\FileMonitor',
        'name' => 'analysedPathsFromConfig',
        'modifiers' => 4,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'array',
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
            ),
          ),
        ),
        'startLine' => 40,
        'endLine' => 41,
        'startColumn' => 3,
        'endColumn' => 40,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'scanFiles' => 
      array (
        'declaringClassName' => 'PHPStan\\File\\FileMonitor',
        'implementingClassName' => 'PHPStan\\File\\FileMonitor',
        'name' => 'scanFiles',
        'modifiers' => 4,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'array',
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
            ),
          ),
        ),
        'startLine' => 42,
        'endLine' => 43,
        'startColumn' => 3,
        'endColumn' => 26,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'scanDirectories' => 
      array (
        'declaringClassName' => 'PHPStan\\File\\FileMonitor',
        'implementingClassName' => 'PHPStan\\File\\FileMonitor',
        'name' => 'scanDirectories',
        'modifiers' => 4,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'array',
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
            ),
          ),
        ),
        'startLine' => 44,
        'endLine' => 45,
        'startColumn' => 3,
        'endColumn' => 32,
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
          'analyseFileFinder' => 
          array (
            'name' => 'analyseFileFinder',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\File\\FileFinder',
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
                    'code' => '\'@fileFinderAnalyse\'',
                    'attributes' => 
                    array (
                      'startLine' => 34,
                      'endLine' => 34,
                      'startTokenPos' => 142,
                      'startFilePos' => 809,
                      'endTokenPos' => 142,
                      'endFilePos' => 828,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 34,
            'endLine' => 35,
            'startColumn' => 3,
            'endColumn' => 39,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'scanFileFinder' => 
          array (
            'name' => 'scanFileFinder',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\File\\FileFinder',
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
                    'code' => '\'@fileFinderScan\'',
                    'attributes' => 
                    array (
                      'startLine' => 36,
                      'endLine' => 36,
                      'startTokenPos' => 159,
                      'startFilePos' => 901,
                      'endTokenPos' => 159,
                      'endFilePos' => 917,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 36,
            'endLine' => 37,
            'startColumn' => 3,
            'endColumn' => 36,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'analysedPaths' => 
          array (
            'name' => 'analysedPaths',
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
            'isPromoted' => true,
            'attributes' => 
            array (
              0 => 
              array (
                'name' => 'PHPStan\\DependencyInjection\\AutowiredParameter',
                'isRepeated' => false,
                'arguments' => 
                array (
                ),
              ),
            ),
            'startLine' => 38,
            'endLine' => 39,
            'startColumn' => 3,
            'endColumn' => 30,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'analysedPathsFromConfig' => 
          array (
            'name' => 'analysedPathsFromConfig',
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
            'isPromoted' => true,
            'attributes' => 
            array (
              0 => 
              array (
                'name' => 'PHPStan\\DependencyInjection\\AutowiredParameter',
                'isRepeated' => false,
                'arguments' => 
                array (
                ),
              ),
            ),
            'startLine' => 40,
            'endLine' => 41,
            'startColumn' => 3,
            'endColumn' => 40,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
          'scanFiles' => 
          array (
            'name' => 'scanFiles',
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
            'isPromoted' => true,
            'attributes' => 
            array (
              0 => 
              array (
                'name' => 'PHPStan\\DependencyInjection\\AutowiredParameter',
                'isRepeated' => false,
                'arguments' => 
                array (
                ),
              ),
            ),
            'startLine' => 42,
            'endLine' => 43,
            'startColumn' => 3,
            'endColumn' => 26,
            'parameterIndex' => 4,
            'isOptional' => false,
          ),
          'scanDirectories' => 
          array (
            'name' => 'scanDirectories',
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
            'isPromoted' => true,
            'attributes' => 
            array (
              0 => 
              array (
                'name' => 'PHPStan\\DependencyInjection\\AutowiredParameter',
                'isRepeated' => false,
                'arguments' => 
                array (
                ),
              ),
            ),
            'startLine' => 44,
            'endLine' => 45,
            'startColumn' => 3,
            'endColumn' => 32,
            'parameterIndex' => 5,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @param string[] $analysedPaths
 * @param string[] $analysedPathsFromConfig
 * @param string[] $scanFiles
 * @param string[] $scanDirectories
 */',
        'startLine' => 33,
        'endLine' => 48,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\File',
        'declaringClassName' => 'PHPStan\\File\\FileMonitor',
        'implementingClassName' => 'PHPStan\\File\\FileMonitor',
        'currentClassName' => 'PHPStan\\File\\FileMonitor',
        'aliasName' => NULL,
      ),
      'initialize' => 
      array (
        'name' => 'initialize',
        'parameters' => 
        array (
          'filePaths' => 
          array (
            'name' => 'filePaths',
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
            'startLine' => 53,
            'endLine' => 53,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @param array<string> $filePaths
 */',
        'startLine' => 53,
        'endLine' => 63,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\File',
        'declaringClassName' => 'PHPStan\\File\\FileMonitor',
        'implementingClassName' => 'PHPStan\\File\\FileMonitor',
        'currentClassName' => 'PHPStan\\File\\FileMonitor',
        'aliasName' => NULL,
      ),
      'getChanges' => 
      array (
        'name' => 'getChanges',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPStan\\File\\FileMonitorResult',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 65,
        'endLine' => 106,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\File',
        'declaringClassName' => 'PHPStan\\File\\FileMonitor',
        'implementingClassName' => 'PHPStan\\File\\FileMonitor',
        'currentClassName' => 'PHPStan\\File\\FileMonitor',
        'aliasName' => NULL,
      ),
      'getFileHash' => 
      array (
        'name' => 'getFileHash',
        'parameters' => 
        array (
          'filePath' => 
          array (
            'name' => 'filePath',
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
            'startLine' => 108,
            'endLine' => 108,
            'startColumn' => 31,
            'endColumn' => 46,
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
        'docComment' => NULL,
        'startLine' => 108,
        'endLine' => 117,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'PHPStan\\File',
        'declaringClassName' => 'PHPStan\\File\\FileMonitor',
        'implementingClassName' => 'PHPStan\\File\\FileMonitor',
        'currentClassName' => 'PHPStan\\File\\FileMonitor',
        'aliasName' => NULL,
      ),
      'getScannedFiles' => 
      array (
        'name' => 'getScannedFiles',
        'parameters' => 
        array (
          'allAnalysedFiles' => 
          array (
            'name' => 'allAnalysedFiles',
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
            'startLine' => 123,
            'endLine' => 123,
            'startColumn' => 35,
            'endColumn' => 57,
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
 * @param string[] $allAnalysedFiles
 * @return array<string>
 */',
        'startLine' => 123,
        'endLine' => 145,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'PHPStan\\File',
        'declaringClassName' => 'PHPStan\\File\\FileMonitor',
        'implementingClassName' => 'PHPStan\\File\\FileMonitor',
        'currentClassName' => 'PHPStan\\File\\FileMonitor',
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