<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/src/Type/ConstantScalarType.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\Type\ConstantScalarType
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-55b3d9624a92e1eb7b55224b8180fc4aecb0256044ca7402ef17c70239902131',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\Type\\ConstantScalarType',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/ConstantScalarType.php',
      ),
    ),
    'namespace' => 'PHPStan\\Type',
    'name' => 'PHPStan\\Type\\ConstantScalarType',
    'shortName' => 'ConstantScalarType',
    'isInterface' => true,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * A type whose value is known at analysis time — a compile-time constant scalar.
 *
 * Implemented by ConstantIntegerType, ConstantFloatType, ConstantStringType,
 * ConstantBooleanType, and NullType.
 *
 * Use Type::isConstantValue() to check if a type is constant without instanceof,
 * and Type::getConstantScalarTypes() to extract constant types from unions.
 *
 * @api
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 16,
    'endLine' => 22,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
      0 => 'PHPStan\\Type\\Type',
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
      'getValue' => 
      array (
        'name' => 'getValue',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/** @return int|float|string|bool|null */',
        'startLine' => 20,
        'endLine' => 20,
        'startColumn' => 2,
        'endColumn' => 28,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type',
        'declaringClassName' => 'PHPStan\\Type\\ConstantScalarType',
        'implementingClassName' => 'PHPStan\\Type\\ConstantScalarType',
        'currentClassName' => 'PHPStan\\Type\\ConstantScalarType',
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