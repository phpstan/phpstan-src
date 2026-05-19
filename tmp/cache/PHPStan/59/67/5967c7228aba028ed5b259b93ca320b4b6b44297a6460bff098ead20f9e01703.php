<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/src/Reflection/ExtendedParametersAcceptor.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\Reflection\ExtendedParametersAcceptor
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-3824031c3309e04ceb4dbadcadbf1205249fe51bec23fd784f7f842d0856a61e',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\Reflection\\ExtendedParametersAcceptor',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/src/Reflection/ExtendedParametersAcceptor.php',
      ),
    ),
    'namespace' => 'PHPStan\\Reflection',
    'name' => 'PHPStan\\Reflection\\ExtendedParametersAcceptor',
    'shortName' => 'ExtendedParametersAcceptor',
    'isInterface' => true,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Extended function/method signature with separate PHPDoc and native types.
 *
 * Extends ParametersAcceptor with:
 * - Extended parameter reflections (separate PHPDoc/native types per parameter)
 * - Separate PHPDoc and native return types (vs the combined return type from ParametersAcceptor)
 * - Call-site variance map for template type parameters
 *
 * This is the return type of FunctionReflection::getVariants() and
 * ExtendedMethodReflection::getVariants().
 *
 * @api
 * @api-do-not-implement
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 22,
    'endLine' => 34,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
      0 => 'PHPStan\\Reflection\\ParametersAcceptor',
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
      'getParameters' => 
      array (
        'name' => 'getParameters',
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
        'docComment' => '/** @return list<ExtendedParameterReflection> */',
        'startLine' => 26,
        'endLine' => 26,
        'startColumn' => 2,
        'endColumn' => 40,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection',
        'declaringClassName' => 'PHPStan\\Reflection\\ExtendedParametersAcceptor',
        'implementingClassName' => 'PHPStan\\Reflection\\ExtendedParametersAcceptor',
        'currentClassName' => 'PHPStan\\Reflection\\ExtendedParametersAcceptor',
        'aliasName' => NULL,
      ),
      'getPhpDocReturnType' => 
      array (
        'name' => 'getPhpDocReturnType',
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
        'startLine' => 28,
        'endLine' => 28,
        'startColumn' => 2,
        'endColumn' => 45,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection',
        'declaringClassName' => 'PHPStan\\Reflection\\ExtendedParametersAcceptor',
        'implementingClassName' => 'PHPStan\\Reflection\\ExtendedParametersAcceptor',
        'currentClassName' => 'PHPStan\\Reflection\\ExtendedParametersAcceptor',
        'aliasName' => NULL,
      ),
      'getNativeReturnType' => 
      array (
        'name' => 'getNativeReturnType',
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
        'startLine' => 30,
        'endLine' => 30,
        'startColumn' => 2,
        'endColumn' => 45,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection',
        'declaringClassName' => 'PHPStan\\Reflection\\ExtendedParametersAcceptor',
        'implementingClassName' => 'PHPStan\\Reflection\\ExtendedParametersAcceptor',
        'currentClassName' => 'PHPStan\\Reflection\\ExtendedParametersAcceptor',
        'aliasName' => NULL,
      ),
      'getCallSiteVarianceMap' => 
      array (
        'name' => 'getCallSiteVarianceMap',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPStan\\Type\\Generic\\TemplateTypeVarianceMap',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 32,
        'endLine' => 32,
        'startColumn' => 2,
        'endColumn' => 67,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection',
        'declaringClassName' => 'PHPStan\\Reflection\\ExtendedParametersAcceptor',
        'implementingClassName' => 'PHPStan\\Reflection\\ExtendedParametersAcceptor',
        'currentClassName' => 'PHPStan\\Reflection\\ExtendedParametersAcceptor',
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