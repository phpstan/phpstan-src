<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/src/Reflection/BetterReflection/SourceLocator/AutoloadSourceLocator.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\Reflection\BetterReflection\SourceLocator\AutoloadSourceLocator
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-6b2318d826dd31ebe8f33e3a28c25d31c8dea39e838204c479b439e193dc5f82',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator\\AutoloadSourceLocator',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/src/Reflection/BetterReflection/SourceLocator/AutoloadSourceLocator.php',
      ),
    ),
    'namespace' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator',
    'name' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator\\AutoloadSourceLocator',
    'shortName' => 'AutoloadSourceLocator',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Use PHP\'s built in autoloader to locate a class, without actually loading.
 *
 * There are some prerequisites...
 *   - we expect the autoloader to load classes from a file (i.e. using require/include)
 *
 * Modified code from Roave/BetterReflection, Copyright (c) 2017 Roave, LLC.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 51,
    'endLine' => 385,
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
      'presentSymbols' => 
      array (
        'declaringClassName' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator\\AutoloadSourceLocator',
        'implementingClassName' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator\\AutoloadSourceLocator',
        'name' => 'presentSymbols',
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
          'code' => '[\'classes\' => [], \'functions\' => [], \'constants\' => []]',
          'attributes' => 
          array (
            'startLine' => 55,
            'endLine' => 59,
            'startTokenPos' => 259,
            'startFilePos' => 1907,
            'endTokenPos' => 285,
            'endFilePos' => 1971,
          ),
        ),
        'docComment' => '/** @var array{classes: array<string, string>, functions: array<string, string>, constants: array<string, string>} */',
        'attributes' => 
        array (
        ),
        'startLine' => 55,
        'endLine' => 59,
        'startColumn' => 2,
        'endColumn' => 3,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'scannedFiles' => 
      array (
        'declaringClassName' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator\\AutoloadSourceLocator',
        'implementingClassName' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator\\AutoloadSourceLocator',
        'name' => 'scannedFiles',
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
            'startLine' => 62,
            'endLine' => 62,
            'startTokenPos' => 298,
            'startFilePos' => 2039,
            'endTokenPos' => 299,
            'endFilePos' => 2040,
          ),
        ),
        'docComment' => '/** @var array<string, true> */',
        'attributes' => 
        array (
        ),
        'startLine' => 62,
        'endLine' => 62,
        'startColumn' => 2,
        'endColumn' => 34,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'startLineByClass' => 
      array (
        'declaringClassName' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator\\AutoloadSourceLocator',
        'implementingClassName' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator\\AutoloadSourceLocator',
        'name' => 'startLineByClass',
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
            'startLine' => 65,
            'endLine' => 65,
            'startTokenPos' => 312,
            'startFilePos' => 2111,
            'endTokenPos' => 313,
            'endFilePos' => 2112,
          ),
        ),
        'docComment' => '/** @var array<string, int> */',
        'attributes' => 
        array (
        ),
        'startLine' => 65,
        'endLine' => 65,
        'startColumn' => 2,
        'endColumn' => 38,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'fileNodesFetcher' => 
      array (
        'declaringClassName' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator\\AutoloadSourceLocator',
        'implementingClassName' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator\\AutoloadSourceLocator',
        'name' => 'fileNodesFetcher',
        'modifiers' => 4,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator\\FileNodesFetcher',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 67,
        'endLine' => 67,
        'startColumn' => 30,
        'endColumn' => 71,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'executeAutoloadersInFileReadTrap' => 
      array (
        'declaringClassName' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator\\AutoloadSourceLocator',
        'implementingClassName' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator\\AutoloadSourceLocator',
        'name' => 'executeAutoloadersInFileReadTrap',
        'modifiers' => 4,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 67,
        'endLine' => 67,
        'startColumn' => 74,
        'endColumn' => 119,
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
          'fileNodesFetcher' => 
          array (
            'name' => 'fileNodesFetcher',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator\\FileNodesFetcher',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 67,
            'endLine' => 67,
            'startColumn' => 30,
            'endColumn' => 71,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'executeAutoloadersInFileReadTrap' => 
          array (
            'name' => 'executeAutoloadersInFileReadTrap',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'bool',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 67,
            'endLine' => 67,
            'startColumn' => 74,
            'endColumn' => 119,
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
        'startLine' => 67,
        'endLine' => 69,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator',
        'declaringClassName' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator\\AutoloadSourceLocator',
        'implementingClassName' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator\\AutoloadSourceLocator',
        'currentClassName' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator\\AutoloadSourceLocator',
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
            'startLine' => 72,
            'endLine' => 72,
            'startColumn' => 35,
            'endColumn' => 54,
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
            'startLine' => 72,
            'endLine' => 72,
            'startColumn' => 57,
            'endColumn' => 78,
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
          0 => 
          array (
            'name' => 'Override',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => NULL,
        'startLine' => 71,
        'endLine' => 169,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator',
        'declaringClassName' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator\\AutoloadSourceLocator',
        'implementingClassName' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator\\AutoloadSourceLocator',
        'currentClassName' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator\\AutoloadSourceLocator',
        'aliasName' => NULL,
      ),
      'findReflection' => 
      array (
        'name' => 'findReflection',
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
            'startLine' => 171,
            'endLine' => 171,
            'startColumn' => 34,
            'endColumn' => 53,
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
            'startLine' => 171,
            'endLine' => 171,
            'startColumn' => 56,
            'endColumn' => 67,
            'parameterIndex' => 1,
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
            'startLine' => 171,
            'endLine' => 171,
            'startColumn' => 70,
            'endColumn' => 91,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'startLine' => 
          array (
            'name' => 'startLine',
            'default' => NULL,
            'type' => 
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
                      'name' => 'int',
                      'isIdentifier' => true,
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
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 171,
            'endLine' => 171,
            'startColumn' => 94,
            'endColumn' => 108,
            'parameterIndex' => 3,
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
        'startLine' => 171,
        'endLine' => 281,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator',
        'declaringClassName' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator\\AutoloadSourceLocator',
        'implementingClassName' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator\\AutoloadSourceLocator',
        'currentClassName' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator\\AutoloadSourceLocator',
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
            'startLine' => 284,
            'endLine' => 284,
            'startColumn' => 42,
            'endColumn' => 61,
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
            'startLine' => 284,
            'endLine' => 284,
            'startColumn' => 64,
            'endColumn' => 93,
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
          0 => 
          array (
            'name' => 'Override',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => NULL,
        'startLine' => 283,
        'endLine' => 287,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator',
        'declaringClassName' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator\\AutoloadSourceLocator',
        'implementingClassName' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator\\AutoloadSourceLocator',
        'currentClassName' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator\\AutoloadSourceLocator',
        'aliasName' => NULL,
      ),
      'getReflectionClass' => 
      array (
        'name' => 'getReflectionClass',
        'parameters' => 
        array (
          'className' => 
          array (
            'name' => 'className',
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
            'startLine' => 292,
            'endLine' => 292,
            'startColumn' => 38,
            'endColumn' => 54,
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
                  'name' => 'ReflectionClass',
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
 * @return ReflectionClass<object>|null
 */',
        'startLine' => 292,
        'endLine' => 299,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator',
        'declaringClassName' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator\\AutoloadSourceLocator',
        'implementingClassName' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator\\AutoloadSourceLocator',
        'currentClassName' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator\\AutoloadSourceLocator',
        'aliasName' => NULL,
      ),
      'locateClassByName' => 
      array (
        'name' => 'locateClassByName',
        'parameters' => 
        array (
          'className' => 
          array (
            'name' => 'className',
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
            'startLine' => 316,
            'endLine' => 316,
            'startColumn' => 37,
            'endColumn' => 53,
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
                  'name' => 'array',
                  'isIdentifier' => true,
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
 * Attempt to locate a class by name.
 *
 * If class already exists, simply use internal reflection API to get the
 * filename and store it.
 *
 * If class does not exist, we make an assumption that whatever autoloaders
 * that are registered will be loading a file. We then override the file://
 * protocol stream wrapper to "capture" the filename we expect the class to
 * be in, and then restore it. Note that class_exists will cause an error
 * that it cannot find the file, so we squelch the errors by overriding the
 * error handler temporarily.
 *
 * @return array{string[], string, int|null}|null
 */',
        'startLine' => 316,
        'endLine' => 378,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator',
        'declaringClassName' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator\\AutoloadSourceLocator',
        'implementingClassName' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator\\AutoloadSourceLocator',
        'currentClassName' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator\\AutoloadSourceLocator',
        'aliasName' => NULL,
      ),
      'silenceErrors' => 
      array (
        'name' => 'silenceErrors',
        'parameters' => 
        array (
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
        ),
        'docComment' => NULL,
        'startLine' => 380,
        'endLine' => 383,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator',
        'declaringClassName' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator\\AutoloadSourceLocator',
        'implementingClassName' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator\\AutoloadSourceLocator',
        'currentClassName' => 'PHPStan\\Reflection\\BetterReflection\\SourceLocator\\AutoloadSourceLocator',
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