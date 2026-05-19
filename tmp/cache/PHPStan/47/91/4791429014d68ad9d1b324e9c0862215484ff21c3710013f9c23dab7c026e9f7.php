<?php declare(strict_types = 1);

// osfsl-/home/runner/work/phpstan-src/phpstan-src/vendor/composer/../ondrejmirtes/better-reflection/src/SourceLocator/Type/AbstractSourceLocator.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\BetterReflection\SourceLocator\Type\AbstractSourceLocator
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-3061ab5d1ece4c19bd3c80d479bac9436fbdda41dbc0a08da65b85a2e6fa4f38-8.4.21-6.70.0.1',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\BetterReflection\\SourceLocator\\Type\\AbstractSourceLocator',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/vendor/composer/../ondrejmirtes/better-reflection/src/SourceLocator/Type/AbstractSourceLocator.php',
      ),
    ),
    'namespace' => 'PHPStan\\BetterReflection\\SourceLocator\\Type',
    'name' => 'PHPStan\\BetterReflection\\SourceLocator\\Type\\AbstractSourceLocator',
    'shortName' => 'AbstractSourceLocator',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 64,
    'docComment' => NULL,
    'attributes' => 
    array (
    ),
    'startLine' => 16,
    'endLine' => 73,
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
      'astLocator' => 
      array (
        'declaringClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\Type\\AbstractSourceLocator',
        'implementingClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\Type\\AbstractSourceLocator',
        'name' => 'astLocator',
        'modifiers' => 4,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPStan\\BetterReflection\\SourceLocator\\Ast\\Locator',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 18,
        'endLine' => 18,
        'startColumn' => 5,
        'endColumn' => 35,
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
      'createLocatedSource' => 
      array (
        'name' => 'createLocatedSource',
        'parameters' => 
        array (
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
            'startColumn' => 53,
            'endColumn' => 74,
            'parameterIndex' => 0,
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
                  'name' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
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
        'docComment' => '/**
 * Children should implement this method and return a LocatedSource object
 * which contains the source and the file from which it was located.
 *
 * @example
 *   return new LocatedSource([\'<?php class Foo {}\', null]);
 *   return new LocatedSource([\\file_get_contents(\'Foo.php\'), \'Foo.php\']);
 */',
        'startLine' => 27,
        'endLine' => 27,
        'startColumn' => 5,
        'endColumn' => 140,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 66,
        'namespace' => 'PHPStan\\BetterReflection\\SourceLocator\\Type',
        'declaringClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\Type\\AbstractSourceLocator',
        'implementingClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\Type\\AbstractSourceLocator',
        'currentClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\Type\\AbstractSourceLocator',
        'aliasName' => NULL,
      ),
      '__construct' => 
      array (
        'name' => '__construct',
        'parameters' => 
        array (
          'astLocator' => 
          array (
            'name' => 'astLocator',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\BetterReflection\\SourceLocator\\Ast\\Locator',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 29,
            'endLine' => 29,
            'startColumn' => 33,
            'endColumn' => 54,
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
        'startLine' => 29,
        'endLine' => 32,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\BetterReflection\\SourceLocator\\Type',
        'declaringClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\Type\\AbstractSourceLocator',
        'implementingClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\Type\\AbstractSourceLocator',
        'currentClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\Type\\AbstractSourceLocator',
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
            'startLine' => 39,
            'endLine' => 39,
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
            'startLine' => 39,
            'endLine' => 39,
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
        'docComment' => '/**
 * {@inheritDoc}
 *
 * @throws ParseToAstFailure
 */',
        'startLine' => 39,
        'endLine' => 52,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\BetterReflection\\SourceLocator\\Type',
        'declaringClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\Type\\AbstractSourceLocator',
        'implementingClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\Type\\AbstractSourceLocator',
        'currentClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\Type\\AbstractSourceLocator',
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
            'startLine' => 59,
            'endLine' => 59,
            'startColumn' => 51,
            'endColumn' => 70,
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
            'startLine' => 59,
            'endLine' => 59,
            'startColumn' => 73,
            'endColumn' => 102,
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
 *
 * @throws ParseToAstFailure
 */',
        'startLine' => 59,
        'endLine' => 72,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 33,
        'namespace' => 'PHPStan\\BetterReflection\\SourceLocator\\Type',
        'declaringClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\Type\\AbstractSourceLocator',
        'implementingClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\Type\\AbstractSourceLocator',
        'currentClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\Type\\AbstractSourceLocator',
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