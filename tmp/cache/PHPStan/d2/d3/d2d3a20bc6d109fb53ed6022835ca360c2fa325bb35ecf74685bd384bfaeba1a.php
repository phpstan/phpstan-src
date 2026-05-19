<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/src/File/FileExcluderFactory.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\File\FileExcluderFactory
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-9f2a66b3a5a508dc2de1717e9ab4ee57a73040f4ae7cf2528ec120d5c80055f1',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\File\\FileExcluderFactory',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/src/File/FileExcluderFactory.php',
      ),
    ),
    'namespace' => 'PHPStan\\File',
    'name' => 'PHPStan\\File\\FileExcluderFactory',
    'shortName' => 'FileExcluderFactory',
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
      'fileExcluderRawFactory' => 
      array (
        'declaringClassName' => 'PHPStan\\File\\FileExcluderFactory',
        'implementingClassName' => 'PHPStan\\File\\FileExcluderFactory',
        'name' => 'fileExcluderRawFactory',
        'modifiers' => 4,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPStan\\File\\FileExcluderRawFactory',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 20,
        'endLine' => 20,
        'startColumn' => 3,
        'endColumn' => 56,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'excludePaths' => 
      array (
        'declaringClassName' => 'PHPStan\\File\\FileExcluderFactory',
        'implementingClassName' => 'PHPStan\\File\\FileExcluderFactory',
        'name' => 'excludePaths',
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
        'startLine' => 21,
        'endLine' => 22,
        'startColumn' => 3,
        'endColumn' => 29,
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
          'fileExcluderRawFactory' => 
          array (
            'name' => 'fileExcluderRawFactory',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\File\\FileExcluderRawFactory',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 20,
            'endLine' => 20,
            'startColumn' => 3,
            'endColumn' => 56,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'excludePaths' => 
          array (
            'name' => 'excludePaths',
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
            'startLine' => 21,
            'endLine' => 22,
            'startColumn' => 3,
            'endColumn' => 29,
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
 * @param array{analyse?: array<int, string>, analyseAndScan?: array<int, string>} $excludePaths
 */',
        'startLine' => 19,
        'endLine' => 25,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\File',
        'declaringClassName' => 'PHPStan\\File\\FileExcluderFactory',
        'implementingClassName' => 'PHPStan\\File\\FileExcluderFactory',
        'currentClassName' => 'PHPStan\\File\\FileExcluderFactory',
        'aliasName' => NULL,
      ),
      'createAnalyseFileExcluder' => 
      array (
        'name' => 'createAnalyseFileExcluder',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPStan\\File\\FileExcluder',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 27,
        'endLine' => 38,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\File',
        'declaringClassName' => 'PHPStan\\File\\FileExcluderFactory',
        'implementingClassName' => 'PHPStan\\File\\FileExcluderFactory',
        'currentClassName' => 'PHPStan\\File\\FileExcluderFactory',
        'aliasName' => NULL,
      ),
      'createScanFileExcluder' => 
      array (
        'name' => 'createScanFileExcluder',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPStan\\File\\FileExcluder',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 40,
        'endLine' => 48,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\File',
        'declaringClassName' => 'PHPStan\\File\\FileExcluderFactory',
        'implementingClassName' => 'PHPStan\\File\\FileExcluderFactory',
        'currentClassName' => 'PHPStan\\File\\FileExcluderFactory',
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