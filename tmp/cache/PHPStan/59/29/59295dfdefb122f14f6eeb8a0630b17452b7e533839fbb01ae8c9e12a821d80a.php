<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/tests/PHPStan/File/FileExcluderTest.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\File\FileExcluderTest
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-2e95d020ac628c1db6ff613327b82bdcb40d06b025bbe3beb658d3a6bb51402a',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\File\\FileExcluderTest',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/tests/PHPStan/File/FileExcluderTest.php',
      ),
    ),
    'namespace' => 'PHPStan\\File',
    'name' => 'PHPStan\\File\\FileExcluderTest',
    'shortName' => 'FileExcluderTest',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => NULL,
    'attributes' => 
    array (
    ),
    'startLine' => 8,
    'endLine' => 259,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'PHPStan\\Testing\\PHPStanTestCase',
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
      'testFilesAreExcludedFromAnalysingOnWindows' => 
      array (
        'name' => 'testFilesAreExcludedFromAnalysingOnWindows',
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
            'startLine' => 16,
            'endLine' => 16,
            'startColumn' => 3,
            'endColumn' => 18,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'analyseExcludes' => 
          array (
            'name' => 'analyseExcludes',
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
            'startLine' => 17,
            'endLine' => 17,
            'startColumn' => 3,
            'endColumn' => 24,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'isExcluded' => 
          array (
            'name' => 'isExcluded',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'bool',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 18,
            'endLine' => 18,
            'startColumn' => 3,
            'endColumn' => 18,
            'parameterIndex' => 2,
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
          0 => 
          array (
            'name' => 'PHPUnit\\Framework\\Attributes\\DataProvider',
            'isRepeated' => false,
            'arguments' => 
            array (
              0 => 
              array (
                'code' => '\'dataExcludeOnWindows\'',
                'attributes' => 
                array (
                  'startLine' => 14,
                  'endLine' => 14,
                  'startTokenPos' => 41,
                  'startFilePos' => 257,
                  'endTokenPos' => 41,
                  'endFilePos' => 278,
                ),
              ),
            ),
          ),
        ),
        'docComment' => '/**
 * @param string[] $analyseExcludes
 */',
        'startLine' => 14,
        'endLine' => 26,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\File',
        'declaringClassName' => 'PHPStan\\File\\FileExcluderTest',
        'implementingClassName' => 'PHPStan\\File\\FileExcluderTest',
        'currentClassName' => 'PHPStan\\File\\FileExcluderTest',
        'aliasName' => NULL,
      ),
      'dataExcludeOnWindows' => 
      array (
        'name' => 'dataExcludeOnWindows',
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
        'docComment' => NULL,
        'startLine' => 28,
        'endLine' => 117,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'PHPStan\\File',
        'declaringClassName' => 'PHPStan\\File\\FileExcluderTest',
        'implementingClassName' => 'PHPStan\\File\\FileExcluderTest',
        'currentClassName' => 'PHPStan\\File\\FileExcluderTest',
        'aliasName' => NULL,
      ),
      'testFilesAreExcludedFromAnalysingOnUnix' => 
      array (
        'name' => 'testFilesAreExcludedFromAnalysingOnUnix',
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
            'startLine' => 124,
            'endLine' => 124,
            'startColumn' => 3,
            'endColumn' => 18,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'analyseExcludes' => 
          array (
            'name' => 'analyseExcludes',
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
            'startLine' => 125,
            'endLine' => 125,
            'startColumn' => 3,
            'endColumn' => 24,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'isExcluded' => 
          array (
            'name' => 'isExcluded',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'bool',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 126,
            'endLine' => 126,
            'startColumn' => 3,
            'endColumn' => 18,
            'parameterIndex' => 2,
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
          0 => 
          array (
            'name' => 'PHPUnit\\Framework\\Attributes\\DataProvider',
            'isRepeated' => false,
            'arguments' => 
            array (
              0 => 
              array (
                'code' => '\'dataExcludeOnUnix\'',
                'attributes' => 
                array (
                  'startLine' => 122,
                  'endLine' => 122,
                  'startTokenPos' => 477,
                  'startFilePos' => 2190,
                  'endTokenPos' => 477,
                  'endFilePos' => 2208,
                ),
              ),
            ),
          ),
        ),
        'docComment' => '/**
 * @param string[] $analyseExcludes
 */',
        'startLine' => 122,
        'endLine' => 134,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\File',
        'declaringClassName' => 'PHPStan\\File\\FileExcluderTest',
        'implementingClassName' => 'PHPStan\\File\\FileExcluderTest',
        'currentClassName' => 'PHPStan\\File\\FileExcluderTest',
        'aliasName' => NULL,
      ),
      'dataExcludeOnUnix' => 
      array (
        'name' => 'dataExcludeOnUnix',
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
        'docComment' => NULL,
        'startLine' => 136,
        'endLine' => 205,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'PHPStan\\File',
        'declaringClassName' => 'PHPStan\\File\\FileExcluderTest',
        'implementingClassName' => 'PHPStan\\File\\FileExcluderTest',
        'currentClassName' => 'PHPStan\\File\\FileExcluderTest',
        'aliasName' => NULL,
      ),
      'dataNoImplicitWildcard' => 
      array (
        'name' => 'dataNoImplicitWildcard',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'iterable',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 207,
        'endLine' => 240,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => true,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'PHPStan\\File',
        'declaringClassName' => 'PHPStan\\File\\FileExcluderTest',
        'implementingClassName' => 'PHPStan\\File\\FileExcluderTest',
        'currentClassName' => 'PHPStan\\File\\FileExcluderTest',
        'aliasName' => NULL,
      ),
      'testNoImplicitWildcard' => 
      array (
        'name' => 'testNoImplicitWildcard',
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
            'startLine' => 247,
            'endLine' => 247,
            'startColumn' => 3,
            'endColumn' => 18,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'analyseExcludes' => 
          array (
            'name' => 'analyseExcludes',
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
            'startLine' => 248,
            'endLine' => 248,
            'startColumn' => 3,
            'endColumn' => 24,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'isExcluded' => 
          array (
            'name' => 'isExcluded',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'bool',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 249,
            'endLine' => 249,
            'startColumn' => 3,
            'endColumn' => 18,
            'parameterIndex' => 2,
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
          0 => 
          array (
            'name' => 'PHPUnit\\Framework\\Attributes\\DataProvider',
            'isRepeated' => false,
            'arguments' => 
            array (
              0 => 
              array (
                'code' => '\'dataNoImplicitWildcard\'',
                'attributes' => 
                array (
                  'startLine' => 245,
                  'endLine' => 245,
                  'startTokenPos' => 978,
                  'startFilePos' => 4215,
                  'endTokenPos' => 978,
                  'endFilePos' => 4238,
                ),
              ),
            ),
          ),
        ),
        'docComment' => '/**
 * @param string[] $analyseExcludes
 */',
        'startLine' => 245,
        'endLine' => 257,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\File',
        'declaringClassName' => 'PHPStan\\File\\FileExcluderTest',
        'implementingClassName' => 'PHPStan\\File\\FileExcluderTest',
        'currentClassName' => 'PHPStan\\File\\FileExcluderTest',
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