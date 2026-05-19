<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/src/Analyser/TypeSpecifierFactory.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\Analyser\TypeSpecifierFactory
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-6781dbb01f4270ec84ea0936381607125feaeb52b7466f99e785fe5b6f4e1b45',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\Analyser\\TypeSpecifierFactory',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/src/Analyser/TypeSpecifierFactory.php',
      ),
    ),
    'namespace' => 'PHPStan\\Analyser',
    'name' => 'PHPStan\\Analyser\\TypeSpecifierFactory',
    'shortName' => 'TypeSpecifierFactory',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => NULL,
    'attributes' => 
    array (
      0 => 
      array (
        'name' => 'PHPStan\\DependencyInjection\\AutowiredService',
        'isRepeated' => false,
        'arguments' => 
        array (
          'name' => 
          array (
            'code' => '\'typeSpecifierFactory\'',
            'attributes' => 
            array (
              'startLine' => 13,
              'endLine' => 13,
              'startTokenPos' => 59,
              'startFilePos' => 351,
              'endTokenPos' => 59,
              'endFilePos' => 372,
            ),
          ),
        ),
      ),
    ),
    'startLine' => 13,
    'endLine' => 61,
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
      'FUNCTION_TYPE_SPECIFYING_EXTENSION_TAG' => 
      array (
        'declaringClassName' => 'PHPStan\\Analyser\\TypeSpecifierFactory',
        'implementingClassName' => 'PHPStan\\Analyser\\TypeSpecifierFactory',
        'name' => 'FUNCTION_TYPE_SPECIFYING_EXTENSION_TAG',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'phpstan.typeSpecifier.functionTypeSpecifyingExtension\'',
          'attributes' => 
          array (
            'startLine' => 17,
            'endLine' => 17,
            'startTokenPos' => 79,
            'startFilePos' => 467,
            'endTokenPos' => 79,
            'endFilePos' => 521,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 17,
        'endLine' => 17,
        'startColumn' => 2,
        'endColumn' => 111,
      ),
      'METHOD_TYPE_SPECIFYING_EXTENSION_TAG' => 
      array (
        'declaringClassName' => 'PHPStan\\Analyser\\TypeSpecifierFactory',
        'implementingClassName' => 'PHPStan\\Analyser\\TypeSpecifierFactory',
        'name' => 'METHOD_TYPE_SPECIFYING_EXTENSION_TAG',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'phpstan.typeSpecifier.methodTypeSpecifyingExtension\'',
          'attributes' => 
          array (
            'startLine' => 18,
            'endLine' => 18,
            'startTokenPos' => 90,
            'startFilePos' => 577,
            'endTokenPos' => 90,
            'endFilePos' => 629,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 18,
        'endLine' => 18,
        'startColumn' => 2,
        'endColumn' => 107,
      ),
      'STATIC_METHOD_TYPE_SPECIFYING_EXTENSION_TAG' => 
      array (
        'declaringClassName' => 'PHPStan\\Analyser\\TypeSpecifierFactory',
        'implementingClassName' => 'PHPStan\\Analyser\\TypeSpecifierFactory',
        'name' => 'STATIC_METHOD_TYPE_SPECIFYING_EXTENSION_TAG',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'phpstan.typeSpecifier.staticMethodTypeSpecifyingExtension\'',
          'attributes' => 
          array (
            'startLine' => 19,
            'endLine' => 19,
            'startTokenPos' => 101,
            'startFilePos' => 692,
            'endTokenPos' => 101,
            'endFilePos' => 750,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 19,
        'endLine' => 19,
        'startColumn' => 2,
        'endColumn' => 120,
      ),
    ),
    'immediateProperties' => 
    array (
      'container' => 
      array (
        'declaringClassName' => 'PHPStan\\Analyser\\TypeSpecifierFactory',
        'implementingClassName' => 'PHPStan\\Analyser\\TypeSpecifierFactory',
        'name' => 'container',
        'modifiers' => 4,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPStan\\DependencyInjection\\Container',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 21,
        'endLine' => 21,
        'startColumn' => 30,
        'endColumn' => 57,
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
          'container' => 
          array (
            'name' => 'container',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\DependencyInjection\\Container',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 21,
            'endLine' => 21,
            'startColumn' => 30,
            'endColumn' => 57,
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
        'startLine' => 21,
        'endLine' => 23,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Analyser',
        'declaringClassName' => 'PHPStan\\Analyser\\TypeSpecifierFactory',
        'implementingClassName' => 'PHPStan\\Analyser\\TypeSpecifierFactory',
        'currentClassName' => 'PHPStan\\Analyser\\TypeSpecifierFactory',
        'aliasName' => NULL,
      ),
      'create' => 
      array (
        'name' => 'create',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPStan\\Analyser\\TypeSpecifier',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 25,
        'endLine' => 59,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Analyser',
        'declaringClassName' => 'PHPStan\\Analyser\\TypeSpecifierFactory',
        'implementingClassName' => 'PHPStan\\Analyser\\TypeSpecifierFactory',
        'currentClassName' => 'PHPStan\\Analyser\\TypeSpecifierFactory',
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