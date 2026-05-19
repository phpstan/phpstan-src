<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/src/Type/GeneralizePrecision.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\Type\GeneralizePrecision
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-7051f0ff502960474e9c1c18f10f2d949103e98730edac768b9442507de23b7d',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\Type\\GeneralizePrecision',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/GeneralizePrecision.php',
      ),
    ),
    'namespace' => 'PHPStan\\Type',
    'name' => 'PHPStan\\Type\\GeneralizePrecision',
    'shortName' => 'GeneralizePrecision',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Controls how aggressively Type::generalize() widens a type.
 *
 * Generalization is the process of widening a specific type to a broader one.
 * For example, generalizing ConstantStringType(\'hello\') yields StringType.
 * This is used when PHPStan needs to merge types across loop iterations or
 * branches where tracking precise constant values is impractical.
 *
 * Three levels of precision:
 * - **lessSpecific**: Aggressive generalization — constant values become their
 *   general type (e.g. \'hello\' → string, array{foo: int} → array<string, int>)
 * - **moreSpecific**: Preserves more detail — e.g. non-empty-string stays
 *   non-empty-string instead of widening to string
 * - **templateArgument**: Used when generalizing template type arguments,
 *   preserving template-specific structure
 *
 * Used as a parameter to Type::generalize():
 *
 *     $type->generalize(GeneralizePrecision::lessSpecific())
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 25,
    'endLine' => 73,
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
      'LESS_SPECIFIC' => 
      array (
        'declaringClassName' => 'PHPStan\\Type\\GeneralizePrecision',
        'implementingClassName' => 'PHPStan\\Type\\GeneralizePrecision',
        'name' => 'LESS_SPECIFIC',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '1',
          'attributes' => 
          array (
            'startLine' => 28,
            'endLine' => 28,
            'startTokenPos' => 34,
            'startFilePos' => 1058,
            'endTokenPos' => 34,
            'endFilePos' => 1058,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 28,
        'endLine' => 28,
        'startColumn' => 2,
        'endColumn' => 33,
      ),
      'MORE_SPECIFIC' => 
      array (
        'declaringClassName' => 'PHPStan\\Type\\GeneralizePrecision',
        'implementingClassName' => 'PHPStan\\Type\\GeneralizePrecision',
        'name' => 'MORE_SPECIFIC',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '2',
          'attributes' => 
          array (
            'startLine' => 29,
            'endLine' => 29,
            'startTokenPos' => 45,
            'startFilePos' => 1092,
            'endTokenPos' => 45,
            'endFilePos' => 1092,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 29,
        'endLine' => 29,
        'startColumn' => 2,
        'endColumn' => 33,
      ),
      'TEMPLATE_ARGUMENT' => 
      array (
        'declaringClassName' => 'PHPStan\\Type\\GeneralizePrecision',
        'implementingClassName' => 'PHPStan\\Type\\GeneralizePrecision',
        'name' => 'TEMPLATE_ARGUMENT',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '3',
          'attributes' => 
          array (
            'startLine' => 30,
            'endLine' => 30,
            'startTokenPos' => 56,
            'startFilePos' => 1130,
            'endTokenPos' => 56,
            'endFilePos' => 1130,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 30,
        'endLine' => 30,
        'startColumn' => 2,
        'endColumn' => 37,
      ),
    ),
    'immediateProperties' => 
    array (
      'registry' => 
      array (
        'declaringClassName' => 'PHPStan\\Type\\GeneralizePrecision',
        'implementingClassName' => 'PHPStan\\Type\\GeneralizePrecision',
        'name' => 'registry',
        'modifiers' => 20,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'default' => NULL,
        'docComment' => '/** @var self[] */',
        'attributes' => 
        array (
        ),
        'startLine' => 33,
        'endLine' => 33,
        'startColumn' => 2,
        'endColumn' => 32,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'value' => 
      array (
        'declaringClassName' => 'PHPStan\\Type\\GeneralizePrecision',
        'implementingClassName' => 'PHPStan\\Type\\GeneralizePrecision',
        'name' => 'value',
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
        'startLine' => 35,
        'endLine' => 35,
        'startColumn' => 31,
        'endColumn' => 48,
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
          'value' => 
          array (
            'name' => 'value',
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
            'startLine' => 35,
            'endLine' => 35,
            'startColumn' => 31,
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
        'docComment' => NULL,
        'startLine' => 35,
        'endLine' => 37,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'PHPStan\\Type',
        'declaringClassName' => 'PHPStan\\Type\\GeneralizePrecision',
        'implementingClassName' => 'PHPStan\\Type\\GeneralizePrecision',
        'currentClassName' => 'PHPStan\\Type\\GeneralizePrecision',
        'aliasName' => NULL,
      ),
      'create' => 
      array (
        'name' => 'create',
        'parameters' => 
        array (
          'value' => 
          array (
            'name' => 'value',
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
            'startLine' => 39,
            'endLine' => 39,
            'startColumn' => 33,
            'endColumn' => 42,
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
        'docComment' => NULL,
        'startLine' => 39,
        'endLine' => 43,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 20,
        'namespace' => 'PHPStan\\Type',
        'declaringClassName' => 'PHPStan\\Type\\GeneralizePrecision',
        'implementingClassName' => 'PHPStan\\Type\\GeneralizePrecision',
        'currentClassName' => 'PHPStan\\Type\\GeneralizePrecision',
        'aliasName' => NULL,
      ),
      'lessSpecific' => 
      array (
        'name' => 'lessSpecific',
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
        'docComment' => '/** @api */',
        'startLine' => 46,
        'endLine' => 49,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'PHPStan\\Type',
        'declaringClassName' => 'PHPStan\\Type\\GeneralizePrecision',
        'implementingClassName' => 'PHPStan\\Type\\GeneralizePrecision',
        'currentClassName' => 'PHPStan\\Type\\GeneralizePrecision',
        'aliasName' => NULL,
      ),
      'moreSpecific' => 
      array (
        'name' => 'moreSpecific',
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
        'docComment' => '/** @api */',
        'startLine' => 52,
        'endLine' => 55,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'PHPStan\\Type',
        'declaringClassName' => 'PHPStan\\Type\\GeneralizePrecision',
        'implementingClassName' => 'PHPStan\\Type\\GeneralizePrecision',
        'currentClassName' => 'PHPStan\\Type\\GeneralizePrecision',
        'aliasName' => NULL,
      ),
      'templateArgument' => 
      array (
        'name' => 'templateArgument',
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
        'docComment' => '/** @api */',
        'startLine' => 58,
        'endLine' => 61,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'PHPStan\\Type',
        'declaringClassName' => 'PHPStan\\Type\\GeneralizePrecision',
        'implementingClassName' => 'PHPStan\\Type\\GeneralizePrecision',
        'currentClassName' => 'PHPStan\\Type\\GeneralizePrecision',
        'aliasName' => NULL,
      ),
      'isMoreSpecific' => 
      array (
        'name' => 'isMoreSpecific',
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
        'startLine' => 63,
        'endLine' => 66,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type',
        'declaringClassName' => 'PHPStan\\Type\\GeneralizePrecision',
        'implementingClassName' => 'PHPStan\\Type\\GeneralizePrecision',
        'currentClassName' => 'PHPStan\\Type\\GeneralizePrecision',
        'aliasName' => NULL,
      ),
      'isTemplateArgument' => 
      array (
        'name' => 'isTemplateArgument',
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
        'startLine' => 68,
        'endLine' => 71,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type',
        'declaringClassName' => 'PHPStan\\Type\\GeneralizePrecision',
        'implementingClassName' => 'PHPStan\\Type\\GeneralizePrecision',
        'currentClassName' => 'PHPStan\\Type\\GeneralizePrecision',
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