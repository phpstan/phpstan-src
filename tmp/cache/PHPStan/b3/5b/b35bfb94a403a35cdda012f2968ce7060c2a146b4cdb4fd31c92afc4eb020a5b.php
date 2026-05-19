<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/src/File/ParentDirectoryRelativePathHelper.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\File\ParentDirectoryRelativePathHelper
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-3bb6c28203c404cb875db0cafbd62622fd306a5c0f5fdfe9155e2dd3b55cffeb',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\File\\ParentDirectoryRelativePathHelper',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/src/File/ParentDirectoryRelativePathHelper.php',
      ),
    ),
    'namespace' => 'PHPStan\\File',
    'name' => 'PHPStan\\File\\ParentDirectoryRelativePathHelper',
    'shortName' => 'ParentDirectoryRelativePathHelper',
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
        'name' => 'PHPStan\\DependencyInjection\\NonAutowiredService',
        'isRepeated' => false,
        'arguments' => 
        array (
          'name' => 
          array (
            'code' => '\'parentDirectoryRelativePathHelper\'',
            'attributes' => 
            array (
              'startLine' => 19,
              'endLine' => 19,
              'startTokenPos' => 107,
              'startFilePos' => 459,
              'endTokenPos' => 107,
              'endFilePos' => 493,
            ),
          ),
        ),
      ),
    ),
    'startLine' => 19,
    'endLine' => 76,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
      0 => 'PHPStan\\File\\RelativePathHelper',
    ),
    'traitClassNames' => 
    array (
    ),
    'immediateConstants' => 
    array (
    ),
    'immediateProperties' => 
    array (
      'parentDirectory' => 
      array (
        'declaringClassName' => 'PHPStan\\File\\ParentDirectoryRelativePathHelper',
        'implementingClassName' => 'PHPStan\\File\\ParentDirectoryRelativePathHelper',
        'name' => 'parentDirectory',
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
                'code' => '\'%currentWorkingDirectory%\'',
                'attributes' => 
                array (
                  'startLine' => 24,
                  'endLine' => 24,
                  'startTokenPos' => 136,
                  'startFilePos' => 634,
                  'endTokenPos' => 136,
                  'endFilePos' => 660,
                ),
              ),
            ),
          ),
        ),
        'startLine' => 24,
        'endLine' => 25,
        'startColumn' => 3,
        'endColumn' => 33,
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
          'parentDirectory' => 
          array (
            'name' => 'parentDirectory',
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
                    'code' => '\'%currentWorkingDirectory%\'',
                    'attributes' => 
                    array (
                      'startLine' => 24,
                      'endLine' => 24,
                      'startTokenPos' => 136,
                      'startFilePos' => 634,
                      'endTokenPos' => 136,
                      'endFilePos' => 660,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 24,
            'endLine' => 25,
            'startColumn' => 3,
            'endColumn' => 33,
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
        'startLine' => 23,
        'endLine' => 28,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\File',
        'declaringClassName' => 'PHPStan\\File\\ParentDirectoryRelativePathHelper',
        'implementingClassName' => 'PHPStan\\File\\ParentDirectoryRelativePathHelper',
        'currentClassName' => 'PHPStan\\File\\ParentDirectoryRelativePathHelper',
        'aliasName' => NULL,
      ),
      'getRelativePath' => 
      array (
        'name' => 'getRelativePath',
        'parameters' => 
        array (
          'filename' => 
          array (
            'name' => 'filename',
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
            'startLine' => 30,
            'endLine' => 30,
            'startColumn' => 34,
            'endColumn' => 49,
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
        'startLine' => 30,
        'endLine' => 33,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\File',
        'declaringClassName' => 'PHPStan\\File\\ParentDirectoryRelativePathHelper',
        'implementingClassName' => 'PHPStan\\File\\ParentDirectoryRelativePathHelper',
        'currentClassName' => 'PHPStan\\File\\ParentDirectoryRelativePathHelper',
        'aliasName' => NULL,
      ),
      'getFilenameParts' => 
      array (
        'name' => 'getFilenameParts',
        'parameters' => 
        array (
          'filename' => 
          array (
            'name' => 'filename',
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
            'startLine' => 38,
            'endLine' => 38,
            'startColumn' => 35,
            'endColumn' => 50,
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
 * @return string[]
 */',
        'startLine' => 38,
        'endLine' => 74,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\File',
        'declaringClassName' => 'PHPStan\\File\\ParentDirectoryRelativePathHelper',
        'implementingClassName' => 'PHPStan\\File\\ParentDirectoryRelativePathHelper',
        'currentClassName' => 'PHPStan\\File\\ParentDirectoryRelativePathHelper',
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