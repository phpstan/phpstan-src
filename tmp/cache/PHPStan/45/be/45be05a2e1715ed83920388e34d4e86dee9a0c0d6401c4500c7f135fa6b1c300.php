<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/src/Command/ErrorFormatter/ErrorFormatter.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\Command\ErrorFormatter\ErrorFormatter
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-146c5f18c6e8d60ccc3e70ebb19aeaadab18fd1ae57f46834678e89b7960bb51',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\Command\\ErrorFormatter\\ErrorFormatter',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/src/Command/ErrorFormatter/ErrorFormatter.php',
      ),
    ),
    'namespace' => 'PHPStan\\Command\\ErrorFormatter',
    'name' => 'PHPStan\\Command\\ErrorFormatter\\ErrorFormatter',
    'shortName' => 'ErrorFormatter',
    'isInterface' => true,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * This is the interface custom error formatters implement. Register it in the configuration file
 * like this:
 *
 * ```
 * services:
 * 	errorFormatter.myFormat:
 *		class: App\\PHPStan\\AwesomeErrorFormatter
 * ```
 *
 * Learn more: https://phpstan.org/developing-extensions/error-formatters
 *
 * @api
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 22,
    'endLine' => 35,
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
    ),
    'immediateMethods' => 
    array (
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
            'startLine' => 32,
            'endLine' => 32,
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
        'docComment' => '/**
 * Formats the errors and outputs them to the console.
 *
 * @return int Error code.
 */',
        'startLine' => 30,
        'endLine' => 33,
        'startColumn' => 2,
        'endColumn' => 8,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Command\\ErrorFormatter',
        'declaringClassName' => 'PHPStan\\Command\\ErrorFormatter\\ErrorFormatter',
        'implementingClassName' => 'PHPStan\\Command\\ErrorFormatter\\ErrorFormatter',
        'currentClassName' => 'PHPStan\\Command\\ErrorFormatter\\ErrorFormatter',
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