<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/src/Php/PhpVersion.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\Php\PhpVersion
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-a9879e36a2dd231f30ec5642a1b6589e373cc4a207b714bb50d8756c5ec4183d',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\Php\\PhpVersion',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/src/Php/PhpVersion.php',
      ),
    ),
    'namespace' => 'PHPStan\\Php',
    'name' => 'PHPStan\\Php\\PhpVersion',
    'shortName' => 'PhpVersion',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Represents a specific PHP version for version-dependent analysis behavior.
 *
 * The version is stored as PHP_VERSION_ID format (e.g. 80100 for PHP 8.1.0).
 * Extension developers can access it by injecting PhpVersion via constructor injection.
 *
 * @api
 */',
    'attributes' => 
    array (
      0 => 
      array (
        'name' => 'PHPStan\\DependencyInjection\\AutowiredService',
        'isRepeated' => false,
        'arguments' => 
        array (
          'factory' => 
          array (
            'code' => '\'@PHPStan\\Php\\PhpVersionFactory::create\'',
            'attributes' => 
            array (
              'startLine' => 16,
              'endLine' => 16,
              'startTokenPos' => 36,
              'startFilePos' => 424,
              'endTokenPos' => 36,
              'endFilePos' => 463,
            ),
          ),
        ),
      ),
    ),
    'startLine' => 16,
    'endLine' => 514,
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
      'SOURCE_RUNTIME' => 
      array (
        'declaringClassName' => 'PHPStan\\Php\\PhpVersion',
        'implementingClassName' => 'PHPStan\\Php\\PhpVersion',
        'name' => 'SOURCE_RUNTIME',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '1',
          'attributes' => 
          array (
            'startLine' => 20,
            'endLine' => 20,
            'startTokenPos' => 56,
            'startFilePos' => 524,
            'endTokenPos' => 56,
            'endFilePos' => 524,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 20,
        'endLine' => 20,
        'startColumn' => 2,
        'endColumn' => 33,
      ),
      'SOURCE_CONFIG' => 
      array (
        'declaringClassName' => 'PHPStan\\Php\\PhpVersion',
        'implementingClassName' => 'PHPStan\\Php\\PhpVersion',
        'name' => 'SOURCE_CONFIG',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '2',
          'attributes' => 
          array (
            'startLine' => 21,
            'endLine' => 21,
            'startTokenPos' => 67,
            'startFilePos' => 557,
            'endTokenPos' => 67,
            'endFilePos' => 557,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 21,
        'endLine' => 21,
        'startColumn' => 2,
        'endColumn' => 32,
      ),
      'SOURCE_COMPOSER_PLATFORM_PHP' => 
      array (
        'declaringClassName' => 'PHPStan\\Php\\PhpVersion',
        'implementingClassName' => 'PHPStan\\Php\\PhpVersion',
        'name' => 'SOURCE_COMPOSER_PLATFORM_PHP',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '3',
          'attributes' => 
          array (
            'startLine' => 22,
            'endLine' => 22,
            'startTokenPos' => 78,
            'startFilePos' => 605,
            'endTokenPos' => 78,
            'endFilePos' => 605,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 22,
        'endLine' => 22,
        'startColumn' => 2,
        'endColumn' => 47,
      ),
      'SOURCE_UNKNOWN' => 
      array (
        'declaringClassName' => 'PHPStan\\Php\\PhpVersion',
        'implementingClassName' => 'PHPStan\\Php\\PhpVersion',
        'name' => 'SOURCE_UNKNOWN',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '4',
          'attributes' => 
          array (
            'startLine' => 23,
            'endLine' => 23,
            'startTokenPos' => 89,
            'startFilePos' => 639,
            'endTokenPos' => 89,
            'endFilePos' => 639,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 23,
        'endLine' => 23,
        'startColumn' => 2,
        'endColumn' => 33,
      ),
    ),
    'immediateProperties' => 
    array (
      'versionId' => 
      array (
        'declaringClassName' => 'PHPStan\\Php\\PhpVersion',
        'implementingClassName' => 'PHPStan\\Php\\PhpVersion',
        'name' => 'versionId',
        'modifiers' => 4,
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
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 29,
        'endLine' => 29,
        'startColumn' => 30,
        'endColumn' => 51,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'source' => 
      array (
        'declaringClassName' => 'PHPStan\\Php\\PhpVersion',
        'implementingClassName' => 'PHPStan\\Php\\PhpVersion',
        'name' => 'source',
        'modifiers' => 4,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'int',
            'isIdentifier' => true,
          ),
        ),
        'default' => 
        array (
          'code' => 'self::SOURCE_UNKNOWN',
          'attributes' => 
          array (
            'startLine' => 29,
            'endLine' => 29,
            'startTokenPos' => 115,
            'startFilePos' => 771,
            'endTokenPos' => 117,
            'endFilePos' => 790,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 29,
        'endLine' => 29,
        'startColumn' => 54,
        'endColumn' => 95,
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
          'versionId' => 
          array (
            'name' => 'versionId',
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
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 29,
            'endLine' => 29,
            'startColumn' => 30,
            'endColumn' => 51,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'source' => 
          array (
            'name' => 'source',
            'default' => 
            array (
              'code' => 'self::SOURCE_UNKNOWN',
              'attributes' => 
              array (
                'startLine' => 29,
                'endLine' => 29,
                'startTokenPos' => 115,
                'startFilePos' => 771,
                'endTokenPos' => 117,
                'endFilePos' => 790,
              ),
            ),
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
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 29,
            'endLine' => 29,
            'startColumn' => 54,
            'endColumn' => 95,
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
 * @api
 * @param self::SOURCE_* $source
 */',
        'startLine' => 29,
        'endLine' => 31,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Php',
        'declaringClassName' => 'PHPStan\\Php\\PhpVersion',
        'implementingClassName' => 'PHPStan\\Php\\PhpVersion',
        'currentClassName' => 'PHPStan\\Php\\PhpVersion',
        'aliasName' => NULL,
      ),
      'getSource' => 
      array (
        'name' => 'getSource',
        'parameters' => 
        array (
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
 * @return self::SOURCE_*
 */',
        'startLine' => 36,
        'endLine' => 39,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Php',
        'declaringClassName' => 'PHPStan\\Php\\PhpVersion',
        'implementingClassName' => 'PHPStan\\Php\\PhpVersion',
        'currentClassName' => 'PHPStan\\Php\\PhpVersion',
        'aliasName' => NULL,
      ),
      'getSourceLabel' => 
      array (
        'name' => 'getSourceLabel',
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
        'docComment' => NULL,
        'startLine' => 41,
        'endLine' => 53,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Php',
        'declaringClassName' => 'PHPStan\\Php\\PhpVersion',
        'implementingClassName' => 'PHPStan\\Php\\PhpVersion',
        'currentClassName' => 'PHPStan\\Php\\PhpVersion',
        'aliasName' => NULL,
      ),
      'getVersionId' => 
      array (
        'name' => 'getVersionId',
        'parameters' => 
        array (
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
        'startLine' => 55,
        'endLine' => 58,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Php',
        'declaringClassName' => 'PHPStan\\Php\\PhpVersion',
        'implementingClassName' => 'PHPStan\\Php\\PhpVersion',
        'currentClassName' => 'PHPStan\\Php\\PhpVersion',
        'aliasName' => NULL,
      ),
      'getMajorVersionId' => 
      array (
        'name' => 'getMajorVersionId',
        'parameters' => 
        array (
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
        'startLine' => 60,
        'endLine' => 63,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Php',
        'declaringClassName' => 'PHPStan\\Php\\PhpVersion',
        'implementingClassName' => 'PHPStan\\Php\\PhpVersion',
        'currentClassName' => 'PHPStan\\Php\\PhpVersion',
        'aliasName' => NULL,
      ),
      'getMinorVersionId' => 
      array (
        'name' => 'getMinorVersionId',
        'parameters' => 
        array (
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
        'startLine' => 65,
        'endLine' => 68,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Php',
        'declaringClassName' => 'PHPStan\\Php\\PhpVersion',
        'implementingClassName' => 'PHPStan\\Php\\PhpVersion',
        'currentClassName' => 'PHPStan\\Php\\PhpVersion',
        'aliasName' => NULL,
      ),
      'getPatchVersionId' => 
      array (
        'name' => 'getPatchVersionId',
        'parameters' => 
        array (
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
        'startLine' => 70,
        'endLine' => 73,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Php',
        'declaringClassName' => 'PHPStan\\Php\\PhpVersion',
        'implementingClassName' => 'PHPStan\\Php\\PhpVersion',
        'currentClassName' => 'PHPStan\\Php\\PhpVersion',
        'aliasName' => NULL,
      ),
      'getVersionString' => 
      array (
        'name' => 'getVersionString',
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
        'docComment' => NULL,
        'startLine' => 75,
        'endLine' => 82,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Php',
        'declaringClassName' => 'PHPStan\\Php\\PhpVersion',
        'implementingClassName' => 'PHPStan\\Php\\PhpVersion',
        'currentClassName' => 'PHPStan\\Php\\PhpVersion',
        'aliasName' => NULL,
      ),
      'supportsNullCoalesceAssign' => 
      array (
        'name' => 'supportsNullCoalesceAssign',
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
        'startLine' => 84,
        'endLine' => 87,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Php',
        'declaringClassName' => 'PHPStan\\Php\\PhpVersion',
        'implementingClassName' => 'PHPStan\\Php\\PhpVersion',
        'currentClassName' => 'PHPStan\\Php\\PhpVersion',
        'aliasName' => NULL,
      ),
      'supportsParameterContravariance' => 
      array (
        'name' => 'supportsParameterContravariance',
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
        'startLine' => 89,
        'endLine' => 92,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Php',
        'declaringClassName' => 'PHPStan\\Php\\PhpVersion',
        'implementingClassName' => 'PHPStan\\Php\\PhpVersion',
        'currentClassName' => 'PHPStan\\Php\\PhpVersion',
        'aliasName' => NULL,
      ),
      'supportsReturnCovariance' => 
      array (
        'name' => 'supportsReturnCovariance',
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
        'startLine' => 94,
        'endLine' => 97,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Php',
        'declaringClassName' => 'PHPStan\\Php\\PhpVersion',
        'implementingClassName' => 'PHPStan\\Php\\PhpVersion',
        'currentClassName' => 'PHPStan\\Php\\PhpVersion',
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
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 99,
        'endLine' => 102,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Php',
        'declaringClassName' => 'PHPStan\\Php\\PhpVersion',
        'implementingClassName' => 'PHPStan\\Php\\PhpVersion',
        'currentClassName' => 'PHPStan\\Php\\PhpVersion',
        'aliasName' => NULL,
      ),
      'supportsNativeUnionTypes' => 
      array (
        'name' => 'supportsNativeUnionTypes',
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
        'startLine' => 104,
        'endLine' => 107,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Php',
        'declaringClassName' => 'PHPStan\\Php\\PhpVersion',
        'implementingClassName' => 'PHPStan\\Php\\PhpVersion',
        'currentClassName' => 'PHPStan\\Php\\PhpVersion',
        'aliasName' => NULL,
      ),
      'deprecatesRequiredParameterAfterOptional' => 
      array (
        'name' => 'deprecatesRequiredParameterAfterOptional',
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
        'startLine' => 109,
        'endLine' => 112,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Php',
        'declaringClassName' => 'PHPStan\\Php\\PhpVersion',
        'implementingClassName' => 'PHPStan\\Php\\PhpVersion',
        'currentClassName' => 'PHPStan\\Php\\PhpVersion',
        'aliasName' => NULL,
      ),
      'deprecatesRequiredParameterAfterOptionalNullableAndDefaultNull' => 
      array (
        'name' => 'deprecatesRequiredParameterAfterOptionalNullableAndDefaultNull',
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
        'startLine' => 114,
        'endLine' => 117,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Php',
        'declaringClassName' => 'PHPStan\\Php\\PhpVersion',
        'implementingClassName' => 'PHPStan\\Php\\PhpVersion',
        'currentClassName' => 'PHPStan\\Php\\PhpVersion',
        'aliasName' => NULL,
      ),
      'deprecatesRequiredParameterAfterOptionalUnionOrMixed' => 
      array (
        'name' => 'deprecatesRequiredParameterAfterOptionalUnionOrMixed',
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
        'startLine' => 119,
        'endLine' => 122,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Php',
        'declaringClassName' => 'PHPStan\\Php\\PhpVersion',
        'implementingClassName' => 'PHPStan\\Php\\PhpVersion',
        'currentClassName' => 'PHPStan\\Php\\PhpVersion',
        'aliasName' => NULL,
      ),
      'supportsLessOverridenParametersWithVariadic' => 
      array (
        'name' => 'supportsLessOverridenParametersWithVariadic',
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
        'startLine' => 124,
        'endLine' => 127,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Php',
        'declaringClassName' => 'PHPStan\\Php\\PhpVersion',
        'implementingClassName' => 'PHPStan\\Php\\PhpVersion',
        'currentClassName' => 'PHPStan\\Php\\PhpVersion',
        'aliasName' => NULL,
      ),
      'supportsThrowExpression' => 
      array (
        'name' => 'supportsThrowExpression',
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
        'startLine' => 129,
        'endLine' => 132,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Php',
        'declaringClassName' => 'PHPStan\\Php\\PhpVersion',
        'implementingClassName' => 'PHPStan\\Php\\PhpVersion',
        'currentClassName' => 'PHPStan\\Php\\PhpVersion',
        'aliasName' => NULL,
      ),
      'supportsClassConstantOnExpression' => 
      array (
        'name' => 'supportsClassConstantOnExpression',
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
        'startLine' => 134,
        'endLine' => 137,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Php',
        'declaringClassName' => 'PHPStan\\Php\\PhpVersion',
        'implementingClassName' => 'PHPStan\\Php\\PhpVersion',
        'currentClassName' => 'PHPStan\\Php\\PhpVersion',
        'aliasName' => NULL,
      ),
      'supportsLegacyConstructor' => 
      array (
        'name' => 'supportsLegacyConstructor',
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
        'startLine' => 139,
        'endLine' => 142,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Php',
        'declaringClassName' => 'PHPStan\\Php\\PhpVersion',
        'implementingClassName' => 'PHPStan\\Php\\PhpVersion',
        'currentClassName' => 'PHPStan\\Php\\PhpVersion',
        'aliasName' => NULL,
      ),
      'supportsPromotedProperties' => 
      array (
        'name' => 'supportsPromotedProperties',
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
        'startLine' => 144,
        'endLine' => 147,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Php',
        'declaringClassName' => 'PHPStan\\Php\\PhpVersion',
        'implementingClassName' => 'PHPStan\\Php\\PhpVersion',
        'currentClassName' => 'PHPStan\\Php\\PhpVersion',
        'aliasName' => NULL,
      ),
      'supportsParameterTypeWidening' => 
      array (
        'name' => 'supportsParameterTypeWidening',
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
        'startLine' => 149,
        'endLine' => 152,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Php',
        'declaringClassName' => 'PHPStan\\Php\\PhpVersion',
        'implementingClassName' => 'PHPStan\\Php\\PhpVersion',
        'currentClassName' => 'PHPStan\\Php\\PhpVersion',
        'aliasName' => NULL,
      ),
      'supportsUnsetCast' => 
      array (
        'name' => 'supportsUnsetCast',
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
        'startLine' => 154,
        'endLine' => 157,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Php',
        'declaringClassName' => 'PHPStan\\Php\\PhpVersion',
        'implementingClassName' => 'PHPStan\\Php\\PhpVersion',
        'currentClassName' => 'PHPStan\\Php\\PhpVersion',
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
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 159,
        'endLine' => 162,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Php',
        'declaringClassName' => 'PHPStan\\Php\\PhpVersion',
        'implementingClassName' => 'PHPStan\\Php\\PhpVersion',
        'currentClassName' => 'PHPStan\\Php\\PhpVersion',
        'aliasName' => NULL,
      ),
      'throwsTypeErrorForInternalFunctions' => 
      array (
        'name' => 'throwsTypeErrorForInternalFunctions',
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
        'startLine' => 164,
        'endLine' => 167,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Php',
        'declaringClassName' => 'PHPStan\\Php\\PhpVersion',
        'implementingClassName' => 'PHPStan\\Php\\PhpVersion',
        'currentClassName' => 'PHPStan\\Php\\PhpVersion',
        'aliasName' => NULL,
      ),
      'throwsValueErrorForInternalFunctions' => 
      array (
        'name' => 'throwsValueErrorForInternalFunctions',
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
        'startLine' => 169,
        'endLine' => 172,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Php',
        'declaringClassName' => 'PHPStan\\Php\\PhpVersion',
        'implementingClassName' => 'PHPStan\\Php\\PhpVersion',
        'currentClassName' => 'PHPStan\\Php\\PhpVersion',
        'aliasName' => NULL,
      ),
      'supportsHhPrintfSpecifier' => 
      array (
        'name' => 'supportsHhPrintfSpecifier',
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
        'startLine' => 174,
        'endLine' => 177,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Php',
        'declaringClassName' => 'PHPStan\\Php\\PhpVersion',
        'implementingClassName' => 'PHPStan\\Php\\PhpVersion',
        'currentClassName' => 'PHPStan\\Php\\PhpVersion',
        'aliasName' => NULL,
      ),
      'isEmptyStringValidAliasForNoneInMbSubstituteCharacter' => 
      array (
        'name' => 'isEmptyStringValidAliasForNoneInMbSubstituteCharacter',
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
        'startLine' => 179,
        'endLine' => 182,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Php',
        'declaringClassName' => 'PHPStan\\Php\\PhpVersion',
        'implementingClassName' => 'PHPStan\\Php\\PhpVersion',
        'currentClassName' => 'PHPStan\\Php\\PhpVersion',
        'aliasName' => NULL,
      ),
      'supportsAllUnicodeScalarCodePointsInMbSubstituteCharacter' => 
      array (
        'name' => 'supportsAllUnicodeScalarCodePointsInMbSubstituteCharacter',
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
        'startLine' => 184,
        'endLine' => 187,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Php',
        'declaringClassName' => 'PHPStan\\Php\\PhpVersion',
        'implementingClassName' => 'PHPStan\\Php\\PhpVersion',
        'currentClassName' => 'PHPStan\\Php\\PhpVersion',
        'aliasName' => NULL,
      ),
      'isNumericStringValidArgInMbSubstituteCharacter' => 
      array (
        'name' => 'isNumericStringValidArgInMbSubstituteCharacter',
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
        'startLine' => 189,
        'endLine' => 192,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Php',
        'declaringClassName' => 'PHPStan\\Php\\PhpVersion',
        'implementingClassName' => 'PHPStan\\Php\\PhpVersion',
        'currentClassName' => 'PHPStan\\Php\\PhpVersion',
        'aliasName' => NULL,
      ),
      'isNullValidArgInMbSubstituteCharacter' => 
      array (
        'name' => 'isNullValidArgInMbSubstituteCharacter',
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
        'startLine' => 194,
        'endLine' => 197,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Php',
        'declaringClassName' => 'PHPStan\\Php\\PhpVersion',
        'implementingClassName' => 'PHPStan\\Php\\PhpVersion',
        'currentClassName' => 'PHPStan\\Php\\PhpVersion',
        'aliasName' => NULL,
      ),
      'isInterfaceConstantImplicitlyFinal' => 
      array (
        'name' => 'isInterfaceConstantImplicitlyFinal',
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
        'startLine' => 199,
        'endLine' => 202,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Php',
        'declaringClassName' => 'PHPStan\\Php\\PhpVersion',
        'implementingClassName' => 'PHPStan\\Php\\PhpVersion',
        'currentClassName' => 'PHPStan\\Php\\PhpVersion',
        'aliasName' => NULL,
      ),
      'supportsFinalConstants' => 
      array (
        'name' => 'supportsFinalConstants',
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
        'startLine' => 204,
        'endLine' => 207,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Php',
        'declaringClassName' => 'PHPStan\\Php\\PhpVersion',
        'implementingClassName' => 'PHPStan\\Php\\PhpVersion',
        'currentClassName' => 'PHPStan\\Php\\PhpVersion',
        'aliasName' => NULL,
      ),
      'supportsReadOnlyProperties' => 
      array (
        'name' => 'supportsReadOnlyProperties',
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
        'startLine' => 209,
        'endLine' => 212,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Php',
        'declaringClassName' => 'PHPStan\\Php\\PhpVersion',
        'implementingClassName' => 'PHPStan\\Php\\PhpVersion',
        'currentClassName' => 'PHPStan\\Php\\PhpVersion',
        'aliasName' => NULL,
      ),
      'supportsEnums' => 
      array (
        'name' => 'supportsEnums',
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
        'startLine' => 214,
        'endLine' => 217,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Php',
        'declaringClassName' => 'PHPStan\\Php\\PhpVersion',
        'implementingClassName' => 'PHPStan\\Php\\PhpVersion',
        'currentClassName' => 'PHPStan\\Php\\PhpVersion',
        'aliasName' => NULL,
      ),
      'supportsPureIntersectionTypes' => 
      array (
        'name' => 'supportsPureIntersectionTypes',
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
        'startLine' => 219,
        'endLine' => 222,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Php',
        'declaringClassName' => 'PHPStan\\Php\\PhpVersion',
        'implementingClassName' => 'PHPStan\\Php\\PhpVersion',
        'currentClassName' => 'PHPStan\\Php\\PhpVersion',
        'aliasName' => NULL,
      ),
      'supportsCaseInsensitiveConstantNames' => 
      array (
        'name' => 'supportsCaseInsensitiveConstantNames',
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
        'startLine' => 224,
        'endLine' => 227,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Php',
        'declaringClassName' => 'PHPStan\\Php\\PhpVersion',
        'implementingClassName' => 'PHPStan\\Php\\PhpVersion',
        'currentClassName' => 'PHPStan\\Php\\PhpVersion',
        'aliasName' => NULL,
      ),
      'hasStricterRoundFunctions' => 
      array (
        'name' => 'hasStricterRoundFunctions',
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
        'startLine' => 229,
        'endLine' => 232,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Php',
        'declaringClassName' => 'PHPStan\\Php\\PhpVersion',
        'implementingClassName' => 'PHPStan\\Php\\PhpVersion',
        'currentClassName' => 'PHPStan\\Php\\PhpVersion',
        'aliasName' => NULL,
      ),
      'hasTentativeReturnTypes' => 
      array (
        'name' => 'hasTentativeReturnTypes',
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
        'startLine' => 234,
        'endLine' => 237,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Php',
        'declaringClassName' => 'PHPStan\\Php\\PhpVersion',
        'implementingClassName' => 'PHPStan\\Php\\PhpVersion',
        'currentClassName' => 'PHPStan\\Php\\PhpVersion',
        'aliasName' => NULL,
      ),
      'supportsFirstClassCallables' => 
      array (
        'name' => 'supportsFirstClassCallables',
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
        'startLine' => 239,
        'endLine' => 242,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Php',
        'declaringClassName' => 'PHPStan\\Php\\PhpVersion',
        'implementingClassName' => 'PHPStan\\Php\\PhpVersion',
        'currentClassName' => 'PHPStan\\Php\\PhpVersion',
        'aliasName' => NULL,
      ),
      'supportsArrayUnpackingWithStringKeys' => 
      array (
        'name' => 'supportsArrayUnpackingWithStringKeys',
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
        'startLine' => 244,
        'endLine' => 247,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Php',
        'declaringClassName' => 'PHPStan\\Php\\PhpVersion',
        'implementingClassName' => 'PHPStan\\Php\\PhpVersion',
        'currentClassName' => 'PHPStan\\Php\\PhpVersion',
        'aliasName' => NULL,
      ),
      'throwsOnInvalidMbStringEncoding' => 
      array (
        'name' => 'throwsOnInvalidMbStringEncoding',
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
        'startLine' => 249,
        'endLine' => 252,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Php',
        'declaringClassName' => 'PHPStan\\Php\\PhpVersion',
        'implementingClassName' => 'PHPStan\\Php\\PhpVersion',
        'currentClassName' => 'PHPStan\\Php\\PhpVersion',
        'aliasName' => NULL,
      ),
      'supportsPassNoneEncodings' => 
      array (
        'name' => 'supportsPassNoneEncodings',
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
        'startLine' => 254,
        'endLine' => 257,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Php',
        'declaringClassName' => 'PHPStan\\Php\\PhpVersion',
        'implementingClassName' => 'PHPStan\\Php\\PhpVersion',
        'currentClassName' => 'PHPStan\\Php\\PhpVersion',
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
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 259,
        'endLine' => 262,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Php',
        'declaringClassName' => 'PHPStan\\Php\\PhpVersion',
        'implementingClassName' => 'PHPStan\\Php\\PhpVersion',
        'currentClassName' => 'PHPStan\\Php\\PhpVersion',
        'aliasName' => NULL,
      ),
      'deprecatesDynamicProperties' => 
      array (
        'name' => 'deprecatesDynamicProperties',
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
        'startLine' => 264,
        'endLine' => 267,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Php',
        'declaringClassName' => 'PHPStan\\Php\\PhpVersion',
        'implementingClassName' => 'PHPStan\\Php\\PhpVersion',
        'currentClassName' => 'PHPStan\\Php\\PhpVersion',
        'aliasName' => NULL,
      ),
      'strSplitReturnsEmptyArray' => 
      array (
        'name' => 'strSplitReturnsEmptyArray',
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
        'startLine' => 269,
        'endLine' => 272,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Php',
        'declaringClassName' => 'PHPStan\\Php\\PhpVersion',
        'implementingClassName' => 'PHPStan\\Php\\PhpVersion',
        'currentClassName' => 'PHPStan\\Php\\PhpVersion',
        'aliasName' => NULL,
      ),
      'supportsDisjunctiveNormalForm' => 
      array (
        'name' => 'supportsDisjunctiveNormalForm',
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
        'startLine' => 274,
        'endLine' => 277,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Php',
        'declaringClassName' => 'PHPStan\\Php\\PhpVersion',
        'implementingClassName' => 'PHPStan\\Php\\PhpVersion',
        'currentClassName' => 'PHPStan\\Php\\PhpVersion',
        'aliasName' => NULL,
      ),
      'serializableRequiresMagicMethods' => 
      array (
        'name' => 'serializableRequiresMagicMethods',
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
        'startLine' => 279,
        'endLine' => 282,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Php',
        'declaringClassName' => 'PHPStan\\Php\\PhpVersion',
        'implementingClassName' => 'PHPStan\\Php\\PhpVersion',
        'currentClassName' => 'PHPStan\\Php\\PhpVersion',
        'aliasName' => NULL,
      ),
      'arrayFunctionsReturnNullWithNonArray' => 
      array (
        'name' => 'arrayFunctionsReturnNullWithNonArray',
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
        'startLine' => 284,
        'endLine' => 287,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Php',
        'declaringClassName' => 'PHPStan\\Php\\PhpVersion',
        'implementingClassName' => 'PHPStan\\Php\\PhpVersion',
        'currentClassName' => 'PHPStan\\Php\\PhpVersion',
        'aliasName' => NULL,
      ),
      'castsNumbersToStringsOnLooseComparison' => 
      array (
        'name' => 'castsNumbersToStringsOnLooseComparison',
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
        'startLine' => 290,
        'endLine' => 293,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Php',
        'declaringClassName' => 'PHPStan\\Php\\PhpVersion',
        'implementingClassName' => 'PHPStan\\Php\\PhpVersion',
        'currentClassName' => 'PHPStan\\Php\\PhpVersion',
        'aliasName' => NULL,
      ),
      'nonNumericStringAndIntegerIsFalseOnLooseComparison' => 
      array (
        'name' => 'nonNumericStringAndIntegerIsFalseOnLooseComparison',
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
        'startLine' => 295,
        'endLine' => 298,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Php',
        'declaringClassName' => 'PHPStan\\Php\\PhpVersion',
        'implementingClassName' => 'PHPStan\\Php\\PhpVersion',
        'currentClassName' => 'PHPStan\\Php\\PhpVersion',
        'aliasName' => NULL,
      ),
      'supportsCallableInstanceMethods' => 
      array (
        'name' => 'supportsCallableInstanceMethods',
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
        'startLine' => 300,
        'endLine' => 303,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Php',
        'declaringClassName' => 'PHPStan\\Php\\PhpVersion',
        'implementingClassName' => 'PHPStan\\Php\\PhpVersion',
        'currentClassName' => 'PHPStan\\Php\\PhpVersion',
        'aliasName' => NULL,
      ),
      'supportsJsonValidate' => 
      array (
        'name' => 'supportsJsonValidate',
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
        'startLine' => 305,
        'endLine' => 308,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Php',
        'declaringClassName' => 'PHPStan\\Php\\PhpVersion',
        'implementingClassName' => 'PHPStan\\Php\\PhpVersion',
        'currentClassName' => 'PHPStan\\Php\\PhpVersion',
        'aliasName' => NULL,
      ),
      'supportsConstantsInTraits' => 
      array (
        'name' => 'supportsConstantsInTraits',
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
        'startLine' => 310,
        'endLine' => 313,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Php',
        'declaringClassName' => 'PHPStan\\Php\\PhpVersion',
        'implementingClassName' => 'PHPStan\\Php\\PhpVersion',
        'currentClassName' => 'PHPStan\\Php\\PhpVersion',
        'aliasName' => NULL,
      ),
      'supportsNativeTypesInClassConstants' => 
      array (
        'name' => 'supportsNativeTypesInClassConstants',
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
        'startLine' => 315,
        'endLine' => 318,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Php',
        'declaringClassName' => 'PHPStan\\Php\\PhpVersion',
        'implementingClassName' => 'PHPStan\\Php\\PhpVersion',
        'currentClassName' => 'PHPStan\\Php\\PhpVersion',
        'aliasName' => NULL,
      ),
      'supportsAbstractTraitMethods' => 
      array (
        'name' => 'supportsAbstractTraitMethods',
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
        'startLine' => 320,
        'endLine' => 323,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Php',
        'declaringClassName' => 'PHPStan\\Php\\PhpVersion',
        'implementingClassName' => 'PHPStan\\Php\\PhpVersion',
        'currentClassName' => 'PHPStan\\Php\\PhpVersion',
        'aliasName' => NULL,
      ),
      'supportsOverrideAttribute' => 
      array (
        'name' => 'supportsOverrideAttribute',
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
        'startLine' => 325,
        'endLine' => 328,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Php',
        'declaringClassName' => 'PHPStan\\Php\\PhpVersion',
        'implementingClassName' => 'PHPStan\\Php\\PhpVersion',
        'currentClassName' => 'PHPStan\\Php\\PhpVersion',
        'aliasName' => NULL,
      ),
      'supportsDynamicClassConstantFetch' => 
      array (
        'name' => 'supportsDynamicClassConstantFetch',
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
        'startLine' => 330,
        'endLine' => 333,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Php',
        'declaringClassName' => 'PHPStan\\Php\\PhpVersion',
        'implementingClassName' => 'PHPStan\\Php\\PhpVersion',
        'currentClassName' => 'PHPStan\\Php\\PhpVersion',
        'aliasName' => NULL,
      ),
      'supportsReadOnlyClasses' => 
      array (
        'name' => 'supportsReadOnlyClasses',
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
        'startLine' => 335,
        'endLine' => 338,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Php',
        'declaringClassName' => 'PHPStan\\Php\\PhpVersion',
        'implementingClassName' => 'PHPStan\\Php\\PhpVersion',
        'currentClassName' => 'PHPStan\\Php\\PhpVersion',
        'aliasName' => NULL,
      ),
      'supportsReadOnlyAnonymousClasses' => 
      array (
        'name' => 'supportsReadOnlyAnonymousClasses',
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
        'startLine' => 340,
        'endLine' => 343,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Php',
        'declaringClassName' => 'PHPStan\\Php\\PhpVersion',
        'implementingClassName' => 'PHPStan\\Php\\PhpVersion',
        'currentClassName' => 'PHPStan\\Php\\PhpVersion',
        'aliasName' => NULL,
      ),
      'supportsNeverReturnTypeInArrowFunction' => 
      array (
        'name' => 'supportsNeverReturnTypeInArrowFunction',
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
        'startLine' => 345,
        'endLine' => 348,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Php',
        'declaringClassName' => 'PHPStan\\Php\\PhpVersion',
        'implementingClassName' => 'PHPStan\\Php\\PhpVersion',
        'currentClassName' => 'PHPStan\\Php\\PhpVersion',
        'aliasName' => NULL,
      ),
      'supportsPregUnmatchedAsNull' => 
      array (
        'name' => 'supportsPregUnmatchedAsNull',
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
        'startLine' => 350,
        'endLine' => 355,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Php',
        'declaringClassName' => 'PHPStan\\Php\\PhpVersion',
        'implementingClassName' => 'PHPStan\\Php\\PhpVersion',
        'currentClassName' => 'PHPStan\\Php\\PhpVersion',
        'aliasName' => NULL,
      ),
      'supportsPregCaptureOnlyNamedGroups' => 
      array (
        'name' => 'supportsPregCaptureOnlyNamedGroups',
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
        'startLine' => 357,
        'endLine' => 361,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Php',
        'declaringClassName' => 'PHPStan\\Php\\PhpVersion',
        'implementingClassName' => 'PHPStan\\Php\\PhpVersion',
        'currentClassName' => 'PHPStan\\Php\\PhpVersion',
        'aliasName' => NULL,
      ),
      'supportsPropertyHooks' => 
      array (
        'name' => 'supportsPropertyHooks',
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
        'startLine' => 363,
        'endLine' => 366,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Php',
        'declaringClassName' => 'PHPStan\\Php\\PhpVersion',
        'implementingClassName' => 'PHPStan\\Php\\PhpVersion',
        'currentClassName' => 'PHPStan\\Php\\PhpVersion',
        'aliasName' => NULL,
      ),
      'supportsFinalProperties' => 
      array (
        'name' => 'supportsFinalProperties',
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
        'startLine' => 368,
        'endLine' => 371,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Php',
        'declaringClassName' => 'PHPStan\\Php\\PhpVersion',
        'implementingClassName' => 'PHPStan\\Php\\PhpVersion',
        'currentClassName' => 'PHPStan\\Php\\PhpVersion',
        'aliasName' => NULL,
      ),
      'supportsAsymmetricVisibility' => 
      array (
        'name' => 'supportsAsymmetricVisibility',
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
        'startLine' => 373,
        'endLine' => 376,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Php',
        'declaringClassName' => 'PHPStan\\Php\\PhpVersion',
        'implementingClassName' => 'PHPStan\\Php\\PhpVersion',
        'currentClassName' => 'PHPStan\\Php\\PhpVersion',
        'aliasName' => NULL,
      ),
      'supportsAsymmetricVisibilityForStaticProperties' => 
      array (
        'name' => 'supportsAsymmetricVisibilityForStaticProperties',
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
        'startLine' => 378,
        'endLine' => 381,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Php',
        'declaringClassName' => 'PHPStan\\Php\\PhpVersion',
        'implementingClassName' => 'PHPStan\\Php\\PhpVersion',
        'currentClassName' => 'PHPStan\\Php\\PhpVersion',
        'aliasName' => NULL,
      ),
      'supportsLazyObjects' => 
      array (
        'name' => 'supportsLazyObjects',
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
        'startLine' => 383,
        'endLine' => 386,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Php',
        'declaringClassName' => 'PHPStan\\Php\\PhpVersion',
        'implementingClassName' => 'PHPStan\\Php\\PhpVersion',
        'currentClassName' => 'PHPStan\\Php\\PhpVersion',
        'aliasName' => NULL,
      ),
      'hasDateTimeExceptions' => 
      array (
        'name' => 'hasDateTimeExceptions',
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
        'startLine' => 388,
        'endLine' => 391,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Php',
        'declaringClassName' => 'PHPStan\\Php\\PhpVersion',
        'implementingClassName' => 'PHPStan\\Php\\PhpVersion',
        'currentClassName' => 'PHPStan\\Php\\PhpVersion',
        'aliasName' => NULL,
      ),
      'isCurloptUrlCheckingFileSchemeWithOpenBasedir' => 
      array (
        'name' => 'isCurloptUrlCheckingFileSchemeWithOpenBasedir',
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
        'startLine' => 393,
        'endLine' => 399,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Php',
        'declaringClassName' => 'PHPStan\\Php\\PhpVersion',
        'implementingClassName' => 'PHPStan\\Php\\PhpVersion',
        'currentClassName' => 'PHPStan\\Php\\PhpVersion',
        'aliasName' => NULL,
      ),
      'supportsCurlShareHandle' => 
      array (
        'name' => 'supportsCurlShareHandle',
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
 * whether curl handles are represented as \'resource\' or CurlShareHandle
 */',
        'startLine' => 404,
        'endLine' => 407,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Php',
        'declaringClassName' => 'PHPStan\\Php\\PhpVersion',
        'implementingClassName' => 'PHPStan\\Php\\PhpVersion',
        'currentClassName' => 'PHPStan\\Php\\PhpVersion',
        'aliasName' => NULL,
      ),
      'supportsCurlSharePersistentHandle' => 
      array (
        'name' => 'supportsCurlSharePersistentHandle',
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
        'startLine' => 409,
        'endLine' => 412,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Php',
        'declaringClassName' => 'PHPStan\\Php\\PhpVersion',
        'implementingClassName' => 'PHPStan\\Php\\PhpVersion',
        'currentClassName' => 'PHPStan\\Php\\PhpVersion',
        'aliasName' => NULL,
      ),
      'highlightStringDoesNotReturnFalse' => 
      array (
        'name' => 'highlightStringDoesNotReturnFalse',
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
        'startLine' => 414,
        'endLine' => 417,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Php',
        'declaringClassName' => 'PHPStan\\Php\\PhpVersion',
        'implementingClassName' => 'PHPStan\\Php\\PhpVersion',
        'currentClassName' => 'PHPStan\\Php\\PhpVersion',
        'aliasName' => NULL,
      ),
      'deprecatesImplicitlyNullableParameterTypes' => 
      array (
        'name' => 'deprecatesImplicitlyNullableParameterTypes',
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
        'startLine' => 419,
        'endLine' => 422,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Php',
        'declaringClassName' => 'PHPStan\\Php\\PhpVersion',
        'implementingClassName' => 'PHPStan\\Php\\PhpVersion',
        'currentClassName' => 'PHPStan\\Php\\PhpVersion',
        'aliasName' => NULL,
      ),
      'substrReturnFalseInsteadOfEmptyString' => 
      array (
        'name' => 'substrReturnFalseInsteadOfEmptyString',
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
        'startLine' => 424,
        'endLine' => 427,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Php',
        'declaringClassName' => 'PHPStan\\Php\\PhpVersion',
        'implementingClassName' => 'PHPStan\\Php\\PhpVersion',
        'currentClassName' => 'PHPStan\\Php\\PhpVersion',
        'aliasName' => NULL,
      ),
      'supportsBcMathNumberOperatorOverloading' => 
      array (
        'name' => 'supportsBcMathNumberOperatorOverloading',
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
        'startLine' => 429,
        'endLine' => 432,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Php',
        'declaringClassName' => 'PHPStan\\Php\\PhpVersion',
        'implementingClassName' => 'PHPStan\\Php\\PhpVersion',
        'currentClassName' => 'PHPStan\\Php\\PhpVersion',
        'aliasName' => NULL,
      ),
      'hasPDOSubclasses' => 
      array (
        'name' => 'hasPDOSubclasses',
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
        'startLine' => 434,
        'endLine' => 437,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Php',
        'declaringClassName' => 'PHPStan\\Php\\PhpVersion',
        'implementingClassName' => 'PHPStan\\Php\\PhpVersion',
        'currentClassName' => 'PHPStan\\Php\\PhpVersion',
        'aliasName' => NULL,
      ),
      'deprecatesImplicitlyFloatConversionToInt' => 
      array (
        'name' => 'deprecatesImplicitlyFloatConversionToInt',
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
        'startLine' => 439,
        'endLine' => 442,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Php',
        'declaringClassName' => 'PHPStan\\Php\\PhpVersion',
        'implementingClassName' => 'PHPStan\\Php\\PhpVersion',
        'currentClassName' => 'PHPStan\\Php\\PhpVersion',
        'aliasName' => NULL,
      ),
      'deprecatesNullArrayOffset' => 
      array (
        'name' => 'deprecatesNullArrayOffset',
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
        'startLine' => 444,
        'endLine' => 447,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Php',
        'declaringClassName' => 'PHPStan\\Php\\PhpVersion',
        'implementingClassName' => 'PHPStan\\Php\\PhpVersion',
        'currentClassName' => 'PHPStan\\Php\\PhpVersion',
        'aliasName' => NULL,
      ),
      'supportsFinalPromotedProperties' => 
      array (
        'name' => 'supportsFinalPromotedProperties',
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
        'startLine' => 449,
        'endLine' => 452,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Php',
        'declaringClassName' => 'PHPStan\\Php\\PhpVersion',
        'implementingClassName' => 'PHPStan\\Php\\PhpVersion',
        'currentClassName' => 'PHPStan\\Php\\PhpVersion',
        'aliasName' => NULL,
      ),
      'supportsVoidCast' => 
      array (
        'name' => 'supportsVoidCast',
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
        'startLine' => 454,
        'endLine' => 457,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Php',
        'declaringClassName' => 'PHPStan\\Php\\PhpVersion',
        'implementingClassName' => 'PHPStan\\Php\\PhpVersion',
        'currentClassName' => 'PHPStan\\Php\\PhpVersion',
        'aliasName' => NULL,
      ),
      'supportsNoDiscardAttribute' => 
      array (
        'name' => 'supportsNoDiscardAttribute',
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
        'startLine' => 459,
        'endLine' => 462,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Php',
        'declaringClassName' => 'PHPStan\\Php\\PhpVersion',
        'implementingClassName' => 'PHPStan\\Php\\PhpVersion',
        'currentClassName' => 'PHPStan\\Php\\PhpVersion',
        'aliasName' => NULL,
      ),
      'deprecatesNonStandardCasts' => 
      array (
        'name' => 'deprecatesNonStandardCasts',
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
        'startLine' => 464,
        'endLine' => 467,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Php',
        'declaringClassName' => 'PHPStan\\Php\\PhpVersion',
        'implementingClassName' => 'PHPStan\\Php\\PhpVersion',
        'currentClassName' => 'PHPStan\\Php\\PhpVersion',
        'aliasName' => NULL,
      ),
      'deprecatesBacktickOperator' => 
      array (
        'name' => 'deprecatesBacktickOperator',
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
        'startLine' => 469,
        'endLine' => 472,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Php',
        'declaringClassName' => 'PHPStan\\Php\\PhpVersion',
        'implementingClassName' => 'PHPStan\\Php\\PhpVersion',
        'currentClassName' => 'PHPStan\\Php\\PhpVersion',
        'aliasName' => NULL,
      ),
      'supportsAttributesOnGlobalConstants' => 
      array (
        'name' => 'supportsAttributesOnGlobalConstants',
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
        'startLine' => 474,
        'endLine' => 477,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Php',
        'declaringClassName' => 'PHPStan\\Php\\PhpVersion',
        'implementingClassName' => 'PHPStan\\Php\\PhpVersion',
        'currentClassName' => 'PHPStan\\Php\\PhpVersion',
        'aliasName' => NULL,
      ),
      'supportsDeprecatedTraits' => 
      array (
        'name' => 'supportsDeprecatedTraits',
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
        'startLine' => 479,
        'endLine' => 482,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Php',
        'declaringClassName' => 'PHPStan\\Php\\PhpVersion',
        'implementingClassName' => 'PHPStan\\Php\\PhpVersion',
        'currentClassName' => 'PHPStan\\Php\\PhpVersion',
        'aliasName' => NULL,
      ),
      'supportsOverrideAttributeOnProperty' => 
      array (
        'name' => 'supportsOverrideAttributeOnProperty',
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
        'startLine' => 484,
        'endLine' => 487,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Php',
        'declaringClassName' => 'PHPStan\\Php\\PhpVersion',
        'implementingClassName' => 'PHPStan\\Php\\PhpVersion',
        'currentClassName' => 'PHPStan\\Php\\PhpVersion',
        'aliasName' => NULL,
      ),
      'deprecatesDecOnNonNumericString' => 
      array (
        'name' => 'deprecatesDecOnNonNumericString',
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
        'startLine' => 489,
        'endLine' => 492,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Php',
        'declaringClassName' => 'PHPStan\\Php\\PhpVersion',
        'implementingClassName' => 'PHPStan\\Php\\PhpVersion',
        'currentClassName' => 'PHPStan\\Php\\PhpVersion',
        'aliasName' => NULL,
      ),
      'deprecatesIncOnNonNumericString' => 
      array (
        'name' => 'deprecatesIncOnNonNumericString',
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
        'startLine' => 494,
        'endLine' => 497,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Php',
        'declaringClassName' => 'PHPStan\\Php\\PhpVersion',
        'implementingClassName' => 'PHPStan\\Php\\PhpVersion',
        'currentClassName' => 'PHPStan\\Php\\PhpVersion',
        'aliasName' => NULL,
      ),
      'supportsObjectsInArraySumProduct' => 
      array (
        'name' => 'supportsObjectsInArraySumProduct',
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
        'startLine' => 499,
        'endLine' => 502,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Php',
        'declaringClassName' => 'PHPStan\\Php\\PhpVersion',
        'implementingClassName' => 'PHPStan\\Php\\PhpVersion',
        'currentClassName' => 'PHPStan\\Php\\PhpVersion',
        'aliasName' => NULL,
      ),
      'hasFilterThrowOnFailureConstant' => 
      array (
        'name' => 'hasFilterThrowOnFailureConstant',
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
        'startLine' => 504,
        'endLine' => 507,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Php',
        'declaringClassName' => 'PHPStan\\Php\\PhpVersion',
        'implementingClassName' => 'PHPStan\\Php\\PhpVersion',
        'currentClassName' => 'PHPStan\\Php\\PhpVersion',
        'aliasName' => NULL,
      ),
      'throwsOnStringCast' => 
      array (
        'name' => 'throwsOnStringCast',
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
        'startLine' => 509,
        'endLine' => 512,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Php',
        'declaringClassName' => 'PHPStan\\Php\\PhpVersion',
        'implementingClassName' => 'PHPStan\\Php\\PhpVersion',
        'currentClassName' => 'PHPStan\\Php\\PhpVersion',
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