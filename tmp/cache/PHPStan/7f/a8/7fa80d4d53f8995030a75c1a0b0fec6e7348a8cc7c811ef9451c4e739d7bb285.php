<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/src/Type/Generic/TemplateTypeReference.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\Type\Generic\TemplateTypeReference
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-2507a72a423c882ed0ea41d302f65e083f8c3b7e47685405188d504dc0e55000',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\Type\\Generic\\TemplateTypeReference',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Generic/TemplateTypeReference.php',
      ),
    ),
    'namespace' => 'PHPStan\\Type\\Generic',
    'name' => 'PHPStan\\Type\\Generic\\TemplateTypeReference',
    'shortName' => 'TemplateTypeReference',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * A reference to a template type together with its variance at the point of usage.
 *
 * When a type contains template type parameters (e.g. `array<T>` or `Comparable<T>`),
 * this class pairs the TemplateType with its positional variance — whether T appears
 * in a covariant position (return type), contravariant position (parameter type),
 * invariant position, or bivariant position.
 *
 * Used by Type::getReferencedTemplateTypes() to report all template types within
 * a type along with their variance context. This information is used for:
 * - Template type inference (knowing the variance affects how types are inferred)
 * - Variance validation (checking that @template-covariant types only appear in
 *   covariant positions)
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 19,
    'endLine' => 36,
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
      'type' => 
      array (
        'declaringClassName' => 'PHPStan\\Type\\Generic\\TemplateTypeReference',
        'implementingClassName' => 'PHPStan\\Type\\Generic\\TemplateTypeReference',
        'name' => 'type',
        'modifiers' => 4,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPStan\\Type\\Generic\\TemplateType',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 22,
        'endLine' => 22,
        'startColumn' => 30,
        'endColumn' => 55,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'positionVariance' => 
      array (
        'declaringClassName' => 'PHPStan\\Type\\Generic\\TemplateTypeReference',
        'implementingClassName' => 'PHPStan\\Type\\Generic\\TemplateTypeReference',
        'name' => 'positionVariance',
        'modifiers' => 4,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPStan\\Type\\Generic\\TemplateTypeVariance',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 22,
        'endLine' => 22,
        'startColumn' => 58,
        'endColumn' => 103,
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
          'type' => 
          array (
            'name' => 'type',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\Type\\Generic\\TemplateType',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 22,
            'endLine' => 22,
            'startColumn' => 30,
            'endColumn' => 55,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'positionVariance' => 
          array (
            'name' => 'positionVariance',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\Type\\Generic\\TemplateTypeVariance',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 22,
            'endLine' => 22,
            'startColumn' => 58,
            'endColumn' => 103,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 22,
        'endLine' => 24,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type\\Generic',
        'declaringClassName' => 'PHPStan\\Type\\Generic\\TemplateTypeReference',
        'implementingClassName' => 'PHPStan\\Type\\Generic\\TemplateTypeReference',
        'currentClassName' => 'PHPStan\\Type\\Generic\\TemplateTypeReference',
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
            'name' => 'PHPStan\\Type\\Generic\\TemplateType',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 26,
        'endLine' => 29,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type\\Generic',
        'declaringClassName' => 'PHPStan\\Type\\Generic\\TemplateTypeReference',
        'implementingClassName' => 'PHPStan\\Type\\Generic\\TemplateTypeReference',
        'currentClassName' => 'PHPStan\\Type\\Generic\\TemplateTypeReference',
        'aliasName' => NULL,
      ),
      'getPositionVariance' => 
      array (
        'name' => 'getPositionVariance',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPStan\\Type\\Generic\\TemplateTypeVariance',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 31,
        'endLine' => 34,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type\\Generic',
        'declaringClassName' => 'PHPStan\\Type\\Generic\\TemplateTypeReference',
        'implementingClassName' => 'PHPStan\\Type\\Generic\\TemplateTypeReference',
        'currentClassName' => 'PHPStan\\Type\\Generic\\TemplateTypeReference',
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