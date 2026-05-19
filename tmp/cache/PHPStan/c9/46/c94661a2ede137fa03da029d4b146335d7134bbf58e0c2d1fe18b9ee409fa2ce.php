<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/vendor/nette/di/src/DI/Definitions/Statement.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Nette\DI\Definitions\Statement
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-cc5ec82d07e915e180b6823cd403712ed0d0db22bf2182d8f180185b4966afdd',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Nette\\DI\\Definitions\\Statement',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/vendor/nette/di/src/DI/Definitions/Statement.php',
      ),
    ),
    'namespace' => 'Nette\\DI\\Definitions',
    'name' => 'Nette\\DI\\Definitions\\Statement',
    'shortName' => 'Statement',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Assignment or calling statement.
 *
 * @property string|array|Definition|Reference|null $entity
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 21,
    'endLine' => 73,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
      0 => 'Nette\\Schema\\DynamicParameter',
    ),
    'traitClassNames' => 
    array (
      0 => 'Nette\\SmartObject',
    ),
    'immediateConstants' => 
    array (
    ),
    'immediateProperties' => 
    array (
      'arguments' => 
      array (
        'declaringClassName' => 'Nette\\DI\\Definitions\\Statement',
        'implementingClassName' => 'Nette\\DI\\Definitions\\Statement',
        'name' => 'arguments',
        'modifiers' => 1,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/** @var array */',
        'attributes' => 
        array (
        ),
        'startLine' => 26,
        'endLine' => 26,
        'startColumn' => 2,
        'endColumn' => 19,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'entity' => 
      array (
        'declaringClassName' => 'Nette\\DI\\Definitions\\Statement',
        'implementingClassName' => 'Nette\\DI\\Definitions\\Statement',
        'name' => 'entity',
        'modifiers' => 4,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/** @var string|array|Definition|Reference|null */',
        'attributes' => 
        array (
        ),
        'startLine' => 29,
        'endLine' => 29,
        'startColumn' => 2,
        'endColumn' => 17,
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
          'entity' => 
          array (
            'name' => 'entity',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 35,
            'endLine' => 35,
            'startColumn' => 30,
            'endColumn' => 36,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'arguments' => 
          array (
            'name' => 'arguments',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 35,
                'endLine' => 35,
                'startTokenPos' => 77,
                'startFilePos' => 672,
                'endTokenPos' => 78,
                'endFilePos' => 673,
              ),
            ),
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
            'startLine' => 35,
            'endLine' => 35,
            'startColumn' => 39,
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
 * @param  string|array|Definition|Reference|null  $entity
 */',
        'startLine' => 35,
        'endLine' => 65,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Nette\\DI\\Definitions',
        'declaringClassName' => 'Nette\\DI\\Definitions\\Statement',
        'implementingClassName' => 'Nette\\DI\\Definitions\\Statement',
        'currentClassName' => 'Nette\\DI\\Definitions\\Statement',
        'aliasName' => NULL,
      ),
      'getEntity' => 
      array (
        'name' => 'getEntity',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/** @return string|array|Definition|Reference|null */',
        'startLine' => 69,
        'endLine' => 72,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Nette\\DI\\Definitions',
        'declaringClassName' => 'Nette\\DI\\Definitions\\Statement',
        'implementingClassName' => 'Nette\\DI\\Definitions\\Statement',
        'currentClassName' => 'Nette\\DI\\Definitions\\Statement',
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