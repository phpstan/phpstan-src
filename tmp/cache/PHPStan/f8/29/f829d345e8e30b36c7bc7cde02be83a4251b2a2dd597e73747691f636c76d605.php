<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/tests/PHPStan/Analyser/TypeSpecifyingExtensionTypeInferenceTrueTest.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\Analyser\TypeSpecifyingExtensionTypeInferenceTrueTest
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-1936db2341711285b4428eb46af7e87540425ea8fc4f99a48ae50565d2a85dea',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\Analyser\\TypeSpecifyingExtensionTypeInferenceTrueTest',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/tests/PHPStan/Analyser/TypeSpecifyingExtensionTypeInferenceTrueTest.php',
      ),
    ),
    'namespace' => 'PHPStan\\Analyser',
    'name' => 'PHPStan\\Analyser\\TypeSpecifyingExtensionTypeInferenceTrueTest',
    'shortName' => 'TypeSpecifyingExtensionTypeInferenceTrueTest',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => NULL,
    'attributes' => 
    array (
    ),
    'startLine' => 8,
    'endLine' => 38,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'PHPStan\\Testing\\TypeInferenceTestCase',
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
      'dataTypeSpecifyingExtensionsTrue' => 
      array (
        'name' => 'dataTypeSpecifyingExtensionsTrue',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'iterable',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 11,
        'endLine' => 16,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => true,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'PHPStan\\Analyser',
        'declaringClassName' => 'PHPStan\\Analyser\\TypeSpecifyingExtensionTypeInferenceTrueTest',
        'implementingClassName' => 'PHPStan\\Analyser\\TypeSpecifyingExtensionTypeInferenceTrueTest',
        'currentClassName' => 'PHPStan\\Analyser\\TypeSpecifyingExtensionTypeInferenceTrueTest',
        'aliasName' => NULL,
      ),
      'testTypeSpecifyingExtensionsTrue' => 
      array (
        'name' => 'testTypeSpecifyingExtensionsTrue',
        'parameters' => 
        array (
          'assertType' => 
          array (
            'name' => 'assertType',
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
            'startLine' => 23,
            'endLine' => 23,
            'startColumn' => 3,
            'endColumn' => 20,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'file' => 
          array (
            'name' => 'file',
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
            'startLine' => 24,
            'endLine' => 24,
            'startColumn' => 3,
            'endColumn' => 14,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'args' => 
          array (
            'name' => 'args',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => true,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 25,
            'endLine' => 25,
            'startColumn' => 3,
            'endColumn' => 10,
            'parameterIndex' => 2,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
          0 => 
          array (
            'name' => 'PHPUnit\\Framework\\Attributes\\DataProvider',
            'isRepeated' => false,
            'arguments' => 
            array (
              0 => 
              array (
                'code' => '\'dataTypeSpecifyingExtensionsTrue\'',
                'attributes' => 
                array (
                  'startLine' => 21,
                  'endLine' => 21,
                  'startTokenPos' => 100,
                  'startFilePos' => 651,
                  'endTokenPos' => 100,
                  'endFilePos' => 684,
                ),
              ),
            ),
          ),
        ),
        'docComment' => '/**
 * @param mixed ...$args
 */',
        'startLine' => 21,
        'endLine' => 29,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => true,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Analyser',
        'declaringClassName' => 'PHPStan\\Analyser\\TypeSpecifyingExtensionTypeInferenceTrueTest',
        'implementingClassName' => 'PHPStan\\Analyser\\TypeSpecifyingExtensionTypeInferenceTrueTest',
        'currentClassName' => 'PHPStan\\Analyser\\TypeSpecifyingExtensionTypeInferenceTrueTest',
        'aliasName' => NULL,
      ),
      'getAdditionalConfigFiles' => 
      array (
        'name' => 'getAdditionalConfigFiles',
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
        'docComment' => NULL,
        'startLine' => 31,
        'endLine' => 36,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'PHPStan\\Analyser',
        'declaringClassName' => 'PHPStan\\Analyser\\TypeSpecifyingExtensionTypeInferenceTrueTest',
        'implementingClassName' => 'PHPStan\\Analyser\\TypeSpecifyingExtensionTypeInferenceTrueTest',
        'currentClassName' => 'PHPStan\\Analyser\\TypeSpecifyingExtensionTypeInferenceTrueTest',
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