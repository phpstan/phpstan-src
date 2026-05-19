<?php declare(strict_types = 1);

// osfsl-/home/runner/work/phpstan-src/phpstan-src/vendor/composer/./semver/src/Semver.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Composer\Semver\Semver
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-83ebfc1abf994399041b75a1bbaddbae308220f4cc1af01c5ed7f0c5356922a2-8.4.21-6.70.0.1',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Composer\\Semver\\Semver',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/vendor/composer/./semver/src/Semver.php',
      ),
    ),
    'namespace' => 'Composer\\Semver',
    'name' => 'Composer\\Semver\\Semver',
    'shortName' => 'Semver',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => NULL,
    'attributes' => 
    array (
    ),
    'startLine' => 16,
    'endLine' => 129,
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
      'SORT_ASC' => 
      array (
        'declaringClassName' => 'Composer\\Semver\\Semver',
        'implementingClassName' => 'Composer\\Semver\\Semver',
        'name' => 'SORT_ASC',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '1',
          'attributes' => 
          array (
            'startLine' => 18,
            'endLine' => 18,
            'startTokenPos' => 26,
            'startFilePos' => 343,
            'endTokenPos' => 26,
            'endFilePos' => 343,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 18,
        'endLine' => 18,
        'startColumn' => 5,
        'endColumn' => 23,
      ),
      'SORT_DESC' => 
      array (
        'declaringClassName' => 'Composer\\Semver\\Semver',
        'implementingClassName' => 'Composer\\Semver\\Semver',
        'name' => 'SORT_DESC',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '-1',
          'attributes' => 
          array (
            'startLine' => 19,
            'endLine' => 19,
            'startTokenPos' => 35,
            'startFilePos' => 368,
            'endTokenPos' => 36,
            'endFilePos' => 369,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 19,
        'endLine' => 19,
        'startColumn' => 5,
        'endColumn' => 25,
      ),
    ),
    'immediateProperties' => 
    array (
      'versionParser' => 
      array (
        'declaringClassName' => 'Composer\\Semver\\Semver',
        'implementingClassName' => 'Composer\\Semver\\Semver',
        'name' => 'versionParser',
        'modifiers' => 20,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/** @var VersionParser */',
        'attributes' => 
        array (
        ),
        'startLine' => 22,
        'endLine' => 22,
        'startColumn' => 5,
        'endColumn' => 34,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
    ),
    'immediateMethods' => 
    array (
      'satisfies' => 
      array (
        'name' => 'satisfies',
        'parameters' => 
        array (
          'version' => 
          array (
            'name' => 'version',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 32,
            'endLine' => 32,
            'startColumn' => 38,
            'endColumn' => 45,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'constraints' => 
          array (
            'name' => 'constraints',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 32,
            'endLine' => 32,
            'startColumn' => 48,
            'endColumn' => 59,
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
 * Determine if given version satisfies given constraints.
 *
 * @param string $version
 * @param string $constraints
 *
 * @return bool
 */',
        'startLine' => 32,
        'endLine' => 43,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Composer\\Semver',
        'declaringClassName' => 'Composer\\Semver\\Semver',
        'implementingClassName' => 'Composer\\Semver\\Semver',
        'currentClassName' => 'Composer\\Semver\\Semver',
        'aliasName' => NULL,
      ),
      'satisfiedBy' => 
      array (
        'name' => 'satisfiedBy',
        'parameters' => 
        array (
          'versions' => 
          array (
            'name' => 'versions',
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
            'startColumn' => 40,
            'endColumn' => 54,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'constraints' => 
          array (
            'name' => 'constraints',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 53,
            'endLine' => 53,
            'startColumn' => 57,
            'endColumn' => 68,
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
 * Return all versions that satisfy given constraints.
 *
 * @param string[] $versions
 * @param string   $constraints
 *
 * @return list<string>
 */',
        'startLine' => 53,
        'endLine' => 60,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Composer\\Semver',
        'declaringClassName' => 'Composer\\Semver\\Semver',
        'implementingClassName' => 'Composer\\Semver\\Semver',
        'currentClassName' => 'Composer\\Semver\\Semver',
        'aliasName' => NULL,
      ),
      'sort' => 
      array (
        'name' => 'sort',
        'parameters' => 
        array (
          'versions' => 
          array (
            'name' => 'versions',
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
            'startLine' => 69,
            'endLine' => 69,
            'startColumn' => 33,
            'endColumn' => 47,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Sort given array of versions.
 *
 * @param string[] $versions
 *
 * @return list<string>
 */',
        'startLine' => 69,
        'endLine' => 72,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Composer\\Semver',
        'declaringClassName' => 'Composer\\Semver\\Semver',
        'implementingClassName' => 'Composer\\Semver\\Semver',
        'currentClassName' => 'Composer\\Semver\\Semver',
        'aliasName' => NULL,
      ),
      'rsort' => 
      array (
        'name' => 'rsort',
        'parameters' => 
        array (
          'versions' => 
          array (
            'name' => 'versions',
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
            'startLine' => 81,
            'endLine' => 81,
            'startColumn' => 34,
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
        'docComment' => '/**
 * Sort given array of versions in reverse.
 *
 * @param string[] $versions
 *
 * @return list<string>
 */',
        'startLine' => 81,
        'endLine' => 84,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Composer\\Semver',
        'declaringClassName' => 'Composer\\Semver\\Semver',
        'implementingClassName' => 'Composer\\Semver\\Semver',
        'currentClassName' => 'Composer\\Semver\\Semver',
        'aliasName' => NULL,
      ),
      'usort' => 
      array (
        'name' => 'usort',
        'parameters' => 
        array (
          'versions' => 
          array (
            'name' => 'versions',
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
            'startLine' => 92,
            'endLine' => 92,
            'startColumn' => 35,
            'endColumn' => 49,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'direction' => 
          array (
            'name' => 'direction',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 92,
            'endLine' => 92,
            'startColumn' => 52,
            'endColumn' => 61,
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
 * @param string[] $versions
 * @param int      $direction
 *
 * @return list<string>
 */',
        'startLine' => 92,
        'endLine' => 128,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 20,
        'namespace' => 'Composer\\Semver',
        'declaringClassName' => 'Composer\\Semver\\Semver',
        'implementingClassName' => 'Composer\\Semver\\Semver',
        'currentClassName' => 'Composer\\Semver\\Semver',
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