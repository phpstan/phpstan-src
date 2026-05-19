<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/src/Php/PhpVersions.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\Php\PhpVersions
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-4eb9927288bceea1998389fb68a12d091df8e64e698c2706419d68b7ced6f8b4',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\Php\\PhpVersions',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/src/Php/PhpVersions.php',
      ),
    ),
    'namespace' => 'PHPStan\\Php',
    'name' => 'PHPStan\\Php\\PhpVersions',
    'shortName' => 'PhpVersions',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Range-aware PHP version check that handles version uncertainty.
 *
 * Unlike PhpVersion (which represents a single known version), PhpVersions wraps
 * a Type representing the possible PHP versions. When the exact version is known,
 * queries return Yes/No. When a range of versions is possible, queries return Maybe.
 *
 * This is the return type of Scope::getPhpVersion().
 *
 * @api
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 20,
    'endLine' => 64,
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
      'phpVersions' => 
      array (
        'declaringClassName' => 'PHPStan\\Php\\PhpVersions',
        'implementingClassName' => 'PHPStan\\Php\\PhpVersions',
        'name' => 'phpVersions',
        'modifiers' => 4,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPStan\\Type\\Type',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 24,
        'endLine' => 24,
        'startColumn' => 3,
        'endColumn' => 27,
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
          'phpVersions' => 
          array (
            'name' => 'phpVersions',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\Type\\Type',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 24,
            'endLine' => 24,
            'startColumn' => 3,
            'endColumn' => 27,
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
        'startLine' => 23,
        'endLine' => 27,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Php',
        'declaringClassName' => 'PHPStan\\Php\\PhpVersions',
        'implementingClassName' => 'PHPStan\\Php\\PhpVersions',
        'currentClassName' => 'PHPStan\\Php\\PhpVersions',
        'aliasName' => NULL,
      ),
      'getType' => 
      array (
        'name' => 'getType',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPStan\\Type\\Type',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 29,
        'endLine' => 32,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Php',
        'declaringClassName' => 'PHPStan\\Php\\PhpVersions',
        'implementingClassName' => 'PHPStan\\Php\\PhpVersions',
        'currentClassName' => 'PHPStan\\Php\\PhpVersions',
        'aliasName' => NULL,
      ),
      'supportsNoncapturingCatches' => 
      array (
        'name' => 'supportsNoncapturingCatches',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPStan\\TrinaryLogic',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 34,
        'endLine' => 37,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Php',
        'declaringClassName' => 'PHPStan\\Php\\PhpVersions',
        'implementingClassName' => 'PHPStan\\Php\\PhpVersions',
        'currentClassName' => 'PHPStan\\Php\\PhpVersions',
        'aliasName' => NULL,
      ),
      'producesWarningForFinalPrivateMethods' => 
      array (
        'name' => 'producesWarningForFinalPrivateMethods',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPStan\\TrinaryLogic',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 39,
        'endLine' => 42,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Php',
        'declaringClassName' => 'PHPStan\\Php\\PhpVersions',
        'implementingClassName' => 'PHPStan\\Php\\PhpVersions',
        'currentClassName' => 'PHPStan\\Php\\PhpVersions',
        'aliasName' => NULL,
      ),
      'supportsNamedArguments' => 
      array (
        'name' => 'supportsNamedArguments',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPStan\\TrinaryLogic',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 44,
        'endLine' => 47,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Php',
        'declaringClassName' => 'PHPStan\\Php\\PhpVersions',
        'implementingClassName' => 'PHPStan\\Php\\PhpVersions',
        'currentClassName' => 'PHPStan\\Php\\PhpVersions',
        'aliasName' => NULL,
      ),
      'supportsNamedArgumentAfterUnpackedArgument' => 
      array (
        'name' => 'supportsNamedArgumentAfterUnpackedArgument',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPStan\\TrinaryLogic',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 49,
        'endLine' => 52,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Php',
        'declaringClassName' => 'PHPStan\\Php\\PhpVersions',
        'implementingClassName' => 'PHPStan\\Php\\PhpVersions',
        'currentClassName' => 'PHPStan\\Php\\PhpVersions',
        'aliasName' => NULL,
      ),
      'supportsTrueAndFalseStandaloneType' => 
      array (
        'name' => 'supportsTrueAndFalseStandaloneType',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPStan\\TrinaryLogic',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 54,
        'endLine' => 57,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Php',
        'declaringClassName' => 'PHPStan\\Php\\PhpVersions',
        'implementingClassName' => 'PHPStan\\Php\\PhpVersions',
        'currentClassName' => 'PHPStan\\Php\\PhpVersions',
        'aliasName' => NULL,
      ),
      'supportsMaxMemoryLimit' => 
      array (
        'name' => 'supportsMaxMemoryLimit',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPStan\\TrinaryLogic',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 59,
        'endLine' => 62,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Php',
        'declaringClassName' => 'PHPStan\\Php\\PhpVersions',
        'implementingClassName' => 'PHPStan\\Php\\PhpVersions',
        'currentClassName' => 'PHPStan\\Php\\PhpVersions',
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