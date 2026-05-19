<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/src/Command/ErrorFormatter/CheckstyleErrorFormatter.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\Command\ErrorFormatter\CheckstyleErrorFormatter
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-abb5f4c306d6138b356999a1510414e68aa7de9e9ff7ece6f1e05518fc326fe1',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\Command\\ErrorFormatter\\CheckstyleErrorFormatter',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/src/Command/ErrorFormatter/CheckstyleErrorFormatter.php',
      ),
    ),
    'namespace' => 'PHPStan\\Command\\ErrorFormatter',
    'name' => 'PHPStan\\Command\\ErrorFormatter\\CheckstyleErrorFormatter',
    'shortName' => 'CheckstyleErrorFormatter',
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
          'name' => 
          array (
            'code' => '\'errorFormatter.checkstyle\'',
            'attributes' => 
            array (
              'startLine' => 17,
              'endLine' => 17,
              'startTokenPos' => 87,
              'startFilePos' => 449,
              'endTokenPos' => 87,
              'endFilePos' => 475,
            ),
          ),
        ),
      ),
    ),
    'startLine' => 17,
    'endLine' => 127,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
      0 => 'PHPStan\\Command\\ErrorFormatter\\ErrorFormatter',
    ),
    'traitClassNames' => 
    array (
    ),
    'immediateConstants' => 
    array (
    ),
    'immediateProperties' => 
    array (
      'relativePathHelper' => 
      array (
        'declaringClassName' => 'PHPStan\\Command\\ErrorFormatter\\CheckstyleErrorFormatter',
        'implementingClassName' => 'PHPStan\\Command\\ErrorFormatter\\CheckstyleErrorFormatter',
        'name' => 'relativePathHelper',
        'modifiers' => 4,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPStan\\File\\RelativePathHelper',
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
                'code' => '\'@simpleRelativePathHelper\'',
                'attributes' => 
                array (
                  'startLine' => 22,
                  'endLine' => 22,
                  'startTokenPos' => 116,
                  'startFilePos' => 603,
                  'endTokenPos' => 116,
                  'endFilePos' => 629,
                ),
              ),
            ),
          ),
        ),
        'startLine' => 22,
        'endLine' => 23,
        'startColumn' => 3,
        'endColumn' => 48,
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
          'relativePathHelper' => 
          array (
            'name' => 'relativePathHelper',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\File\\RelativePathHelper',
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
                    'code' => '\'@simpleRelativePathHelper\'',
                    'attributes' => 
                    array (
                      'startLine' => 22,
                      'endLine' => 22,
                      'startTokenPos' => 116,
                      'startFilePos' => 603,
                      'endTokenPos' => 116,
                      'endFilePos' => 629,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 22,
            'endLine' => 23,
            'startColumn' => 3,
            'endColumn' => 48,
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
        'startLine' => 21,
        'endLine' => 26,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Command\\ErrorFormatter',
        'declaringClassName' => 'PHPStan\\Command\\ErrorFormatter\\CheckstyleErrorFormatter',
        'implementingClassName' => 'PHPStan\\Command\\ErrorFormatter\\CheckstyleErrorFormatter',
        'currentClassName' => 'PHPStan\\Command\\ErrorFormatter\\CheckstyleErrorFormatter',
        'aliasName' => NULL,
      ),
      'formatErrors' => 
      array (
        'name' => 'formatErrors',
        'parameters' => 
        array (
          'analysisResult' => 
          array (
            'name' => 'analysisResult',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\Command\\AnalysisResult',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 29,
            'endLine' => 29,
            'startColumn' => 3,
            'endColumn' => 32,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'output' => 
          array (
            'name' => 'output',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\Command\\Output',
                'isIdentifier' => false,
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
            'startColumn' => 3,
            'endColumn' => 16,
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
        'docComment' => NULL,
        'startLine' => 28,
        'endLine' => 90,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Command\\ErrorFormatter',
        'declaringClassName' => 'PHPStan\\Command\\ErrorFormatter\\CheckstyleErrorFormatter',
        'implementingClassName' => 'PHPStan\\Command\\ErrorFormatter\\CheckstyleErrorFormatter',
        'currentClassName' => 'PHPStan\\Command\\ErrorFormatter\\CheckstyleErrorFormatter',
        'aliasName' => NULL,
      ),
      'escape' => 
      array (
        'name' => 'escape',
        'parameters' => 
        array (
          'string' => 
          array (
            'name' => 'string',
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
            'startLine' => 96,
            'endLine' => 96,
            'startColumn' => 26,
            'endColumn' => 39,
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
        'docComment' => '/**
 * Escapes values for using in XML
 *
 */',
        'startLine' => 96,
        'endLine' => 99,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'PHPStan\\Command\\ErrorFormatter',
        'declaringClassName' => 'PHPStan\\Command\\ErrorFormatter\\CheckstyleErrorFormatter',
        'implementingClassName' => 'PHPStan\\Command\\ErrorFormatter\\CheckstyleErrorFormatter',
        'currentClassName' => 'PHPStan\\Command\\ErrorFormatter\\CheckstyleErrorFormatter',
        'aliasName' => NULL,
      ),
      'groupByFile' => 
      array (
        'name' => 'groupByFile',
        'parameters' => 
        array (
          'analysisResult' => 
          array (
            'name' => 'analysisResult',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\Command\\AnalysisResult',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 107,
            'endLine' => 107,
            'startColumn' => 31,
            'endColumn' => 60,
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
 * Group errors by file
 *
 * @return array<string, array<Error>> Array that have as key the relative path of file
 * and as value an array with occurred errors.
 */',
        'startLine' => 107,
        'endLine' => 125,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'PHPStan\\Command\\ErrorFormatter',
        'declaringClassName' => 'PHPStan\\Command\\ErrorFormatter\\CheckstyleErrorFormatter',
        'implementingClassName' => 'PHPStan\\Command\\ErrorFormatter\\CheckstyleErrorFormatter',
        'currentClassName' => 'PHPStan\\Command\\ErrorFormatter\\CheckstyleErrorFormatter',
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