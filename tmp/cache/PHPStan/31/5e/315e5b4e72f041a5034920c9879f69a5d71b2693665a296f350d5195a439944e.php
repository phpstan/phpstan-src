<?php declare(strict_types = 1);

// osfsl-/home/runner/work/phpstan-src/phpstan-src/vendor/composer/./semver/src/Constraint/ConstraintInterface.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Composer\Semver\Constraint\ConstraintInterface
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-510bb348c73ed1dac38e011cda1dd13bf1878664235bb1d3e9271ae88cb3ae16-8.4.21-6.70.0.1',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Composer\\Semver\\Constraint\\ConstraintInterface',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/vendor/composer/./semver/src/Constraint/ConstraintInterface.php',
      ),
    ),
    'namespace' => 'Composer\\Semver\\Constraint',
    'name' => 'Composer\\Semver\\Constraint\\ConstraintInterface',
    'shortName' => 'ConstraintInterface',
    'isInterface' => true,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * DO NOT IMPLEMENT this interface. It is only meant for usage as a type hint
 * in libraries relying on composer/semver but creating your own constraint class
 * that implements this interface is not a supported use case and will cause the
 * composer/semver components to return unexpected results.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 20,
    'endLine' => 75,
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
      'matches' => 
      array (
        'name' => 'matches',
        'parameters' => 
        array (
          'provider' => 
          array (
            'name' => 'provider',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Composer\\Semver\\Constraint\\ConstraintInterface',
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
            'startColumn' => 29,
            'endColumn' => 57,
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
 * Checks whether the given constraint intersects in any way with this constraint
 *
 * @param ConstraintInterface $provider
 *
 * @return bool
 */',
        'startLine' => 29,
        'endLine' => 29,
        'startColumn' => 5,
        'endColumn' => 59,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Composer\\Semver\\Constraint',
        'declaringClassName' => 'Composer\\Semver\\Constraint\\ConstraintInterface',
        'implementingClassName' => 'Composer\\Semver\\Constraint\\ConstraintInterface',
        'currentClassName' => 'Composer\\Semver\\Constraint\\ConstraintInterface',
        'aliasName' => NULL,
      ),
      'compile' => 
      array (
        'name' => 'compile',
        'parameters' => 
        array (
          'otherOperator' => 
          array (
            'name' => 'otherOperator',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 47,
            'endLine' => 47,
            'startColumn' => 29,
            'endColumn' => 42,
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
 * Provides a compiled version of the constraint for the given operator
 * The compiled version must be a PHP expression.
 * Executor of compile version must provide 2 variables:
 * - $v = the string version to compare with
 * - $b = whether or not the version is a non-comparable branch (starts with "dev-")
 *
 * @see Constraint::OP_* for the list of available operators.
 * @example return \'!$b && version_compare($v, \'1.0\', \'>\')\';
 *
 * @param int $otherOperator one Constraint::OP_*
 *
 * @return string
 *
 * @phpstan-param Constraint::OP_* $otherOperator
 */',
        'startLine' => 47,
        'endLine' => 47,
        'startColumn' => 5,
        'endColumn' => 44,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Composer\\Semver\\Constraint',
        'declaringClassName' => 'Composer\\Semver\\Constraint\\ConstraintInterface',
        'implementingClassName' => 'Composer\\Semver\\Constraint\\ConstraintInterface',
        'currentClassName' => 'Composer\\Semver\\Constraint\\ConstraintInterface',
        'aliasName' => NULL,
      ),
      'getUpperBound' => 
      array (
        'name' => 'getUpperBound',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @return Bound
 */',
        'startLine' => 52,
        'endLine' => 52,
        'startColumn' => 5,
        'endColumn' => 36,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Composer\\Semver\\Constraint',
        'declaringClassName' => 'Composer\\Semver\\Constraint\\ConstraintInterface',
        'implementingClassName' => 'Composer\\Semver\\Constraint\\ConstraintInterface',
        'currentClassName' => 'Composer\\Semver\\Constraint\\ConstraintInterface',
        'aliasName' => NULL,
      ),
      'getLowerBound' => 
      array (
        'name' => 'getLowerBound',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @return Bound
 */',
        'startLine' => 57,
        'endLine' => 57,
        'startColumn' => 5,
        'endColumn' => 36,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Composer\\Semver\\Constraint',
        'declaringClassName' => 'Composer\\Semver\\Constraint\\ConstraintInterface',
        'implementingClassName' => 'Composer\\Semver\\Constraint\\ConstraintInterface',
        'currentClassName' => 'Composer\\Semver\\Constraint\\ConstraintInterface',
        'aliasName' => NULL,
      ),
      'getPrettyString' => 
      array (
        'name' => 'getPrettyString',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @return string
 */',
        'startLine' => 62,
        'endLine' => 62,
        'startColumn' => 5,
        'endColumn' => 38,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Composer\\Semver\\Constraint',
        'declaringClassName' => 'Composer\\Semver\\Constraint\\ConstraintInterface',
        'implementingClassName' => 'Composer\\Semver\\Constraint\\ConstraintInterface',
        'currentClassName' => 'Composer\\Semver\\Constraint\\ConstraintInterface',
        'aliasName' => NULL,
      ),
      'setPrettyString' => 
      array (
        'name' => 'setPrettyString',
        'parameters' => 
        array (
          'prettyString' => 
          array (
            'name' => 'prettyString',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 69,
            'endLine' => 69,
            'startColumn' => 37,
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
 * @param string|null $prettyString
 *
 * @return void
 */',
        'startLine' => 69,
        'endLine' => 69,
        'startColumn' => 5,
        'endColumn' => 51,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Composer\\Semver\\Constraint',
        'declaringClassName' => 'Composer\\Semver\\Constraint\\ConstraintInterface',
        'implementingClassName' => 'Composer\\Semver\\Constraint\\ConstraintInterface',
        'currentClassName' => 'Composer\\Semver\\Constraint\\ConstraintInterface',
        'aliasName' => NULL,
      ),
      '__toString' => 
      array (
        'name' => '__toString',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @return string
 */',
        'startLine' => 74,
        'endLine' => 74,
        'startColumn' => 5,
        'endColumn' => 33,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Composer\\Semver\\Constraint',
        'declaringClassName' => 'Composer\\Semver\\Constraint\\ConstraintInterface',
        'implementingClassName' => 'Composer\\Semver\\Constraint\\ConstraintInterface',
        'currentClassName' => 'Composer\\Semver\\Constraint\\ConstraintInterface',
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