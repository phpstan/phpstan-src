<?php declare(strict_types = 1);

// osfsl-/home/runner/work/phpstan-src/phpstan-src/vendor/composer/../ondram/ci-detector/src/CiDetectorInterface.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OndraM\CiDetector\CiDetectorInterface
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-faee2609c8c1051557ef310edd8ce4fb462a95446c94247366ba43d8b2b0b28a-8.4.21-6.70.0.1',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OndraM\\CiDetector\\CiDetectorInterface',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/vendor/composer/../ondram/ci-detector/src/CiDetectorInterface.php',
      ),
    ),
    'namespace' => 'OndraM\\CiDetector',
    'name' => 'OndraM\\CiDetector\\CiDetectorInterface',
    'shortName' => 'CiDetectorInterface',
    'isInterface' => true,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Unified way to get environment variables from current continuous integration server
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 11,
    'endLine' => 24,
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
      'isCiDetected' => 
      array (
        'name' => 'isCiDetected',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Is current environment an recognized CI server?
 */',
        'startLine' => 16,
        'endLine' => 16,
        'startColumn' => 5,
        'endColumn' => 41,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OndraM\\CiDetector',
        'declaringClassName' => 'OndraM\\CiDetector\\CiDetectorInterface',
        'implementingClassName' => 'OndraM\\CiDetector\\CiDetectorInterface',
        'currentClassName' => 'OndraM\\CiDetector\\CiDetectorInterface',
        'aliasName' => NULL,
      ),
      'detect' => 
      array (
        'name' => 'detect',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OndraM\\CiDetector\\Ci\\CiInterface',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Detect current CI server and return instance of its settings
 *
 * @throws CiNotDetectedException
 */',
        'startLine' => 23,
        'endLine' => 23,
        'startColumn' => 5,
        'endColumn' => 42,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OndraM\\CiDetector',
        'declaringClassName' => 'OndraM\\CiDetector\\CiDetectorInterface',
        'implementingClassName' => 'OndraM\\CiDetector\\CiDetectorInterface',
        'currentClassName' => 'OndraM\\CiDetector\\CiDetectorInterface',
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