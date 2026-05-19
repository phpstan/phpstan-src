<?php declare(strict_types = 1);

// osfsl-/home/runner/work/phpstan-src/phpstan-src/vendor/composer/../nikic/php-parser/lib/PhpParser/PhpVersion.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PhpParser\PhpVersion
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-86d2acc2619dbbab0e62149b43f264ba9e804bbc520f02a53073d9960ec1c7f9-8.4.21-6.70.0.1',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PhpParser\\PhpVersion',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/vendor/composer/../nikic/php-parser/lib/PhpParser/PhpVersion.php',
      ),
    ),
    'namespace' => 'PhpParser',
    'name' => 'PhpParser\\PhpVersion',
    'shortName' => 'PhpVersion',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * A PHP version, representing only the major and minor version components.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 8,
    'endLine' => 175,
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
      'BUILTIN_TYPE_VERSIONS' => 
      array (
        'declaringClassName' => 'PhpParser\\PhpVersion',
        'implementingClassName' => 'PhpParser\\PhpVersion',
        'name' => 'BUILTIN_TYPE_VERSIONS',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'array\' => 50100, \'callable\' => 50400, \'bool\' => 70000, \'int\' => 70000, \'float\' => 70000, \'string\' => 70000, \'iterable\' => 70100, \'void\' => 70100, \'object\' => 70200, \'null\' => 80000, \'false\' => 80000, \'mixed\' => 80000, \'never\' => 80100, \'true\' => 80200]',
          'attributes' => 
          array (
            'startLine' => 13,
            'endLine' => 28,
            'startTokenPos' => 41,
            'startFilePos' => 333,
            'endTokenPos' => 141,
            'endFilePos' => 745,
          ),
        ),
        'docComment' => '/** @var int[] Minimum versions for builtin types */',
        'attributes' => 
        array (
        ),
        'startLine' => 13,
        'endLine' => 28,
        'startColumn' => 5,
        'endColumn' => 6,
      ),
    ),
    'immediateProperties' => 
    array (
      'id' => 
      array (
        'declaringClassName' => 'PhpParser\\PhpVersion',
        'implementingClassName' => 'PhpParser\\PhpVersion',
        'name' => 'id',
        'modifiers' => 1,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'int',
            'isIdentifier' => true,
          ),
        ),
        'default' => NULL,
        'docComment' => '/** @var int Version ID in PHP_VERSION_ID format */',
        'attributes' => 
        array (
        ),
        'startLine' => 10,
        'endLine' => 10,
        'startColumn' => 5,
        'endColumn' => 19,
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
      '__construct' => 
      array (
        'name' => '__construct',
        'parameters' => 
        array (
          'id' => 
          array (
            'name' => 'id',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'int',
                'isIdentifier' => true,
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
            'startColumn' => 34,
            'endColumn' => 40,
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
        'startLine' => 30,
        'endLine' => 32,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'PhpParser',
        'declaringClassName' => 'PhpParser\\PhpVersion',
        'implementingClassName' => 'PhpParser\\PhpVersion',
        'currentClassName' => 'PhpParser\\PhpVersion',
        'aliasName' => NULL,
      ),
      'fromComponents' => 
      array (
        'name' => 'fromComponents',
        'parameters' => 
        array (
          'major' => 
          array (
            'name' => 'major',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'int',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 37,
            'endLine' => 37,
            'startColumn' => 43,
            'endColumn' => 52,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'minor' => 
          array (
            'name' => 'minor',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'int',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 37,
            'endLine' => 37,
            'startColumn' => 55,
            'endColumn' => 64,
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
            'name' => 'self',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Create a PhpVersion object from major and minor version components.
 */',
        'startLine' => 37,
        'endLine' => 39,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'PhpParser',
        'declaringClassName' => 'PhpParser\\PhpVersion',
        'implementingClassName' => 'PhpParser\\PhpVersion',
        'currentClassName' => 'PhpParser\\PhpVersion',
        'aliasName' => NULL,
      ),
      'getNewestSupported' => 
      array (
        'name' => 'getNewestSupported',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'self',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the newest PHP version supported by this library. Support for this version may be partial,
 * if it is still under development.
 */',
        'startLine' => 45,
        'endLine' => 47,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'PhpParser',
        'declaringClassName' => 'PhpParser\\PhpVersion',
        'implementingClassName' => 'PhpParser\\PhpVersion',
        'currentClassName' => 'PhpParser\\PhpVersion',
        'aliasName' => NULL,
      ),
      'getHostVersion' => 
      array (
        'name' => 'getHostVersion',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'self',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the host PHP version, that is the PHP version we\'re currently running on.
 */',
        'startLine' => 52,
        'endLine' => 54,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'PhpParser',
        'declaringClassName' => 'PhpParser\\PhpVersion',
        'implementingClassName' => 'PhpParser\\PhpVersion',
        'currentClassName' => 'PhpParser\\PhpVersion',
        'aliasName' => NULL,
      ),
      'fromString' => 
      array (
        'name' => 'fromString',
        'parameters' => 
        array (
          'version' => 
          array (
            'name' => 'version',
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
            'startLine' => 59,
            'endLine' => 59,
            'startColumn' => 39,
            'endColumn' => 53,
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
            'name' => 'self',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Parse the version from a string like "8.1".
 */',
        'startLine' => 59,
        'endLine' => 64,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'PhpParser',
        'declaringClassName' => 'PhpParser\\PhpVersion',
        'implementingClassName' => 'PhpParser\\PhpVersion',
        'currentClassName' => 'PhpParser\\PhpVersion',
        'aliasName' => NULL,
      ),
      'equals' => 
      array (
        'name' => 'equals',
        'parameters' => 
        array (
          'other' => 
          array (
            'name' => 'other',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PhpParser\\PhpVersion',
                'isIdentifier' => false,
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
            'startColumn' => 28,
            'endColumn' => 44,
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
 * Check whether two versions are the same.
 */',
        'startLine' => 69,
        'endLine' => 71,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpParser',
        'declaringClassName' => 'PhpParser\\PhpVersion',
        'implementingClassName' => 'PhpParser\\PhpVersion',
        'currentClassName' => 'PhpParser\\PhpVersion',
        'aliasName' => NULL,
      ),
      'newerOrEqual' => 
      array (
        'name' => 'newerOrEqual',
        'parameters' => 
        array (
          'other' => 
          array (
            'name' => 'other',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PhpParser\\PhpVersion',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 76,
            'endLine' => 76,
            'startColumn' => 34,
            'endColumn' => 50,
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
 * Check whether this version is greater than or equal to the argument.
 */',
        'startLine' => 76,
        'endLine' => 78,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpParser',
        'declaringClassName' => 'PhpParser\\PhpVersion',
        'implementingClassName' => 'PhpParser\\PhpVersion',
        'currentClassName' => 'PhpParser\\PhpVersion',
        'aliasName' => NULL,
      ),
      'older' => 
      array (
        'name' => 'older',
        'parameters' => 
        array (
          'other' => 
          array (
            'name' => 'other',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PhpParser\\PhpVersion',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 83,
            'endLine' => 83,
            'startColumn' => 27,
            'endColumn' => 43,
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
 * Check whether this version is older than the argument.
 */',
        'startLine' => 83,
        'endLine' => 85,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpParser',
        'declaringClassName' => 'PhpParser\\PhpVersion',
        'implementingClassName' => 'PhpParser\\PhpVersion',
        'currentClassName' => 'PhpParser\\PhpVersion',
        'aliasName' => NULL,
      ),
      'isHostVersion' => 
      array (
        'name' => 'isHostVersion',
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
 * Check whether this is the host PHP version.
 */',
        'startLine' => 90,
        'endLine' => 92,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpParser',
        'declaringClassName' => 'PhpParser\\PhpVersion',
        'implementingClassName' => 'PhpParser\\PhpVersion',
        'currentClassName' => 'PhpParser\\PhpVersion',
        'aliasName' => NULL,
      ),
      'supportsBuiltinType' => 
      array (
        'name' => 'supportsBuiltinType',
        'parameters' => 
        array (
          'type' => 
          array (
            'name' => 'type',
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
            'startLine' => 97,
            'endLine' => 97,
            'startColumn' => 41,
            'endColumn' => 52,
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
 * Check whether this PHP version supports the given builtin type. Type name must be lowercase.
 */',
        'startLine' => 97,
        'endLine' => 100,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpParser',
        'declaringClassName' => 'PhpParser\\PhpVersion',
        'implementingClassName' => 'PhpParser\\PhpVersion',
        'currentClassName' => 'PhpParser\\PhpVersion',
        'aliasName' => NULL,
      ),
      'supportsShortArraySyntax' => 
      array (
        'name' => 'supportsShortArraySyntax',
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
 * Whether this version supports [] array literals.
 */',
        'startLine' => 105,
        'endLine' => 107,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpParser',
        'declaringClassName' => 'PhpParser\\PhpVersion',
        'implementingClassName' => 'PhpParser\\PhpVersion',
        'currentClassName' => 'PhpParser\\PhpVersion',
        'aliasName' => NULL,
      ),
      'supportsShortArrayDestructuring' => 
      array (
        'name' => 'supportsShortArrayDestructuring',
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
 * Whether this version supports [] for destructuring.
 */',
        'startLine' => 112,
        'endLine' => 114,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpParser',
        'declaringClassName' => 'PhpParser\\PhpVersion',
        'implementingClassName' => 'PhpParser\\PhpVersion',
        'currentClassName' => 'PhpParser\\PhpVersion',
        'aliasName' => NULL,
      ),
      'supportsFlexibleHeredoc' => 
      array (
        'name' => 'supportsFlexibleHeredoc',
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
 * Whether this version supports flexible heredoc/nowdoc.
 */',
        'startLine' => 119,
        'endLine' => 121,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpParser',
        'declaringClassName' => 'PhpParser\\PhpVersion',
        'implementingClassName' => 'PhpParser\\PhpVersion',
        'currentClassName' => 'PhpParser\\PhpVersion',
        'aliasName' => NULL,
      ),
      'supportsTrailingCommaInParamList' => 
      array (
        'name' => 'supportsTrailingCommaInParamList',
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
 * Whether this version supports trailing commas in parameter lists.
 */',
        'startLine' => 126,
        'endLine' => 128,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpParser',
        'declaringClassName' => 'PhpParser\\PhpVersion',
        'implementingClassName' => 'PhpParser\\PhpVersion',
        'currentClassName' => 'PhpParser\\PhpVersion',
        'aliasName' => NULL,
      ),
      'allowsAssignNewByReference' => 
      array (
        'name' => 'allowsAssignNewByReference',
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
 * Whether this version allows "$var =& new Obj".
 */',
        'startLine' => 133,
        'endLine' => 135,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpParser',
        'declaringClassName' => 'PhpParser\\PhpVersion',
        'implementingClassName' => 'PhpParser\\PhpVersion',
        'currentClassName' => 'PhpParser\\PhpVersion',
        'aliasName' => NULL,
      ),
      'allowsInvalidOctals' => 
      array (
        'name' => 'allowsInvalidOctals',
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
 * Whether this version allows invalid octals like "08".
 */',
        'startLine' => 140,
        'endLine' => 142,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpParser',
        'declaringClassName' => 'PhpParser\\PhpVersion',
        'implementingClassName' => 'PhpParser\\PhpVersion',
        'currentClassName' => 'PhpParser\\PhpVersion',
        'aliasName' => NULL,
      ),
      'allowsDelInIdentifiers' => 
      array (
        'name' => 'allowsDelInIdentifiers',
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
 * Whether this version allows DEL (\\x7f) to occur in identifiers.
 */',
        'startLine' => 147,
        'endLine' => 149,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpParser',
        'declaringClassName' => 'PhpParser\\PhpVersion',
        'implementingClassName' => 'PhpParser\\PhpVersion',
        'currentClassName' => 'PhpParser\\PhpVersion',
        'aliasName' => NULL,
      ),
      'supportsYieldWithoutParentheses' => 
      array (
        'name' => 'supportsYieldWithoutParentheses',
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
 * Whether this version supports yield in expression context without parentheses.
 */',
        'startLine' => 154,
        'endLine' => 156,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpParser',
        'declaringClassName' => 'PhpParser\\PhpVersion',
        'implementingClassName' => 'PhpParser\\PhpVersion',
        'currentClassName' => 'PhpParser\\PhpVersion',
        'aliasName' => NULL,
      ),
      'supportsUnicodeEscapes' => 
      array (
        'name' => 'supportsUnicodeEscapes',
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
 * Whether this version supports unicode escape sequences in strings.
 */',
        'startLine' => 161,
        'endLine' => 163,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpParser',
        'declaringClassName' => 'PhpParser\\PhpVersion',
        'implementingClassName' => 'PhpParser\\PhpVersion',
        'currentClassName' => 'PhpParser\\PhpVersion',
        'aliasName' => NULL,
      ),
      'supportsAttributes' => 
      array (
        'name' => 'supportsAttributes',
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
        'docComment' => NULL,
        'startLine' => 168,
        'endLine' => 170,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpParser',
        'declaringClassName' => 'PhpParser\\PhpVersion',
        'implementingClassName' => 'PhpParser\\PhpVersion',
        'currentClassName' => 'PhpParser\\PhpVersion',
        'aliasName' => NULL,
      ),
      'supportsNewDereferenceWithoutParentheses' => 
      array (
        'name' => 'supportsNewDereferenceWithoutParentheses',
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
        'docComment' => NULL,
        'startLine' => 172,
        'endLine' => 174,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpParser',
        'declaringClassName' => 'PhpParser\\PhpVersion',
        'implementingClassName' => 'PhpParser\\PhpVersion',
        'currentClassName' => 'PhpParser\\PhpVersion',
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