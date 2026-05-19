<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/src/Command/ErrorFormatter/GithubErrorFormatter.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\Command\ErrorFormatter\GithubErrorFormatter
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-7fcebe666344acea39abb73cebcfaccfa9f1f4c67b2015c38ef56a21b5e21cab',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\Command\\ErrorFormatter\\GithubErrorFormatter',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/src/Command/ErrorFormatter/GithubErrorFormatter.php',
      ),
    ),
    'namespace' => 'PHPStan\\Command\\ErrorFormatter',
    'name' => 'PHPStan\\Command\\ErrorFormatter\\GithubErrorFormatter',
    'shortName' => 'GithubErrorFormatter',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Allow errors to be reported in pull-requests diff when run in a GitHub Action
 * @see https://help.github.com/en/actions/reference/workflow-commands-for-github-actions#setting-an-error-message
 */',
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
            'code' => '\'errorFormatter.github\'',
            'attributes' => 
            array (
              'startLine' => 20,
              'endLine' => 20,
              'startTokenPos' => 84,
              'startFilePos' => 632,
              'endTokenPos' => 84,
              'endFilePos' => 654,
            ),
          ),
        ),
      ),
    ),
    'startLine' => 20,
    'endLine' => 83,
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
        'declaringClassName' => 'PHPStan\\Command\\ErrorFormatter\\GithubErrorFormatter',
        'implementingClassName' => 'PHPStan\\Command\\ErrorFormatter\\GithubErrorFormatter',
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
                  'startLine' => 25,
                  'endLine' => 25,
                  'startTokenPos' => 113,
                  'startFilePos' => 778,
                  'endTokenPos' => 113,
                  'endFilePos' => 804,
                ),
              ),
            ),
          ),
        ),
        'startLine' => 25,
        'endLine' => 26,
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
                      'startLine' => 25,
                      'endLine' => 25,
                      'startTokenPos' => 113,
                      'startFilePos' => 778,
                      'endTokenPos' => 113,
                      'endFilePos' => 804,
                    ),
                  ),
                ),
              ),
            ),
            'startLine' => 25,
            'endLine' => 26,
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
        'startLine' => 24,
        'endLine' => 29,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Command\\ErrorFormatter',
        'declaringClassName' => 'PHPStan\\Command\\ErrorFormatter\\GithubErrorFormatter',
        'implementingClassName' => 'PHPStan\\Command\\ErrorFormatter\\GithubErrorFormatter',
        'currentClassName' => 'PHPStan\\Command\\ErrorFormatter\\GithubErrorFormatter',
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
            'startLine' => 31,
            'endLine' => 31,
            'startColumn' => 31,
            'endColumn' => 60,
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
            'startLine' => 31,
            'endLine' => 31,
            'startColumn' => 63,
            'endColumn' => 76,
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
        'startLine' => 31,
        'endLine' => 72,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Command\\ErrorFormatter',
        'declaringClassName' => 'PHPStan\\Command\\ErrorFormatter\\GithubErrorFormatter',
        'implementingClassName' => 'PHPStan\\Command\\ErrorFormatter\\GithubErrorFormatter',
        'currentClassName' => 'PHPStan\\Command\\ErrorFormatter\\GithubErrorFormatter',
        'aliasName' => NULL,
      ),
      'formatMessage' => 
      array (
        'name' => 'formatMessage',
        'parameters' => 
        array (
          'message' => 
          array (
            'name' => 'message',
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
            'startLine' => 74,
            'endLine' => 74,
            'startColumn' => 33,
            'endColumn' => 47,
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
        'startLine' => 74,
        'endLine' => 81,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'PHPStan\\Command\\ErrorFormatter',
        'declaringClassName' => 'PHPStan\\Command\\ErrorFormatter\\GithubErrorFormatter',
        'implementingClassName' => 'PHPStan\\Command\\ErrorFormatter\\GithubErrorFormatter',
        'currentClassName' => 'PHPStan\\Command\\ErrorFormatter\\GithubErrorFormatter',
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