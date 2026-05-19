<?php declare(strict_types = 1);

// osfsl-/home/runner/work/phpstan-src/phpstan-src/vendor/composer/../ondrejmirtes/better-reflection/src/SourceLocator/Type/AggregateSourceLocator.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\BetterReflection\SourceLocator\Type\AggregateSourceLocator
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-eaed0e4e4c0dbd56491c6c8fb9c1ff3528f47870196a7d3dddc0e3f33634899c-8.4.21-6.70.0.1',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\BetterReflection\\SourceLocator\\Type\\AggregateSourceLocator',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/vendor/composer/../ondrejmirtes/better-reflection/src/SourceLocator/Type/AggregateSourceLocator.php',
      ),
    ),
    'namespace' => 'PHPStan\\BetterReflection\\SourceLocator\\Type',
    'name' => 'PHPStan\\BetterReflection\\SourceLocator\\Type\\AggregateSourceLocator',
    'shortName' => 'AggregateSourceLocator',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => NULL,
    'attributes' => 
    array (
    ),
    'startLine' => 15,
    'endLine' => 50,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
      0 => 'PHPStan\\BetterReflection\\SourceLocator\\Type\\SourceLocator',
    ),
    'traitClassNames' => 
    array (
    ),
    'immediateConstants' => 
    array (
    ),
    'immediateProperties' => 
    array (
      'sourceLocators' => 
      array (
        'declaringClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\Type\\AggregateSourceLocator',
        'implementingClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\Type\\AggregateSourceLocator',
        'name' => 'sourceLocators',
        'modifiers' => 4,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'default' => 
        array (
          'code' => '[]',
          'attributes' => 
          array (
            'startLine' => 20,
            'endLine' => 20,
            'startTokenPos' => 69,
            'startFilePos' => 491,
            'endTokenPos' => 70,
            'endFilePos' => 492,
          ),
        ),
        'docComment' => '/**
 * @var list<SourceLocator>
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 20,
        'endLine' => 20,
        'startColumn' => 5,
        'endColumn' => 39,
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
          'sourceLocators' => 
          array (
            'name' => 'sourceLocators',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 22,
                'endLine' => 22,
                'startTokenPos' => 87,
                'startFilePos' => 605,
                'endTokenPos' => 88,
                'endFilePos' => 606,
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
            'startLine' => 22,
            'endLine' => 22,
            'startColumn' => 33,
            'endColumn' => 58,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/** @param list<SourceLocator> $sourceLocators */',
        'startLine' => 22,
        'endLine' => 25,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\BetterReflection\\SourceLocator\\Type',
        'declaringClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\Type\\AggregateSourceLocator',
        'implementingClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\Type\\AggregateSourceLocator',
        'currentClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\Type\\AggregateSourceLocator',
        'aliasName' => NULL,
      ),
      'locateIdentifier' => 
      array (
        'name' => 'locateIdentifier',
        'parameters' => 
        array (
          'reflector' => 
          array (
            'name' => 'reflector',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\BetterReflection\\Reflector\\Reflector',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 27,
            'endLine' => 27,
            'startColumn' => 38,
            'endColumn' => 57,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'identifier' => 
          array (
            'name' => 'identifier',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\BetterReflection\\Identifier\\Identifier',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 27,
            'endLine' => 27,
            'startColumn' => 60,
            'endColumn' => 81,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionUnionType',
          'data' => 
          array (
            'types' => 
            array (
              0 => 
              array (
                'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                'data' => 
                array (
                  'name' => 'PHPStan\\BetterReflection\\Reflection\\Reflection',
                  'isIdentifier' => false,
                ),
              ),
              1 => 
              array (
                'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                'data' => 
                array (
                  'name' => 'null',
                  'isIdentifier' => true,
                ),
              ),
            ),
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 27,
        'endLine' => 38,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\BetterReflection\\SourceLocator\\Type',
        'declaringClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\Type\\AggregateSourceLocator',
        'implementingClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\Type\\AggregateSourceLocator',
        'currentClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\Type\\AggregateSourceLocator',
        'aliasName' => NULL,
      ),
      'locateIdentifiersByType' => 
      array (
        'name' => 'locateIdentifiersByType',
        'parameters' => 
        array (
          'reflector' => 
          array (
            'name' => 'reflector',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\BetterReflection\\Reflector\\Reflector',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 43,
            'endLine' => 43,
            'startColumn' => 45,
            'endColumn' => 64,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'identifierType' => 
          array (
            'name' => 'identifierType',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\BetterReflection\\Identifier\\IdentifierType',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 43,
            'endLine' => 43,
            'startColumn' => 67,
            'endColumn' => 96,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * {@inheritDoc}
 */',
        'startLine' => 43,
        'endLine' => 49,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\BetterReflection\\SourceLocator\\Type',
        'declaringClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\Type\\AggregateSourceLocator',
        'implementingClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\Type\\AggregateSourceLocator',
        'currentClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\Type\\AggregateSourceLocator',
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