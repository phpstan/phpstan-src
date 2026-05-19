<?php declare(strict_types = 1);

// osfsl-/home/runner/work/phpstan-src/phpstan-src/vendor/composer/./xdebug-handler/src/PhpConfig.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Composer\XdebugHandler\PhpConfig
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-a1c6a05ab6a74179aea210ef2d781888e4d548d84aad663845d515481c389e70-8.4.21-6.70.0.1',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Composer\\XdebugHandler\\PhpConfig',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/vendor/composer/./xdebug-handler/src/PhpConfig.php',
      ),
    ),
    'namespace' => 'Composer\\XdebugHandler',
    'name' => 'Composer\\XdebugHandler\\PhpConfig',
    'shortName' => 'PhpConfig',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * @author John Stevenson <john-stevenson@blueyonder.co.uk>
 *
 * @phpstan-type restartData array{tmpIni: string, scannedInis: bool, scanDir: false|string, phprc: false|string, inis: string[], skipped: string}
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 21,
    'endLine' => 91,
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
      'useOriginal' => 
      array (
        'name' => 'useOriginal',
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
        'docComment' => '/**
 * Use the original PHP configuration
 *
 * @return string[] Empty array of PHP cli options
 */',
        'startLine' => 28,
        'endLine' => 32,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Composer\\XdebugHandler',
        'declaringClassName' => 'Composer\\XdebugHandler\\PhpConfig',
        'implementingClassName' => 'Composer\\XdebugHandler\\PhpConfig',
        'currentClassName' => 'Composer\\XdebugHandler\\PhpConfig',
        'aliasName' => NULL,
      ),
      'useStandard' => 
      array (
        'name' => 'useStandard',
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
        'docComment' => '/**
 * Use standard restart settings
 *
 * @return string[] PHP cli options
 */',
        'startLine' => 39,
        'endLine' => 47,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Composer\\XdebugHandler',
        'declaringClassName' => 'Composer\\XdebugHandler\\PhpConfig',
        'implementingClassName' => 'Composer\\XdebugHandler\\PhpConfig',
        'currentClassName' => 'Composer\\XdebugHandler\\PhpConfig',
        'aliasName' => NULL,
      ),
      'usePersistent' => 
      array (
        'name' => 'usePersistent',
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
        'docComment' => '/**
 * Use environment variables to persist settings
 *
 * @return string[] Empty array of PHP cli options
 */',
        'startLine' => 54,
        'endLine' => 63,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Composer\\XdebugHandler',
        'declaringClassName' => 'Composer\\XdebugHandler\\PhpConfig',
        'implementingClassName' => 'Composer\\XdebugHandler\\PhpConfig',
        'currentClassName' => 'Composer\\XdebugHandler\\PhpConfig',
        'aliasName' => NULL,
      ),
      'getDataAndReset' => 
      array (
        'name' => 'getDataAndReset',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
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
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Returns restart data if available and resets the environment
 *
 * @phpstan-return restartData|null
 */',
        'startLine' => 70,
        'endLine' => 79,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'Composer\\XdebugHandler',
        'declaringClassName' => 'Composer\\XdebugHandler\\PhpConfig',
        'implementingClassName' => 'Composer\\XdebugHandler\\PhpConfig',
        'currentClassName' => 'Composer\\XdebugHandler\\PhpConfig',
        'aliasName' => NULL,
      ),
      'updateEnv' => 
      array (
        'name' => 'updateEnv',
        'parameters' => 
        array (
          'name' => 
          array (
            'name' => 'name',
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
            'startLine' => 87,
            'endLine' => 87,
            'startColumn' => 32,
            'endColumn' => 43,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'value' => 
          array (
            'name' => 'value',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 87,
            'endLine' => 87,
            'startColumn' => 46,
            'endColumn' => 51,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Updates a restart settings value in the environment
 *
 * @param string $name
 * @param string|false $value
 */',
        'startLine' => 87,
        'endLine' => 90,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'Composer\\XdebugHandler',
        'declaringClassName' => 'Composer\\XdebugHandler\\PhpConfig',
        'implementingClassName' => 'Composer\\XdebugHandler\\PhpConfig',
        'currentClassName' => 'Composer\\XdebugHandler\\PhpConfig',
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