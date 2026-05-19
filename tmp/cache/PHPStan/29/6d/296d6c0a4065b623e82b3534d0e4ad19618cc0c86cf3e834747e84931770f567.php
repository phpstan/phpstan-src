<?php declare(strict_types = 1);

// osfsl-/home/runner/work/phpstan-src/phpstan-src/vendor/composer/../ondrejmirtes/better-reflection/src/SourceLocator/SourceStubber/ReflectionSourceStubber.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\BetterReflection\SourceLocator\SourceStubber\ReflectionSourceStubber
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-5bc5acb92fe02d1403c35899e0e971031c5949a4911931709119b2df513db416-8.4.21-6.70.0.1',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber\\ReflectionSourceStubber',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/vendor/composer/../ondrejmirtes/better-reflection/src/SourceLocator/SourceStubber/ReflectionSourceStubber.php',
      ),
    ),
    'namespace' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber',
    'name' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber\\ReflectionSourceStubber',
    'shortName' => 'ReflectionSourceStubber',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * It generates a stub source from internal reflection for given class or function name.
 *
 * @internal
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 71,
    'endLine' => 830,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
      0 => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber\\SourceStubber',
    ),
    'traitClassNames' => 
    array (
    ),
    'immediateConstants' => 
    array (
    ),
    'immediateProperties' => 
    array (
      'phpVersion' => 
      array (
        'declaringClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber\\ReflectionSourceStubber',
        'implementingClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber\\ReflectionSourceStubber',
        'name' => 'phpVersion',
        'modifiers' => 4,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'int',
            'isIdentifier' => true,
          ),
        ),
        'default' => 
        array (
          'code' => '\\PHP_VERSION_ID',
          'attributes' => 
          array (
            'startLine' => 73,
            'endLine' => 73,
            'startTokenPos' => 412,
            'startFilePos' => 2289,
            'endTokenPos' => 412,
            'endFilePos' => 2302,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 73,
        'endLine' => 73,
        'startColumn' => 5,
        'endColumn' => 45,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'builderFactory' => 
      array (
        'declaringClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber\\ReflectionSourceStubber',
        'implementingClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber\\ReflectionSourceStubber',
        'name' => 'builderFactory',
        'modifiers' => 4,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PhpParser\\BuilderFactory',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 74,
        'endLine' => 74,
        'startColumn' => 5,
        'endColumn' => 43,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'prettyPrinter' => 
      array (
        'declaringClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber\\ReflectionSourceStubber',
        'implementingClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber\\ReflectionSourceStubber',
        'name' => 'prettyPrinter',
        'modifiers' => 4,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PhpParser\\PrettyPrinter\\Standard',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 76,
        'endLine' => 76,
        'startColumn' => 5,
        'endColumn' => 36,
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
          'prettyPrinter' => 
          array (
            'name' => 'prettyPrinter',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PhpParser\\PrettyPrinter\\Standard',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 78,
            'endLine' => 78,
            'startColumn' => 33,
            'endColumn' => 55,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'phpVersion' => 
          array (
            'name' => 'phpVersion',
            'default' => 
            array (
              'code' => '\\PHP_VERSION_ID',
              'attributes' => 
              array (
                'startLine' => 78,
                'endLine' => 78,
                'startTokenPos' => 446,
                'startFilePos' => 2463,
                'endTokenPos' => 446,
                'endFilePos' => 2476,
              ),
            ),
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'int',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 78,
            'endLine' => 78,
            'startColumn' => 58,
            'endColumn' => 89,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 78,
        'endLine' => 83,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber',
        'declaringClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber\\ReflectionSourceStubber',
        'implementingClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber\\ReflectionSourceStubber',
        'currentClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber\\ReflectionSourceStubber',
        'aliasName' => NULL,
      ),
      'generateClassStub' => 
      array (
        'name' => 'generateClassStub',
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
            'startLine' => 86,
            'endLine' => 86,
            'startColumn' => 39,
            'endColumn' => 55,
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
                  'name' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber\\StubData',
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
        'docComment' => '/** @param class-string|trait-string $className */',
        'startLine' => 86,
        'endLine' => 136,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber',
        'declaringClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber\\ReflectionSourceStubber',
        'implementingClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber\\ReflectionSourceStubber',
        'currentClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber\\ReflectionSourceStubber',
        'aliasName' => NULL,
      ),
      'generateFunctionStub' => 
      array (
        'name' => 'generateFunctionStub',
        'parameters' => 
        array (
          'functionName' => 
          array (
            'name' => 'functionName',
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
            'startLine' => 138,
            'endLine' => 138,
            'startColumn' => 42,
            'endColumn' => 61,
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
                  'name' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber\\StubData',
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
        'startLine' => 138,
        'endLine' => 145,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber',
        'declaringClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber\\ReflectionSourceStubber',
        'implementingClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber\\ReflectionSourceStubber',
        'currentClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber\\ReflectionSourceStubber',
        'aliasName' => NULL,
      ),
      'generateFunctionStubFromReflection' => 
      array (
        'name' => 'generateFunctionStubFromReflection',
        'parameters' => 
        array (
          'functionReflection' => 
          array (
            'name' => 'functionReflection',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'ReflectionFunction',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 147,
            'endLine' => 147,
            'startColumn' => 56,
            'endColumn' => 97,
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
                  'name' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber\\StubData',
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
        'startLine' => 147,
        'endLine' => 177,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber',
        'declaringClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber\\ReflectionSourceStubber',
        'implementingClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber\\ReflectionSourceStubber',
        'currentClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber\\ReflectionSourceStubber',
        'aliasName' => NULL,
      ),
      'generateConstantStub' => 
      array (
        'name' => 'generateConstantStub',
        'parameters' => 
        array (
          'constantName' => 
          array (
            'name' => 'constantName',
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
            'startLine' => 179,
            'endLine' => 179,
            'startColumn' => 42,
            'endColumn' => 61,
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
                  'name' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber\\StubData',
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
        'startLine' => 179,
        'endLine' => 199,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber',
        'declaringClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber\\ReflectionSourceStubber',
        'implementingClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber\\ReflectionSourceStubber',
        'currentClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber\\ReflectionSourceStubber',
        'aliasName' => NULL,
      ),
      'findConstantData' => 
      array (
        'name' => 'findConstantData',
        'parameters' => 
        array (
          'constantName' => 
          array (
            'name' => 'constantName',
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
            'startLine' => 202,
            'endLine' => 202,
            'startColumn' => 39,
            'endColumn' => 58,
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
        'docComment' => '/** @return array{0: scalar|list<scalar>|resource|null, 1: non-empty-string|null}|null */',
        'startLine' => 202,
        'endLine' => 217,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber',
        'declaringClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber\\ReflectionSourceStubber',
        'implementingClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber\\ReflectionSourceStubber',
        'currentClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber\\ReflectionSourceStubber',
        'aliasName' => NULL,
      ),
      'createClass' => 
      array (
        'name' => 'createClass',
        'parameters' => 
        array (
          'classReflection' => 
          array (
            'name' => 'classReflection',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'ReflectionClass',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 222,
            'endLine' => 222,
            'startColumn' => 34,
            'endColumn' => 69,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @return \\PhpParser\\Builder\\Class_|\\PhpParser\\Builder\\Interface_|\\PhpParser\\Builder\\Trait_|\\PhpParser\\Builder\\Enum_
 */',
        'startLine' => 222,
        'endLine' => 237,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber',
        'declaringClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber\\ReflectionSourceStubber',
        'implementingClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber\\ReflectionSourceStubber',
        'currentClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber\\ReflectionSourceStubber',
        'aliasName' => NULL,
      ),
      'addAttributes' => 
      array (
        'name' => 'addAttributes',
        'parameters' => 
        array (
          'node' => 
          array (
            'name' => 'node',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 244,
            'endLine' => 244,
            'startColumn' => 9,
            'endColumn' => 13,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'reflection' => 
          array (
            'name' => 'reflection',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 245,
            'endLine' => 245,
            'startColumn' => 9,
            'endColumn' => 19,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @param \\PhpParser\\Builder\\Class_|\\PhpParser\\Builder\\Interface_|\\PhpParser\\Builder\\Trait_|\\PhpParser\\Builder\\Enum_|\\PhpParser\\Builder\\ClassConst|\\PhpParser\\Builder\\EnumCase|\\PhpParser\\Builder\\Method|\\PhpParser\\Builder\\Property|\\PhpParser\\Builder\\Function_|\\PhpParser\\Builder\\Param $node
 * @param CoreReflectionClass|CoreReflectionClassConstant|CoreReflectionEnumUnitCase|CoreReflectionMethod|CoreReflectionProperty|CoreReflectionFunction|CoreReflectionParameter $reflection
 */',
        'startLine' => 243,
        'endLine' => 260,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber',
        'declaringClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber\\ReflectionSourceStubber',
        'implementingClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber\\ReflectionSourceStubber',
        'currentClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber\\ReflectionSourceStubber',
        'aliasName' => NULL,
      ),
      'addDocComment' => 
      array (
        'name' => 'addDocComment',
        'parameters' => 
        array (
          'node' => 
          array (
            'name' => 'node',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 267,
            'endLine' => 267,
            'startColumn' => 9,
            'endColumn' => 13,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'reflection' => 
          array (
            'name' => 'reflection',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 268,
            'endLine' => 268,
            'startColumn' => 9,
            'endColumn' => 19,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @param \\PhpParser\\Builder\\Class_|\\PhpParser\\Builder\\Interface_|\\PhpParser\\Builder\\Trait_|\\PhpParser\\Builder\\Enum_|\\PhpParser\\Builder\\Method|\\PhpParser\\Builder\\Property|\\PhpParser\\Builder\\Function_ $node
 * @param CoreReflectionClass|CoreReflectionMethod|CoreReflectionProperty|CoreReflectionFunction $reflection
 */',
        'startLine' => 266,
        'endLine' => 298,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber',
        'declaringClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber\\ReflectionSourceStubber',
        'implementingClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber\\ReflectionSourceStubber',
        'currentClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber\\ReflectionSourceStubber',
        'aliasName' => NULL,
      ),
      'addEnumBackingType' => 
      array (
        'name' => 'addEnumBackingType',
        'parameters' => 
        array (
          'enumNode' => 
          array (
            'name' => 'enumNode',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PhpParser\\Builder\\Enum_',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 300,
            'endLine' => 300,
            'startColumn' => 41,
            'endColumn' => 55,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'enumReflection' => 
          array (
            'name' => 'enumReflection',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'ReflectionEnum',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 300,
            'endLine' => 300,
            'startColumn' => 58,
            'endColumn' => 91,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 300,
        'endLine' => 311,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber',
        'declaringClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber\\ReflectionSourceStubber',
        'implementingClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber\\ReflectionSourceStubber',
        'currentClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber\\ReflectionSourceStubber',
        'aliasName' => NULL,
      ),
      'addClassModifiers' => 
      array (
        'name' => 'addClassModifiers',
        'parameters' => 
        array (
          'classNode' => 
          array (
            'name' => 'classNode',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PhpParser\\Builder\\Class_',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 313,
            'endLine' => 313,
            'startColumn' => 40,
            'endColumn' => 56,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'classReflection' => 
          array (
            'name' => 'classReflection',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'ReflectionClass',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 313,
            'endLine' => 313,
            'startColumn' => 59,
            'endColumn' => 94,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 313,
        'endLine' => 325,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber',
        'declaringClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber\\ReflectionSourceStubber',
        'implementingClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber\\ReflectionSourceStubber',
        'currentClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber\\ReflectionSourceStubber',
        'aliasName' => NULL,
      ),
      'addExtendsAndImplements' => 
      array (
        'name' => 'addExtendsAndImplements',
        'parameters' => 
        array (
          'classNode' => 
          array (
            'name' => 'classNode',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 330,
            'endLine' => 330,
            'startColumn' => 46,
            'endColumn' => 55,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'classReflection' => 
          array (
            'name' => 'classReflection',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'ReflectionClass',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 330,
            'endLine' => 330,
            'startColumn' => 58,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @param \\PhpParser\\Builder\\Class_|\\PhpParser\\Builder\\Interface_|\\PhpParser\\Builder\\Enum_ $classNode
 */',
        'startLine' => 330,
        'endLine' => 364,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber',
        'declaringClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber\\ReflectionSourceStubber',
        'implementingClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber\\ReflectionSourceStubber',
        'currentClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber\\ReflectionSourceStubber',
        'aliasName' => NULL,
      ),
      'addTraitUse' => 
      array (
        'name' => 'addTraitUse',
        'parameters' => 
        array (
          'classNode' => 
          array (
            'name' => 'classNode',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 369,
            'endLine' => 369,
            'startColumn' => 34,
            'endColumn' => 43,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'classReflection' => 
          array (
            'name' => 'classReflection',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'ReflectionClass',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 369,
            'endLine' => 369,
            'startColumn' => 46,
            'endColumn' => 81,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @param \\PhpParser\\Builder\\Class_|\\PhpParser\\Builder\\Trait_|\\PhpParser\\Builder\\Enum_ $classNode
 */',
        'startLine' => 369,
        'endLine' => 393,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber',
        'declaringClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber\\ReflectionSourceStubber',
        'implementingClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber\\ReflectionSourceStubber',
        'currentClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber\\ReflectionSourceStubber',
        'aliasName' => NULL,
      ),
      'addProperties' => 
      array (
        'name' => 'addProperties',
        'parameters' => 
        array (
          'classNode' => 
          array (
            'name' => 'classNode',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 398,
            'endLine' => 398,
            'startColumn' => 36,
            'endColumn' => 45,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'classReflection' => 
          array (
            'name' => 'classReflection',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'ReflectionClass',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 398,
            'endLine' => 398,
            'startColumn' => 48,
            'endColumn' => 83,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @param \\PhpParser\\Builder\\Class_|\\PhpParser\\Builder\\Trait_ $classNode
 */',
        'startLine' => 398,
        'endLine' => 430,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber',
        'declaringClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber\\ReflectionSourceStubber',
        'implementingClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber\\ReflectionSourceStubber',
        'currentClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber\\ReflectionSourceStubber',
        'aliasName' => NULL,
      ),
      'isPropertyDeclaredInClass' => 
      array (
        'name' => 'isPropertyDeclaredInClass',
        'parameters' => 
        array (
          'propertyReflection' => 
          array (
            'name' => 'propertyReflection',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'ReflectionProperty',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 432,
            'endLine' => 432,
            'startColumn' => 48,
            'endColumn' => 89,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'classReflection' => 
          array (
            'name' => 'classReflection',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'ReflectionClass',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 432,
            'endLine' => 432,
            'startColumn' => 92,
            'endColumn' => 127,
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
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 432,
        'endLine' => 445,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber',
        'declaringClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber\\ReflectionSourceStubber',
        'implementingClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber\\ReflectionSourceStubber',
        'currentClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber\\ReflectionSourceStubber',
        'aliasName' => NULL,
      ),
      'addPropertyModifiers' => 
      array (
        'name' => 'addPropertyModifiers',
        'parameters' => 
        array (
          'propertyNode' => 
          array (
            'name' => 'propertyNode',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PhpParser\\Builder\\Property',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 447,
            'endLine' => 447,
            'startColumn' => 43,
            'endColumn' => 64,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'propertyReflection' => 
          array (
            'name' => 'propertyReflection',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'ReflectionProperty',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 447,
            'endLine' => 447,
            'startColumn' => 67,
            'endColumn' => 108,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 447,
        'endLine' => 466,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber',
        'declaringClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber\\ReflectionSourceStubber',
        'implementingClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber\\ReflectionSourceStubber',
        'currentClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber\\ReflectionSourceStubber',
        'aliasName' => NULL,
      ),
      'addEnumCases' => 
      array (
        'name' => 'addEnumCases',
        'parameters' => 
        array (
          'enumNode' => 
          array (
            'name' => 'enumNode',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PhpParser\\Builder\\Enum_',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 468,
            'endLine' => 468,
            'startColumn' => 35,
            'endColumn' => 49,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'enumReflection' => 
          array (
            'name' => 'enumReflection',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'ReflectionEnum',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 468,
            'endLine' => 468,
            'startColumn' => 52,
            'endColumn' => 85,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 468,
        'endLine' => 481,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber',
        'declaringClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber\\ReflectionSourceStubber',
        'implementingClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber\\ReflectionSourceStubber',
        'currentClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber\\ReflectionSourceStubber',
        'aliasName' => NULL,
      ),
      'addClassConstants' => 
      array (
        'name' => 'addClassConstants',
        'parameters' => 
        array (
          'classNode' => 
          array (
            'name' => 'classNode',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 486,
            'endLine' => 486,
            'startColumn' => 40,
            'endColumn' => 49,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'classReflection' => 
          array (
            'name' => 'classReflection',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'ReflectionClass',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 486,
            'endLine' => 486,
            'startColumn' => 52,
            'endColumn' => 87,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @param \\PhpParser\\Builder\\Class_|\\PhpParser\\Builder\\Interface_|\\PhpParser\\Builder\\Trait_|\\PhpParser\\Builder\\Enum_ $classNode
 */',
        'startLine' => 486,
        'endLine' => 519,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber',
        'declaringClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber\\ReflectionSourceStubber',
        'implementingClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber\\ReflectionSourceStubber',
        'currentClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber\\ReflectionSourceStubber',
        'aliasName' => NULL,
      ),
      'addClassConstantModifiers' => 
      array (
        'name' => 'addClassConstantModifiers',
        'parameters' => 
        array (
          'classConstantNode' => 
          array (
            'name' => 'classConstantNode',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PhpParser\\Builder\\ClassConst',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 521,
            'endLine' => 521,
            'startColumn' => 48,
            'endColumn' => 76,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'classConstantReflection' => 
          array (
            'name' => 'classConstantReflection',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'ReflectionClassConstant',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 521,
            'endLine' => 521,
            'startColumn' => 79,
            'endColumn' => 130,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 521,
        'endLine' => 534,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber',
        'declaringClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber\\ReflectionSourceStubber',
        'implementingClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber\\ReflectionSourceStubber',
        'currentClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber\\ReflectionSourceStubber',
        'aliasName' => NULL,
      ),
      'addMethods' => 
      array (
        'name' => 'addMethods',
        'parameters' => 
        array (
          'classNode' => 
          array (
            'name' => 'classNode',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 539,
            'endLine' => 539,
            'startColumn' => 33,
            'endColumn' => 42,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'classReflection' => 
          array (
            'name' => 'classReflection',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'ReflectionClass',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 539,
            'endLine' => 539,
            'startColumn' => 45,
            'endColumn' => 80,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @param \\PhpParser\\Builder\\Class_|\\PhpParser\\Builder\\Interface_|\\PhpParser\\Builder\\Trait_|\\PhpParser\\Builder\\Enum_ $classNode
 */',
        'startLine' => 539,
        'endLine' => 565,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber',
        'declaringClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber\\ReflectionSourceStubber',
        'implementingClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber\\ReflectionSourceStubber',
        'currentClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber\\ReflectionSourceStubber',
        'aliasName' => NULL,
      ),
      'isMethodDeclaredInClass' => 
      array (
        'name' => 'isMethodDeclaredInClass',
        'parameters' => 
        array (
          'methodReflection' => 
          array (
            'name' => 'methodReflection',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'ReflectionMethod',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 567,
            'endLine' => 567,
            'startColumn' => 46,
            'endColumn' => 83,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'classReflection' => 
          array (
            'name' => 'classReflection',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'ReflectionClass',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 567,
            'endLine' => 567,
            'startColumn' => 86,
            'endColumn' => 121,
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
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 567,
        'endLine' => 599,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber',
        'declaringClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber\\ReflectionSourceStubber',
        'implementingClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber\\ReflectionSourceStubber',
        'currentClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber\\ReflectionSourceStubber',
        'aliasName' => NULL,
      ),
      'addMethodFlags' => 
      array (
        'name' => 'addMethodFlags',
        'parameters' => 
        array (
          'methodNode' => 
          array (
            'name' => 'methodNode',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PhpParser\\Builder\\Method',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 601,
            'endLine' => 601,
            'startColumn' => 37,
            'endColumn' => 54,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'methodReflection' => 
          array (
            'name' => 'methodReflection',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'ReflectionMethod',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 601,
            'endLine' => 601,
            'startColumn' => 57,
            'endColumn' => 94,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 601,
        'endLine' => 632,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber',
        'declaringClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber\\ReflectionSourceStubber',
        'implementingClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber\\ReflectionSourceStubber',
        'currentClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber\\ReflectionSourceStubber',
        'aliasName' => NULL,
      ),
      'addParameters' => 
      array (
        'name' => 'addParameters',
        'parameters' => 
        array (
          'functionNode' => 
          array (
            'name' => 'functionNode',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PhpParser\\Builder\\FunctionLike',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 634,
            'endLine' => 634,
            'startColumn' => 36,
            'endColumn' => 61,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'functionReflectionAbstract' => 
          array (
            'name' => 'functionReflectionAbstract',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'ReflectionFunctionAbstract',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 634,
            'endLine' => 634,
            'startColumn' => 64,
            'endColumn' => 121,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 634,
        'endLine' => 645,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber',
        'declaringClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber\\ReflectionSourceStubber',
        'implementingClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber\\ReflectionSourceStubber',
        'currentClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber\\ReflectionSourceStubber',
        'aliasName' => NULL,
      ),
      'addParameterModifiers' => 
      array (
        'name' => 'addParameterModifiers',
        'parameters' => 
        array (
          'parameterReflection' => 
          array (
            'name' => 'parameterReflection',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'ReflectionParameter',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 647,
            'endLine' => 647,
            'startColumn' => 44,
            'endColumn' => 87,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'parameterNode' => 
          array (
            'name' => 'parameterNode',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PhpParser\\Builder\\Param',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 647,
            'endLine' => 647,
            'startColumn' => 90,
            'endColumn' => 109,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 647,
        'endLine' => 665,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber',
        'declaringClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber\\ReflectionSourceStubber',
        'implementingClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber\\ReflectionSourceStubber',
        'currentClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber\\ReflectionSourceStubber',
        'aliasName' => NULL,
      ),
      'setParameterDefaultValue' => 
      array (
        'name' => 'setParameterDefaultValue',
        'parameters' => 
        array (
          'parameterReflection' => 
          array (
            'name' => 'parameterReflection',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'ReflectionParameter',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 667,
            'endLine' => 667,
            'startColumn' => 47,
            'endColumn' => 90,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'parameterNode' => 
          array (
            'name' => 'parameterNode',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PhpParser\\Builder\\Param',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 667,
            'endLine' => 667,
            'startColumn' => 93,
            'endColumn' => 112,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 667,
        'endLine' => 711,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber',
        'declaringClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber\\ReflectionSourceStubber',
        'implementingClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber\\ReflectionSourceStubber',
        'currentClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber\\ReflectionSourceStubber',
        'aliasName' => NULL,
      ),
      'formatType' => 
      array (
        'name' => 'formatType',
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
                'name' => 'ReflectionType',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 716,
            'endLine' => 716,
            'startColumn' => 33,
            'endColumn' => 56,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @return \\PhpParser\\Node\\Name|\\PhpParser\\Node\\NullableType|\\PhpParser\\Node\\UnionType|\\PhpParser\\Node\\IntersectionType
 */',
        'startLine' => 716,
        'endLine' => 784,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber',
        'declaringClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber\\ReflectionSourceStubber',
        'implementingClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber\\ReflectionSourceStubber',
        'currentClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber\\ReflectionSourceStubber',
        'aliasName' => NULL,
      ),
      'formatTypes' => 
      array (
        'name' => 'formatTypes',
        'parameters' => 
        array (
          'types' => 
          array (
            'name' => 'types',
            'default' => NULL,
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
            'startLine' => 791,
            'endLine' => 791,
            'startColumn' => 34,
            'endColumn' => 45,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @param list<CoreReflectionType> $types
 *
 * @return list<Name|UnionType|IntersectionType>
 */',
        'startLine' => 791,
        'endLine' => 799,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber',
        'declaringClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber\\ReflectionSourceStubber',
        'implementingClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber\\ReflectionSourceStubber',
        'currentClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber\\ReflectionSourceStubber',
        'aliasName' => NULL,
      ),
      'formatNamedType' => 
      array (
        'name' => 'formatNamedType',
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
                'name' => 'ReflectionNamedType',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 801,
            'endLine' => 801,
            'startColumn' => 38,
            'endColumn' => 66,
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
            'name' => 'PhpParser\\Node\\Name',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 801,
        'endLine' => 806,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber',
        'declaringClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber\\ReflectionSourceStubber',
        'implementingClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber\\ReflectionSourceStubber',
        'currentClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber\\ReflectionSourceStubber',
        'aliasName' => NULL,
      ),
      'generateStubInNamespace' => 
      array (
        'name' => 'generateStubInNamespace',
        'parameters' => 
        array (
          'node' => 
          array (
            'name' => 'node',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PhpParser\\Node',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 808,
            'endLine' => 808,
            'startColumn' => 46,
            'endColumn' => 55,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'namespaceName' => 
          array (
            'name' => 'namespaceName',
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
            'startLine' => 808,
            'endLine' => 808,
            'startColumn' => 58,
            'endColumn' => 78,
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
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 808,
        'endLine' => 814,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber',
        'declaringClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber\\ReflectionSourceStubber',
        'implementingClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber\\ReflectionSourceStubber',
        'currentClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber\\ReflectionSourceStubber',
        'aliasName' => NULL,
      ),
      'generateStub' => 
      array (
        'name' => 'generateStub',
        'parameters' => 
        array (
          'node' => 
          array (
            'name' => 'node',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PhpParser\\Node',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 816,
            'endLine' => 816,
            'startColumn' => 35,
            'endColumn' => 44,
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
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 816,
        'endLine' => 823,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber',
        'declaringClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber\\ReflectionSourceStubber',
        'implementingClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber\\ReflectionSourceStubber',
        'currentClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber\\ReflectionSourceStubber',
        'aliasName' => NULL,
      ),
      'createStubData' => 
      array (
        'name' => 'createStubData',
        'parameters' => 
        array (
          'stub' => 
          array (
            'name' => 'stub',
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
            'startLine' => 826,
            'endLine' => 826,
            'startColumn' => 37,
            'endColumn' => 48,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'extensionName' => 
          array (
            'name' => 'extensionName',
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
                      'name' => 'string',
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
            'startLine' => 826,
            'endLine' => 826,
            'startColumn' => 51,
            'endColumn' => 72,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'fileName' => 
          array (
            'name' => 'fileName',
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
                      'name' => 'string',
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
            'startLine' => 826,
            'endLine' => 826,
            'startColumn' => 75,
            'endColumn' => 91,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber\\StubData',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/** @param non-empty-string|null $extensionName */',
        'startLine' => 826,
        'endLine' => 829,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber',
        'declaringClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber\\ReflectionSourceStubber',
        'implementingClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber\\ReflectionSourceStubber',
        'currentClassName' => 'PHPStan\\BetterReflection\\SourceLocator\\SourceStubber\\ReflectionSourceStubber',
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