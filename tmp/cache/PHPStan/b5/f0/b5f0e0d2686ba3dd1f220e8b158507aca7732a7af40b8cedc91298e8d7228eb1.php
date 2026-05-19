<?php declare(strict_types = 1);

// osfsl-/home/runner/work/phpstan-src/phpstan-src/vendor/composer/./semver/src/VersionParser.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Composer\Semver\VersionParser
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-63311ae28a20ca266fb0d9599cbb39c60aed64f69aadc59052b34b9b32043e17-8.4.21-6.70.0.1',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Composer\\Semver\\VersionParser',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/vendor/composer/./semver/src/VersionParser.php',
      ),
    ),
    'namespace' => 'Composer\\Semver',
    'name' => 'Composer\\Semver\\VersionParser',
    'shortName' => 'VersionParser',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Version parser.
 *
 * @author Jordi Boggiano <j.boggiano@seld.be>
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 24,
    'endLine' => 591,
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
      'modifierRegex' => 
      array (
        'declaringClassName' => 'Composer\\Semver\\VersionParser',
        'implementingClassName' => 'Composer\\Semver\\VersionParser',
        'name' => 'modifierRegex',
        'modifiers' => 20,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'[._-]?(?:(stable|beta|b|RC|alpha|a|patch|pl|p)((?:[.-]?\\d+)*+)?)?([.-]?dev)?\'',
          'attributes' => 
          array (
            'startLine' => 39,
            'endLine' => 39,
            'startTokenPos' => 47,
            'startFilePos' => 1126,
            'endTokenPos' => 47,
            'endFilePos' => 1203,
          ),
        ),
        'docComment' => '/**
 * Regex to match pre-release data (sort of).
 *
 * Due to backwards compatibility:
 *   - Instead of enforcing hyphen, an underscore, dot or nothing at all are also accepted.
 *   - Only stabilities as recognized by Composer are allowed to precede a numerical identifier.
 *   - Numerical-only pre-release identifiers are not supported, see tests.
 *
 *                        |--------------|
 * [major].[minor].[patch] -[pre-release] +[build-metadata]
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 39,
        'endLine' => 39,
        'startColumn' => 5,
        'endColumn' => 115,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'stabilitiesRegex' => 
      array (
        'declaringClassName' => 'Composer\\Semver\\VersionParser',
        'implementingClassName' => 'Composer\\Semver\\VersionParser',
        'name' => 'stabilitiesRegex',
        'modifiers' => 20,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'stable|RC|beta|alpha|dev\'',
          'attributes' => 
          array (
            'startLine' => 42,
            'endLine' => 42,
            'startTokenPos' => 60,
            'startFilePos' => 1269,
            'endTokenPos' => 60,
            'endFilePos' => 1294,
          ),
        ),
        'docComment' => '/** @var string */',
        'attributes' => 
        array (
        ),
        'startLine' => 42,
        'endLine' => 42,
        'startColumn' => 5,
        'endColumn' => 66,
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
      'parseStability' => 
      array (
        'name' => 'parseStability',
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
            'startLine' => 52,
            'endLine' => 52,
            'startColumn' => 43,
            'endColumn' => 50,
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
 * Returns the stability of a version.
 *
 * @param string $version
 *
 * @return string
 * @phpstan-return \'stable\'|\'RC\'|\'beta\'|\'alpha\'|\'dev\'
 */',
        'startLine' => 52,
        'endLine' => 79,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Composer\\Semver',
        'declaringClassName' => 'Composer\\Semver\\VersionParser',
        'implementingClassName' => 'Composer\\Semver\\VersionParser',
        'currentClassName' => 'Composer\\Semver\\VersionParser',
        'aliasName' => NULL,
      ),
      'normalizeStability' => 
      array (
        'name' => 'normalizeStability',
        'parameters' => 
        array (
          'stability' => 
          array (
            'name' => 'stability',
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
            'startColumn' => 47,
            'endColumn' => 56,
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
 * @param string $stability
 *
 * @return string
 * @phpstan-return \'stable\'|\'RC\'|\'beta\'|\'alpha\'|\'dev\'
 */',
        'startLine' => 87,
        'endLine' => 96,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Composer\\Semver',
        'declaringClassName' => 'Composer\\Semver\\VersionParser',
        'implementingClassName' => 'Composer\\Semver\\VersionParser',
        'currentClassName' => 'Composer\\Semver\\VersionParser',
        'aliasName' => NULL,
      ),
      'normalize' => 
      array (
        'name' => 'normalize',
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
            'startLine' => 108,
            'endLine' => 108,
            'startColumn' => 31,
            'endColumn' => 38,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'fullVersion' => 
          array (
            'name' => 'fullVersion',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 108,
                'endLine' => 108,
                'startTokenPos' => 407,
                'startFilePos' => 3180,
                'endTokenPos' => 407,
                'endFilePos' => 3183,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 108,
            'endLine' => 108,
            'startColumn' => 41,
            'endColumn' => 59,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Normalizes a version string to be able to perform comparisons on it.
 *
 * @param string $version
 * @param ?string $fullVersion optional complete version string to give more context
 *
 * @throws \\UnexpectedValueException
 *
 * @return string
 */',
        'startLine' => 108,
        'endLine' => 192,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Composer\\Semver',
        'declaringClassName' => 'Composer\\Semver\\VersionParser',
        'implementingClassName' => 'Composer\\Semver\\VersionParser',
        'currentClassName' => 'Composer\\Semver\\VersionParser',
        'aliasName' => NULL,
      ),
      'parseNumericAliasPrefix' => 
      array (
        'name' => 'parseNumericAliasPrefix',
        'parameters' => 
        array (
          'branch' => 
          array (
            'name' => 'branch',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 201,
            'endLine' => 201,
            'startColumn' => 45,
            'endColumn' => 51,
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
 * Extract numeric prefix from alias, if it is in numeric format, suitable for version comparison.
 *
 * @param string $branch Branch name (e.g. 2.1.x-dev)
 *
 * @return string|false Numeric prefix if present (e.g. 2.1.) or false
 */',
        'startLine' => 201,
        'endLine' => 208,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Composer\\Semver',
        'declaringClassName' => 'Composer\\Semver\\VersionParser',
        'implementingClassName' => 'Composer\\Semver\\VersionParser',
        'currentClassName' => 'Composer\\Semver\\VersionParser',
        'aliasName' => NULL,
      ),
      'normalizeBranch' => 
      array (
        'name' => 'normalizeBranch',
        'parameters' => 
        array (
          'name' => 
          array (
            'name' => 'name',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 217,
            'endLine' => 217,
            'startColumn' => 37,
            'endColumn' => 41,
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
 * Normalizes a branch name to be able to perform comparisons on it.
 *
 * @param string $name
 *
 * @return string
 */',
        'startLine' => 217,
        'endLine' => 231,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Composer\\Semver',
        'declaringClassName' => 'Composer\\Semver\\VersionParser',
        'implementingClassName' => 'Composer\\Semver\\VersionParser',
        'currentClassName' => 'Composer\\Semver\\VersionParser',
        'aliasName' => NULL,
      ),
      'normalizeDefaultBranch' => 
      array (
        'name' => 'normalizeDefaultBranch',
        'parameters' => 
        array (
          'name' => 
          array (
            'name' => 'name',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 242,
            'endLine' => 242,
            'startColumn' => 44,
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
 * Normalizes a default branch name (i.e. master on git) to 9999999-dev.
 *
 * @param string $name
 *
 * @return string
 *
 * @deprecated No need to use this anymore in theory, Composer 2 does not normalize any branch names to 9999999-dev anymore
 */',
        'startLine' => 242,
        'endLine' => 249,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Composer\\Semver',
        'declaringClassName' => 'Composer\\Semver\\VersionParser',
        'implementingClassName' => 'Composer\\Semver\\VersionParser',
        'currentClassName' => 'Composer\\Semver\\VersionParser',
        'aliasName' => NULL,
      ),
      'parseConstraints' => 
      array (
        'name' => 'parseConstraints',
        'parameters' => 
        array (
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
            'startLine' => 258,
            'endLine' => 258,
            'startColumn' => 38,
            'endColumn' => 49,
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
 * Parses a constraint string into MultiConstraint and/or Constraint objects.
 *
 * @param string $constraints
 *
 * @return ConstraintInterface
 */',
        'startLine' => 258,
        'endLine' => 298,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Composer\\Semver',
        'declaringClassName' => 'Composer\\Semver\\VersionParser',
        'implementingClassName' => 'Composer\\Semver\\VersionParser',
        'currentClassName' => 'Composer\\Semver\\VersionParser',
        'aliasName' => NULL,
      ),
      'parseConstraint' => 
      array (
        'name' => 'parseConstraint',
        'parameters' => 
        array (
          'constraint' => 
          array (
            'name' => 'constraint',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 309,
            'endLine' => 309,
            'startColumn' => 38,
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
 * @param string $constraint
 *
 * @throws \\UnexpectedValueException
 *
 * @return array
 *
 * @phpstan-return non-empty-array<ConstraintInterface>
 */',
        'startLine' => 309,
        'endLine' => 527,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'Composer\\Semver',
        'declaringClassName' => 'Composer\\Semver\\VersionParser',
        'implementingClassName' => 'Composer\\Semver\\VersionParser',
        'currentClassName' => 'Composer\\Semver\\VersionParser',
        'aliasName' => NULL,
      ),
      'manipulateVersionString' => 
      array (
        'name' => 'manipulateVersionString',
        'parameters' => 
        array (
          'matches' => 
          array (
            'name' => 'matches',
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
            'startLine' => 543,
            'endLine' => 543,
            'startColumn' => 46,
            'endColumn' => 59,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'position' => 
          array (
            'name' => 'position',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 543,
            'endLine' => 543,
            'startColumn' => 62,
            'endColumn' => 70,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'increment' => 
          array (
            'name' => 'increment',
            'default' => 
            array (
              'code' => '0',
              'attributes' => 
              array (
                'startLine' => 543,
                'endLine' => 543,
                'startTokenPos' => 3729,
                'startFilePos' => 20804,
                'endTokenPos' => 3729,
                'endFilePos' => 20804,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 543,
            'endLine' => 543,
            'startColumn' => 73,
            'endColumn' => 86,
            'parameterIndex' => 2,
            'isOptional' => true,
          ),
          'pad' => 
          array (
            'name' => 'pad',
            'default' => 
            array (
              'code' => '\'0\'',
              'attributes' => 
              array (
                'startLine' => 543,
                'endLine' => 543,
                'startTokenPos' => 3736,
                'startFilePos' => 20814,
                'endTokenPos' => 3736,
                'endFilePos' => 20816,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 543,
            'endLine' => 543,
            'startColumn' => 89,
            'endColumn' => 98,
            'parameterIndex' => 3,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Increment, decrement, or simply pad a version number.
 *
 * Support function for {@link parseConstraint()}
 *
 * @param array  $matches   Array with version parts in array indexes 1,2,3,4
 * @param int    $position  1,2,3,4 - which segment of the version to increment/decrement
 * @param int    $increment
 * @param string $pad       The string to pad version parts after $position
 *
 * @return string|null The new version
 *
 * @phpstan-param string[] $matches
 */',
        'startLine' => 543,
        'endLine' => 564,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'Composer\\Semver',
        'declaringClassName' => 'Composer\\Semver\\VersionParser',
        'implementingClassName' => 'Composer\\Semver\\VersionParser',
        'currentClassName' => 'Composer\\Semver\\VersionParser',
        'aliasName' => NULL,
      ),
      'expandStability' => 
      array (
        'name' => 'expandStability',
        'parameters' => 
        array (
          'stability' => 
          array (
            'name' => 'stability',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 573,
            'endLine' => 573,
            'startColumn' => 38,
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
 * Expand shorthand stability string to long version.
 *
 * @param string $stability
 *
 * @return string
 */',
        'startLine' => 573,
        'endLine' => 590,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'Composer\\Semver',
        'declaringClassName' => 'Composer\\Semver\\VersionParser',
        'implementingClassName' => 'Composer\\Semver\\VersionParser',
        'currentClassName' => 'Composer\\Semver\\VersionParser',
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