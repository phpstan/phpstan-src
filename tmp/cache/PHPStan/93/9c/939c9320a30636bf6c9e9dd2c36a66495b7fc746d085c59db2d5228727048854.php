<?php declare(strict_types = 1);

// osfsl-/home/runner/work/phpstan-src/phpstan-src/vendor/composer/../ondram/ci-detector/src/Ci/CiInterface.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OndraM\CiDetector\Ci\CiInterface
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-0d7c0c92a6cda6d2d5cc826ca3025ef73d1700f0d87ca013af203ed3509576a1-8.4.21-6.70.0.1',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OndraM\\CiDetector\\Ci\\CiInterface',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/vendor/composer/../ondram/ci-detector/src/Ci/CiInterface.php',
      ),
    ),
    'namespace' => 'OndraM\\CiDetector\\Ci',
    'name' => 'OndraM\\CiDetector\\Ci\\CiInterface',
    'shortName' => 'CiInterface',
    'isInterface' => true,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => NULL,
    'attributes' => 
    array (
    ),
    'startLine' => 8,
    'endLine' => 78,
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
      'isDetected' => 
      array (
        'name' => 'isDetected',
        'parameters' => 
        array (
          'env' => 
          array (
            'name' => 'env',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OndraM\\CiDetector\\Env',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 13,
            'endLine' => 13,
            'startColumn' => 39,
            'endColumn' => 46,
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
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Return true if this CI was detected.
 */',
        'startLine' => 13,
        'endLine' => 13,
        'startColumn' => 5,
        'endColumn' => 54,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'OndraM\\CiDetector\\Ci',
        'declaringClassName' => 'OndraM\\CiDetector\\Ci\\CiInterface',
        'implementingClassName' => 'OndraM\\CiDetector\\Ci\\CiInterface',
        'currentClassName' => 'OndraM\\CiDetector\\Ci\\CiInterface',
        'aliasName' => NULL,
      ),
      'getCiName' => 
      array (
        'name' => 'getCiName',
        'parameters' => 
        array (
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
 * Get name of the CI server type.
 */',
        'startLine' => 18,
        'endLine' => 18,
        'startColumn' => 5,
        'endColumn' => 40,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OndraM\\CiDetector\\Ci',
        'declaringClassName' => 'OndraM\\CiDetector\\Ci\\CiInterface',
        'implementingClassName' => 'OndraM\\CiDetector\\Ci\\CiInterface',
        'currentClassName' => 'OndraM\\CiDetector\\Ci\\CiInterface',
        'aliasName' => NULL,
      ),
      'describe' => 
      array (
        'name' => 'describe',
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
 * Return key-value map of all detected properties in human-readable form.
 *
 * @return array<string, string>
 */',
        'startLine' => 25,
        'endLine' => 25,
        'startColumn' => 5,
        'endColumn' => 38,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OndraM\\CiDetector\\Ci',
        'declaringClassName' => 'OndraM\\CiDetector\\Ci\\CiInterface',
        'implementingClassName' => 'OndraM\\CiDetector\\Ci\\CiInterface',
        'currentClassName' => 'OndraM\\CiDetector\\Ci\\CiInterface',
        'aliasName' => NULL,
      ),
      'getBuildNumber' => 
      array (
        'name' => 'getBuildNumber',
        'parameters' => 
        array (
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
 * Get number of this concrete build.
 *
 * Build number is usually human-readable increasing number sequence. It should increase each time this particular
 * job was run on the CI server. Most CIs use simple numbering sequence like: 1, 2, 3...
 * However, some CIs do not provide this simple human-readable value and rather use for example alphanumeric hash.
 */',
        'startLine' => 34,
        'endLine' => 34,
        'startColumn' => 5,
        'endColumn' => 45,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OndraM\\CiDetector\\Ci',
        'declaringClassName' => 'OndraM\\CiDetector\\Ci\\CiInterface',
        'implementingClassName' => 'OndraM\\CiDetector\\Ci\\CiInterface',
        'currentClassName' => 'OndraM\\CiDetector\\Ci\\CiInterface',
        'aliasName' => NULL,
      ),
      'getBuildUrl' => 
      array (
        'name' => 'getBuildUrl',
        'parameters' => 
        array (
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
 * Get URL where this build can be found and viewed.
 */',
        'startLine' => 39,
        'endLine' => 39,
        'startColumn' => 5,
        'endColumn' => 42,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OndraM\\CiDetector\\Ci',
        'declaringClassName' => 'OndraM\\CiDetector\\Ci\\CiInterface',
        'implementingClassName' => 'OndraM\\CiDetector\\Ci\\CiInterface',
        'currentClassName' => 'OndraM\\CiDetector\\Ci\\CiInterface',
        'aliasName' => NULL,
      ),
      'getCommit' => 
      array (
        'name' => 'getCommit',
        'parameters' => 
        array (
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
 * Get hash of the git (or other VCS) commit being built.
 */',
        'startLine' => 44,
        'endLine' => 44,
        'startColumn' => 5,
        'endColumn' => 40,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OndraM\\CiDetector\\Ci',
        'declaringClassName' => 'OndraM\\CiDetector\\Ci\\CiInterface',
        'implementingClassName' => 'OndraM\\CiDetector\\Ci\\CiInterface',
        'currentClassName' => 'OndraM\\CiDetector\\Ci\\CiInterface',
        'aliasName' => NULL,
      ),
      'getBranch' => 
      array (
        'name' => 'getBranch',
        'parameters' => 
        array (
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
 * Get name of the git (or other VCS) branch which is being built.
 *
 * Use `getTargetBranch()` to get name of the branch where this branch is targeted.
 */',
        'startLine' => 51,
        'endLine' => 51,
        'startColumn' => 5,
        'endColumn' => 40,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OndraM\\CiDetector\\Ci',
        'declaringClassName' => 'OndraM\\CiDetector\\Ci\\CiInterface',
        'implementingClassName' => 'OndraM\\CiDetector\\Ci\\CiInterface',
        'currentClassName' => 'OndraM\\CiDetector\\Ci\\CiInterface',
        'aliasName' => NULL,
      ),
      'getTargetBranch' => 
      array (
        'name' => 'getTargetBranch',
        'parameters' => 
        array (
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
 * Get name of the target branch of a pull request.
 */',
        'startLine' => 56,
        'endLine' => 56,
        'startColumn' => 5,
        'endColumn' => 46,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OndraM\\CiDetector\\Ci',
        'declaringClassName' => 'OndraM\\CiDetector\\Ci\\CiInterface',
        'implementingClassName' => 'OndraM\\CiDetector\\Ci\\CiInterface',
        'currentClassName' => 'OndraM\\CiDetector\\Ci\\CiInterface',
        'aliasName' => NULL,
      ),
      'getRepositoryName' => 
      array (
        'name' => 'getRepositoryName',
        'parameters' => 
        array (
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
 * Get name of the git (or other VCS) repository which is being built.
 *
 * This is usually in form "user/repository", for example "OndraM/ci-detector".
 */',
        'startLine' => 63,
        'endLine' => 63,
        'startColumn' => 5,
        'endColumn' => 48,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OndraM\\CiDetector\\Ci',
        'declaringClassName' => 'OndraM\\CiDetector\\Ci\\CiInterface',
        'implementingClassName' => 'OndraM\\CiDetector\\Ci\\CiInterface',
        'currentClassName' => 'OndraM\\CiDetector\\Ci\\CiInterface',
        'aliasName' => NULL,
      ),
      'getRepositoryUrl' => 
      array (
        'name' => 'getRepositoryUrl',
        'parameters' => 
        array (
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
 * Get URL where the repository which is being built can be found.
 *
 * This is either HTTP URL like "https://github.com/OndraM/ci-detector"
 * but may be a git ssh url like "ssh://git@bitbucket.org/OndraM/ci-detector".
 */',
        'startLine' => 71,
        'endLine' => 71,
        'startColumn' => 5,
        'endColumn' => 47,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OndraM\\CiDetector\\Ci',
        'declaringClassName' => 'OndraM\\CiDetector\\Ci\\CiInterface',
        'implementingClassName' => 'OndraM\\CiDetector\\Ci\\CiInterface',
        'currentClassName' => 'OndraM\\CiDetector\\Ci\\CiInterface',
        'aliasName' => NULL,
      ),
      'isPullRequest' => 
      array (
        'name' => 'isPullRequest',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OndraM\\CiDetector\\TrinaryLogic',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Returned TrinaryLogic object\'s value will be true if the current build is from a pull/merge request,
 * false if it not, and maybe if we can\'t determine it.
 */',
        'startLine' => 77,
        'endLine' => 77,
        'startColumn' => 5,
        'endColumn' => 50,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OndraM\\CiDetector\\Ci',
        'declaringClassName' => 'OndraM\\CiDetector\\Ci\\CiInterface',
        'implementingClassName' => 'OndraM\\CiDetector\\Ci\\CiInterface',
        'currentClassName' => 'OndraM\\CiDetector\\Ci\\CiInterface',
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