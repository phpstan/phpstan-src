<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/vendor/nette/di/src/DI/Definitions/Reference.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Nette\DI\Definitions\Reference
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-c92b593511febb61c5179cf195b669f062d851f3a0c64bbb9f870288c9751d46',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Nette\\DI\\Definitions\\Reference',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/vendor/nette/di/src/DI/Definitions/Reference.php',
      ),
    ),
    'namespace' => 'Nette\\DI\\Definitions',
    'name' => 'Nette\\DI\\Definitions\\Reference',
    'shortName' => 'Reference',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Reference to service. Either by name or by type or reference to the \'self\' service.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 18,
    'endLine' => 69,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
    ),
    'traitClassNames' => 
    array (
      0 => 'Nette\\SmartObject',
    ),
    'immediateConstants' => 
    array (
      'Self' => 
      array (
        'declaringClassName' => 'Nette\\DI\\Definitions\\Reference',
        'implementingClassName' => 'Nette\\DI\\Definitions\\Reference',
        'name' => 'Self',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'self\'',
          'attributes' => 
          array (
            'startLine' => 22,
            'endLine' => 22,
            'startTokenPos' => 45,
            'startFilePos' => 376,
            'endTokenPos' => 45,
            'endFilePos' => 381,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 22,
        'endLine' => 22,
        'startColumn' => 2,
        'endColumn' => 28,
      ),
      'SELF' => 
      array (
        'declaringClassName' => 'Nette\\DI\\Definitions\\Reference',
        'implementingClassName' => 'Nette\\DI\\Definitions\\Reference',
        'name' => 'SELF',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => 'self::Self',
          'attributes' => 
          array (
            'startLine' => 25,
            'endLine' => 25,
            'startTokenPos' => 58,
            'startFilePos' => 446,
            'endTokenPos' => 60,
            'endFilePos' => 455,
          ),
        ),
        'docComment' => '/** @deprecated use Reference::Self */',
        'attributes' => 
        array (
        ),
        'startLine' => 25,
        'endLine' => 25,
        'startColumn' => 2,
        'endColumn' => 32,
      ),
    ),
    'immediateProperties' => 
    array (
      'value' => 
      array (
        'declaringClassName' => 'Nette\\DI\\Definitions\\Reference',
        'implementingClassName' => 'Nette\\DI\\Definitions\\Reference',
        'name' => 'value',
        'modifiers' => 4,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/** @var string */',
        'attributes' => 
        array (
        ),
        'startLine' => 28,
        'endLine' => 28,
        'startColumn' => 2,
        'endColumn' => 16,
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
      'fromType' => 
      array (
        'name' => 'fromType',
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
            'startLine' => 31,
            'endLine' => 31,
            'startColumn' => 34,
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
            'name' => 'self',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 31,
        'endLine' => 38,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Nette\\DI\\Definitions',
        'declaringClassName' => 'Nette\\DI\\Definitions\\Reference',
        'implementingClassName' => 'Nette\\DI\\Definitions\\Reference',
        'currentClassName' => 'Nette\\DI\\Definitions\\Reference',
        'aliasName' => NULL,
      ),
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
            'startLine' => 41,
            'endLine' => 41,
            'startColumn' => 30,
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
        'docComment' => NULL,
        'startLine' => 41,
        'endLine' => 44,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Nette\\DI\\Definitions',
        'declaringClassName' => 'Nette\\DI\\Definitions\\Reference',
        'implementingClassName' => 'Nette\\DI\\Definitions\\Reference',
        'currentClassName' => 'Nette\\DI\\Definitions\\Reference',
        'aliasName' => NULL,
      ),
      'getValue' => 
      array (
        'name' => 'getValue',
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
        'startLine' => 47,
        'endLine' => 50,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Nette\\DI\\Definitions',
        'declaringClassName' => 'Nette\\DI\\Definitions\\Reference',
        'implementingClassName' => 'Nette\\DI\\Definitions\\Reference',
        'currentClassName' => 'Nette\\DI\\Definitions\\Reference',
        'aliasName' => NULL,
      ),
      'isName' => 
      array (
        'name' => 'isName',
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
        'startLine' => 53,
        'endLine' => 56,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Nette\\DI\\Definitions',
        'declaringClassName' => 'Nette\\DI\\Definitions\\Reference',
        'implementingClassName' => 'Nette\\DI\\Definitions\\Reference',
        'currentClassName' => 'Nette\\DI\\Definitions\\Reference',
        'aliasName' => NULL,
      ),
      'isType' => 
      array (
        'name' => 'isType',
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
        'startLine' => 59,
        'endLine' => 62,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Nette\\DI\\Definitions',
        'declaringClassName' => 'Nette\\DI\\Definitions\\Reference',
        'implementingClassName' => 'Nette\\DI\\Definitions\\Reference',
        'currentClassName' => 'Nette\\DI\\Definitions\\Reference',
        'aliasName' => NULL,
      ),
      'isSelf' => 
      array (
        'name' => 'isSelf',
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
        'startLine' => 65,
        'endLine' => 68,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Nette\\DI\\Definitions',
        'declaringClassName' => 'Nette\\DI\\Definitions\\Reference',
        'implementingClassName' => 'Nette\\DI\\Definitions\\Reference',
        'currentClassName' => 'Nette\\DI\\Definitions\\Reference',
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