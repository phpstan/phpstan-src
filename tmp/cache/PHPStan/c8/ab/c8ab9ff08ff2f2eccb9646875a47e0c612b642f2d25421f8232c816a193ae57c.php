<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/src/Analyser/Scope.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\Analyser\Scope
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-c20504f414f06c50487b63876741190989c5bf5e4b05373ab654506057abcf1a',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\Analyser\\Scope',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/src/Analyser/Scope.php',
      ),
    ),
    'namespace' => 'PHPStan\\Analyser',
    'name' => 'PHPStan\\Analyser\\Scope',
    'shortName' => 'Scope',
    'isInterface' => true,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Represents the state of the analyser at a specific position in the AST.
 *
 * The Scope tracks everything PHPStan knows at a given point in code: variable types,
 * the current class/function/method context, whether strict_types is enabled, and more.
 * It is the primary interface through which rules and extensions query information
 * about the analysed code.
 *
 * The Scope is passed as a parameter to:
 * - Custom rules (2nd parameter of processNode())
 * - Dynamic return type extensions (last parameter of getTypeFrom*Call())
 * - Dynamic throw type extensions
 * - Type-specifying extensions (3rd parameter of specifyTypes())
 *
 * The Scope is immutable from the extension\'s perspective. Each AST node gets
 * its own Scope reflecting the analysis state at that point. For example, after
 * an `if ($x instanceof Foo)` check, the Scope inside the if-branch knows that
 * $x is of type Foo.
 *
 * @api
 * @api-do-not-implement
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 47,
    'endLine' => 345,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
      0 => 'PHPStan\\Reflection\\ClassMemberAccessAnswerer',
      1 => 'PHPStan\\Reflection\\NamespaceAnswerer',
    ),
    'traitClassNames' => 
    array (
    ),
    'immediateConstants' => 
    array (
      'SUPERGLOBAL_VARIABLES' => 
      array (
        'declaringClassName' => 'PHPStan\\Analyser\\Scope',
        'implementingClassName' => 'PHPStan\\Analyser\\Scope',
        'name' => 'SUPERGLOBAL_VARIABLES',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'GLOBALS\', \'_SERVER\', \'_GET\', \'_POST\', \'_FILES\', \'_COOKIE\', \'_SESSION\', \'_REQUEST\', \'_ENV\']',
          'attributes' => 
          array (
            'startLine' => 50,
            'endLine' => 60,
            'startTokenPos' => 134,
            'startFilePos' => 1833,
            'endTokenPos' => 163,
            'endFilePos' => 1946,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 50,
        'endLine' => 60,
        'startColumn' => 2,
        'endColumn' => 3,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'getFile' => 
      array (
        'name' => 'getFile',
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
        'docComment' => '/**
 * When analysing a trait, returns the file where the trait is used,
 * not the trait file itself. Use getFileDescription() for the trait file path.
 */',
        'startLine' => 66,
        'endLine' => 66,
        'startColumn' => 2,
        'endColumn' => 35,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Analyser',
        'declaringClassName' => 'PHPStan\\Analyser\\Scope',
        'implementingClassName' => 'PHPStan\\Analyser\\Scope',
        'currentClassName' => 'PHPStan\\Analyser\\Scope',
        'aliasName' => NULL,
      ),
      'getFileDescription' => 
      array (
        'name' => 'getFileDescription',
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
        'docComment' => '/**
 * For traits, returns the trait file path with the using class context,
 * e.g. "TraitFile.php (in context of class MyClass)".
 */',
        'startLine' => 72,
        'endLine' => 72,
        'startColumn' => 2,
        'endColumn' => 46,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Analyser',
        'declaringClassName' => 'PHPStan\\Analyser\\Scope',
        'implementingClassName' => 'PHPStan\\Analyser\\Scope',
        'currentClassName' => 'PHPStan\\Analyser\\Scope',
        'aliasName' => NULL,
      ),
      'isDeclareStrictTypes' => 
      array (
        'name' => 'isDeclareStrictTypes',
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
        'startLine' => 74,
        'endLine' => 74,
        'startColumn' => 2,
        'endColumn' => 46,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Analyser',
        'declaringClassName' => 'PHPStan\\Analyser\\Scope',
        'implementingClassName' => 'PHPStan\\Analyser\\Scope',
        'currentClassName' => 'PHPStan\\Analyser\\Scope',
        'aliasName' => NULL,
      ),
      'isInTrait' => 
      array (
        'name' => 'isInTrait',
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
        'docComment' => '/**
 * @phpstan-assert-if-true !null $this->getTraitReflection()
 */',
        'startLine' => 79,
        'endLine' => 79,
        'startColumn' => 2,
        'endColumn' => 35,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Analyser',
        'declaringClassName' => 'PHPStan\\Analyser\\Scope',
        'implementingClassName' => 'PHPStan\\Analyser\\Scope',
        'currentClassName' => 'PHPStan\\Analyser\\Scope',
        'aliasName' => NULL,
      ),
      'getTraitReflection' => 
      array (
        'name' => 'getTraitReflection',
        'parameters' => 
        array (
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
                  'name' => 'PHPStan\\Reflection\\ClassReflection',
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
 * Returns the trait itself, not the class using the trait.
 * Use getClassReflection() for the using class.
 */',
        'startLine' => 85,
        'endLine' => 85,
        'startColumn' => 2,
        'endColumn' => 56,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Analyser',
        'declaringClassName' => 'PHPStan\\Analyser\\Scope',
        'implementingClassName' => 'PHPStan\\Analyser\\Scope',
        'currentClassName' => 'PHPStan\\Analyser\\Scope',
        'aliasName' => NULL,
      ),
      'getFunction' => 
      array (
        'name' => 'getFunction',
        'parameters' => 
        array (
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
                  'name' => 'PHPStan\\Reflection\\Php\\PhpFunctionFromParserNodeReflection',
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
        'startLine' => 87,
        'endLine' => 87,
        'startColumn' => 2,
        'endColumn' => 69,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Analyser',
        'declaringClassName' => 'PHPStan\\Analyser\\Scope',
        'implementingClassName' => 'PHPStan\\Analyser\\Scope',
        'currentClassName' => 'PHPStan\\Analyser\\Scope',
        'aliasName' => NULL,
      ),
      'getFunctionName' => 
      array (
        'name' => 'getFunctionName',
        'parameters' => 
        array (
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
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 89,
        'endLine' => 89,
        'startColumn' => 2,
        'endColumn' => 44,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Analyser',
        'declaringClassName' => 'PHPStan\\Analyser\\Scope',
        'implementingClassName' => 'PHPStan\\Analyser\\Scope',
        'currentClassName' => 'PHPStan\\Analyser\\Scope',
        'aliasName' => NULL,
      ),
      'getParentScope' => 
      array (
        'name' => 'getParentScope',
        'parameters' => 
        array (
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
                  'name' => 'self',
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
        'startLine' => 91,
        'endLine' => 91,
        'startColumn' => 2,
        'endColumn' => 41,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Analyser',
        'declaringClassName' => 'PHPStan\\Analyser\\Scope',
        'implementingClassName' => 'PHPStan\\Analyser\\Scope',
        'currentClassName' => 'PHPStan\\Analyser\\Scope',
        'aliasName' => NULL,
      ),
      'hasVariableType' => 
      array (
        'name' => 'hasVariableType',
        'parameters' => 
        array (
          'variableName' => 
          array (
            'name' => 'variableName',
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
            'startLine' => 93,
            'endLine' => 93,
            'startColumn' => 34,
            'endColumn' => 53,
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
            'name' => 'PHPStan\\TrinaryLogic',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 93,
        'endLine' => 93,
        'startColumn' => 2,
        'endColumn' => 69,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Analyser',
        'declaringClassName' => 'PHPStan\\Analyser\\Scope',
        'implementingClassName' => 'PHPStan\\Analyser\\Scope',
        'currentClassName' => 'PHPStan\\Analyser\\Scope',
        'aliasName' => NULL,
      ),
      'getVariableType' => 
      array (
        'name' => 'getVariableType',
        'parameters' => 
        array (
          'variableName' => 
          array (
            'name' => 'variableName',
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
            'startLine' => 95,
            'endLine' => 95,
            'startColumn' => 34,
            'endColumn' => 53,
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
            'name' => 'PHPStan\\Type\\Type',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 95,
        'endLine' => 95,
        'startColumn' => 2,
        'endColumn' => 61,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Analyser',
        'declaringClassName' => 'PHPStan\\Analyser\\Scope',
        'implementingClassName' => 'PHPStan\\Analyser\\Scope',
        'currentClassName' => 'PHPStan\\Analyser\\Scope',
        'aliasName' => NULL,
      ),
      'canAnyVariableExist' => 
      array (
        'name' => 'canAnyVariableExist',
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
        'docComment' => '/**
 * True at the top level of a file or after extract() — contexts where
 * arbitrary variables may exist.
 */',
        'startLine' => 101,
        'endLine' => 101,
        'startColumn' => 2,
        'endColumn' => 45,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Analyser',
        'declaringClassName' => 'PHPStan\\Analyser\\Scope',
        'implementingClassName' => 'PHPStan\\Analyser\\Scope',
        'currentClassName' => 'PHPStan\\Analyser\\Scope',
        'aliasName' => NULL,
      ),
      'getDefinedVariables' => 
      array (
        'name' => 'getDefinedVariables',
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
        'docComment' => '/** @return array<int, string> */',
        'startLine' => 104,
        'endLine' => 104,
        'startColumn' => 2,
        'endColumn' => 46,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Analyser',
        'declaringClassName' => 'PHPStan\\Analyser\\Scope',
        'implementingClassName' => 'PHPStan\\Analyser\\Scope',
        'currentClassName' => 'PHPStan\\Analyser\\Scope',
        'aliasName' => NULL,
      ),
      'getMaybeDefinedVariables' => 
      array (
        'name' => 'getMaybeDefinedVariables',
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
        'docComment' => '/**
 * Variables with TrinaryLogic::Maybe certainty — defined in some code paths but not others.
 *
 * @return array<int, string>
 */',
        'startLine' => 111,
        'endLine' => 111,
        'startColumn' => 2,
        'endColumn' => 51,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Analyser',
        'declaringClassName' => 'PHPStan\\Analyser\\Scope',
        'implementingClassName' => 'PHPStan\\Analyser\\Scope',
        'currentClassName' => 'PHPStan\\Analyser\\Scope',
        'aliasName' => NULL,
      ),
      'hasConstant' => 
      array (
        'name' => 'hasConstant',
        'parameters' => 
        array (
          'name' => 
          array (
            'name' => 'name',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PhpParser\\Node\\Name',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 113,
            'endLine' => 113,
            'startColumn' => 30,
            'endColumn' => 39,
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
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 113,
        'endLine' => 113,
        'startColumn' => 2,
        'endColumn' => 47,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Analyser',
        'declaringClassName' => 'PHPStan\\Analyser\\Scope',
        'implementingClassName' => 'PHPStan\\Analyser\\Scope',
        'currentClassName' => 'PHPStan\\Analyser\\Scope',
        'aliasName' => NULL,
      ),
      'getPropertyReflection' => 
      array (
        'name' => 'getPropertyReflection',
        'parameters' => 
        array (
          'typeWithProperty' => 
          array (
            'name' => 'typeWithProperty',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\Type\\Type',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 118,
            'endLine' => 118,
            'startColumn' => 40,
            'endColumn' => 61,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'propertyName' => 
          array (
            'name' => 'propertyName',
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
            'startLine' => 118,
            'endLine' => 118,
            'startColumn' => 64,
            'endColumn' => 83,
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
                  'name' => 'PHPStan\\Reflection\\ExtendedPropertyReflection',
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
 * @deprecated Use getInstancePropertyReflection or getStaticPropertyReflection instead
 */',
        'startLine' => 118,
        'endLine' => 118,
        'startColumn' => 2,
        'endColumn' => 114,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Analyser',
        'declaringClassName' => 'PHPStan\\Analyser\\Scope',
        'implementingClassName' => 'PHPStan\\Analyser\\Scope',
        'currentClassName' => 'PHPStan\\Analyser\\Scope',
        'aliasName' => NULL,
      ),
      'getInstancePropertyReflection' => 
      array (
        'name' => 'getInstancePropertyReflection',
        'parameters' => 
        array (
          'typeWithProperty' => 
          array (
            'name' => 'typeWithProperty',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\Type\\Type',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 120,
            'endLine' => 120,
            'startColumn' => 48,
            'endColumn' => 69,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'propertyName' => 
          array (
            'name' => 'propertyName',
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
            'startLine' => 120,
            'endLine' => 120,
            'startColumn' => 72,
            'endColumn' => 91,
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
                  'name' => 'PHPStan\\Reflection\\ExtendedPropertyReflection',
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
        'startLine' => 120,
        'endLine' => 120,
        'startColumn' => 2,
        'endColumn' => 122,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Analyser',
        'declaringClassName' => 'PHPStan\\Analyser\\Scope',
        'implementingClassName' => 'PHPStan\\Analyser\\Scope',
        'currentClassName' => 'PHPStan\\Analyser\\Scope',
        'aliasName' => NULL,
      ),
      'getStaticPropertyReflection' => 
      array (
        'name' => 'getStaticPropertyReflection',
        'parameters' => 
        array (
          'typeWithProperty' => 
          array (
            'name' => 'typeWithProperty',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\Type\\Type',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 122,
            'endLine' => 122,
            'startColumn' => 46,
            'endColumn' => 67,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'propertyName' => 
          array (
            'name' => 'propertyName',
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
            'startLine' => 122,
            'endLine' => 122,
            'startColumn' => 70,
            'endColumn' => 89,
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
                  'name' => 'PHPStan\\Reflection\\ExtendedPropertyReflection',
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
        'startLine' => 122,
        'endLine' => 122,
        'startColumn' => 2,
        'endColumn' => 120,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Analyser',
        'declaringClassName' => 'PHPStan\\Analyser\\Scope',
        'implementingClassName' => 'PHPStan\\Analyser\\Scope',
        'currentClassName' => 'PHPStan\\Analyser\\Scope',
        'aliasName' => NULL,
      ),
      'getMethodReflection' => 
      array (
        'name' => 'getMethodReflection',
        'parameters' => 
        array (
          'typeWithMethod' => 
          array (
            'name' => 'typeWithMethod',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\Type\\Type',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 124,
            'endLine' => 124,
            'startColumn' => 38,
            'endColumn' => 57,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'methodName' => 
          array (
            'name' => 'methodName',
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
            'startLine' => 124,
            'endLine' => 124,
            'startColumn' => 60,
            'endColumn' => 77,
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
                  'name' => 'PHPStan\\Reflection\\ExtendedMethodReflection',
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
        'startLine' => 124,
        'endLine' => 124,
        'startColumn' => 2,
        'endColumn' => 106,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Analyser',
        'declaringClassName' => 'PHPStan\\Analyser\\Scope',
        'implementingClassName' => 'PHPStan\\Analyser\\Scope',
        'currentClassName' => 'PHPStan\\Analyser\\Scope',
        'aliasName' => NULL,
      ),
      'getConstantReflection' => 
      array (
        'name' => 'getConstantReflection',
        'parameters' => 
        array (
          'typeWithConstant' => 
          array (
            'name' => 'typeWithConstant',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\Type\\Type',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 126,
            'endLine' => 126,
            'startColumn' => 40,
            'endColumn' => 61,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
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
            'startLine' => 126,
            'endLine' => 126,
            'startColumn' => 64,
            'endColumn' => 83,
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
                  'name' => 'PHPStan\\Reflection\\ClassConstantReflection',
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
        'startLine' => 126,
        'endLine' => 126,
        'startColumn' => 2,
        'endColumn' => 111,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Analyser',
        'declaringClassName' => 'PHPStan\\Analyser\\Scope',
        'implementingClassName' => 'PHPStan\\Analyser\\Scope',
        'currentClassName' => 'PHPStan\\Analyser\\Scope',
        'aliasName' => NULL,
      ),
      'getConstantExplicitTypeFromConfig' => 
      array (
        'name' => 'getConstantExplicitTypeFromConfig',
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
            'startLine' => 128,
            'endLine' => 128,
            'startColumn' => 52,
            'endColumn' => 71,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'constantType' => 
          array (
            'name' => 'constantType',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\Type\\Type',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 128,
            'endLine' => 128,
            'startColumn' => 74,
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
            'name' => 'PHPStan\\Type\\Type',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 128,
        'endLine' => 128,
        'startColumn' => 2,
        'endColumn' => 99,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Analyser',
        'declaringClassName' => 'PHPStan\\Analyser\\Scope',
        'implementingClassName' => 'PHPStan\\Analyser\\Scope',
        'currentClassName' => 'PHPStan\\Analyser\\Scope',
        'aliasName' => NULL,
      ),
      'getIterableKeyType' => 
      array (
        'name' => 'getIterableKeyType',
        'parameters' => 
        array (
          'iteratee' => 
          array (
            'name' => 'iteratee',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\Type\\Type',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 130,
            'endLine' => 130,
            'startColumn' => 37,
            'endColumn' => 50,
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
            'name' => 'PHPStan\\Type\\Type',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 130,
        'endLine' => 130,
        'startColumn' => 2,
        'endColumn' => 58,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Analyser',
        'declaringClassName' => 'PHPStan\\Analyser\\Scope',
        'implementingClassName' => 'PHPStan\\Analyser\\Scope',
        'currentClassName' => 'PHPStan\\Analyser\\Scope',
        'aliasName' => NULL,
      ),
      'getIterableValueType' => 
      array (
        'name' => 'getIterableValueType',
        'parameters' => 
        array (
          'iteratee' => 
          array (
            'name' => 'iteratee',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\Type\\Type',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 132,
            'endLine' => 132,
            'startColumn' => 39,
            'endColumn' => 52,
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
            'name' => 'PHPStan\\Type\\Type',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 132,
        'endLine' => 132,
        'startColumn' => 2,
        'endColumn' => 60,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Analyser',
        'declaringClassName' => 'PHPStan\\Analyser\\Scope',
        'implementingClassName' => 'PHPStan\\Analyser\\Scope',
        'currentClassName' => 'PHPStan\\Analyser\\Scope',
        'aliasName' => NULL,
      ),
      'isInAnonymousFunction' => 
      array (
        'name' => 'isInAnonymousFunction',
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
        'docComment' => '/**
 * @phpstan-assert-if-true !null $this->getAnonymousFunctionReflection()
 * @phpstan-assert-if-true !null $this->getAnonymousFunctionReturnType()
 */',
        'startLine' => 138,
        'endLine' => 138,
        'startColumn' => 2,
        'endColumn' => 47,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Analyser',
        'declaringClassName' => 'PHPStan\\Analyser\\Scope',
        'implementingClassName' => 'PHPStan\\Analyser\\Scope',
        'currentClassName' => 'PHPStan\\Analyser\\Scope',
        'aliasName' => NULL,
      ),
      'getAnonymousFunctionReflection' => 
      array (
        'name' => 'getAnonymousFunctionReflection',
        'parameters' => 
        array (
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
                  'name' => 'PHPStan\\Type\\ClosureType',
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
        'startLine' => 140,
        'endLine' => 140,
        'startColumn' => 2,
        'endColumn' => 64,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Analyser',
        'declaringClassName' => 'PHPStan\\Analyser\\Scope',
        'implementingClassName' => 'PHPStan\\Analyser\\Scope',
        'currentClassName' => 'PHPStan\\Analyser\\Scope',
        'aliasName' => NULL,
      ),
      'getAnonymousFunctionReturnType' => 
      array (
        'name' => 'getAnonymousFunctionReturnType',
        'parameters' => 
        array (
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
                  'name' => 'PHPStan\\Type\\Type',
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
        'startLine' => 142,
        'endLine' => 142,
        'startColumn' => 2,
        'endColumn' => 57,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Analyser',
        'declaringClassName' => 'PHPStan\\Analyser\\Scope',
        'implementingClassName' => 'PHPStan\\Analyser\\Scope',
        'currentClassName' => 'PHPStan\\Analyser\\Scope',
        'aliasName' => NULL,
      ),
      'getType' => 
      array (
        'name' => 'getType',
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
                'name' => 'PhpParser\\Node\\Expr',
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
            'startColumn' => 26,
            'endColumn' => 35,
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
            'name' => 'PHPStan\\Type\\Type',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Returns the PHPDoc-enhanced type. Use getNativeType() for native types only.
 */',
        'startLine' => 147,
        'endLine' => 147,
        'startColumn' => 2,
        'endColumn' => 43,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Analyser',
        'declaringClassName' => 'PHPStan\\Analyser\\Scope',
        'implementingClassName' => 'PHPStan\\Analyser\\Scope',
        'currentClassName' => 'PHPStan\\Analyser\\Scope',
        'aliasName' => NULL,
      ),
      'getNativeType' => 
      array (
        'name' => 'getNativeType',
        'parameters' => 
        array (
          'expr' => 
          array (
            'name' => 'expr',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PhpParser\\Node\\Expr',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 152,
            'endLine' => 152,
            'startColumn' => 32,
            'endColumn' => 41,
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
            'name' => 'PHPStan\\Type\\Type',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Returns only what PHP\'s native type system knows, ignoring PHPDoc.
 */',
        'startLine' => 152,
        'endLine' => 152,
        'startColumn' => 2,
        'endColumn' => 49,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Analyser',
        'declaringClassName' => 'PHPStan\\Analyser\\Scope',
        'implementingClassName' => 'PHPStan\\Analyser\\Scope',
        'currentClassName' => 'PHPStan\\Analyser\\Scope',
        'aliasName' => NULL,
      ),
      'getKeepVoidType' => 
      array (
        'name' => 'getKeepVoidType',
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
                'name' => 'PhpParser\\Node\\Expr',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 158,
            'endLine' => 158,
            'startColumn' => 34,
            'endColumn' => 43,
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
            'name' => 'PHPStan\\Type\\Type',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Like getType(), but preserves void for function/method calls
 * (normally getType() replaces void with null).
 */',
        'startLine' => 158,
        'endLine' => 158,
        'startColumn' => 2,
        'endColumn' => 51,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Analyser',
        'declaringClassName' => 'PHPStan\\Analyser\\Scope',
        'implementingClassName' => 'PHPStan\\Analyser\\Scope',
        'currentClassName' => 'PHPStan\\Analyser\\Scope',
        'aliasName' => NULL,
      ),
      'getScopeType' => 
      array (
        'name' => 'getScopeType',
        'parameters' => 
        array (
          'expr' => 
          array (
            'name' => 'expr',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PhpParser\\Node\\Expr',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 164,
            'endLine' => 164,
            'startColumn' => 31,
            'endColumn' => 40,
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
            'name' => 'PHPStan\\Type\\Type',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Unlike getType() which may defer evaluation, this uses the scope\'s
 * current state immediately.
 */',
        'startLine' => 164,
        'endLine' => 164,
        'startColumn' => 2,
        'endColumn' => 48,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Analyser',
        'declaringClassName' => 'PHPStan\\Analyser\\Scope',
        'implementingClassName' => 'PHPStan\\Analyser\\Scope',
        'currentClassName' => 'PHPStan\\Analyser\\Scope',
        'aliasName' => NULL,
      ),
      'getScopeNativeType' => 
      array (
        'name' => 'getScopeNativeType',
        'parameters' => 
        array (
          'expr' => 
          array (
            'name' => 'expr',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PhpParser\\Node\\Expr',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 166,
            'endLine' => 166,
            'startColumn' => 37,
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
            'name' => 'PHPStan\\Type\\Type',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 166,
        'endLine' => 166,
        'startColumn' => 2,
        'endColumn' => 54,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Analyser',
        'declaringClassName' => 'PHPStan\\Analyser\\Scope',
        'implementingClassName' => 'PHPStan\\Analyser\\Scope',
        'currentClassName' => 'PHPStan\\Analyser\\Scope',
        'aliasName' => NULL,
      ),
      'resolveName' => 
      array (
        'name' => 'resolveName',
        'parameters' => 
        array (
          'name' => 
          array (
            'name' => 'name',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PhpParser\\Node\\Name',
                'isIdentifier' => false,
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
            'startColumn' => 30,
            'endColumn' => 39,
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
        'docComment' => '/**
 * Resolves a Name AST node to a fully qualified class name string.
 *
 * Handles special names: `self` and `static` resolve to the current class,
 * `parent` resolves to the parent class. Other names are returned as-is
 * (they should already be fully qualified by the PHP parser\'s name resolver).
 *
 * Inside a Closure::bind() context, `self`/`static` resolve to the bound class.
 *
 * @return non-empty-string
 */',
        'startLine' => 179,
        'endLine' => 179,
        'startColumn' => 2,
        'endColumn' => 49,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Analyser',
        'declaringClassName' => 'PHPStan\\Analyser\\Scope',
        'implementingClassName' => 'PHPStan\\Analyser\\Scope',
        'currentClassName' => 'PHPStan\\Analyser\\Scope',
        'aliasName' => NULL,
      ),
      'resolveTypeByName' => 
      array (
        'name' => 'resolveTypeByName',
        'parameters' => 
        array (
          'name' => 
          array (
            'name' => 'name',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PhpParser\\Node\\Name',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 190,
            'endLine' => 190,
            'startColumn' => 36,
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
            'name' => 'PHPStan\\Type\\TypeWithClassName',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Resolves a Name AST node to a TypeWithClassName.
 *
 * Unlike resolveName() which returns a plain string, this returns a proper
 * Type object that preserves late-static-binding information:
 * - `static` returns a StaticType (preserves LSB in subclasses)
 * - `self` returns a ThisType when inside the same class hierarchy
 * - Other names return an ObjectType
 */',
        'startLine' => 190,
        'endLine' => 190,
        'startColumn' => 2,
        'endColumn' => 66,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Analyser',
        'declaringClassName' => 'PHPStan\\Analyser\\Scope',
        'implementingClassName' => 'PHPStan\\Analyser\\Scope',
        'currentClassName' => 'PHPStan\\Analyser\\Scope',
        'aliasName' => NULL,
      ),
      'getTypeFromValue' => 
      array (
        'name' => 'getTypeFromValue',
        'parameters' => 
        array (
          'value' => 
          array (
            'name' => 'value',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 201,
            'endLine' => 201,
            'startColumn' => 35,
            'endColumn' => 40,
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
            'name' => 'PHPStan\\Type\\Type',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Returns the PHPStan Type representing a given PHP value.
 *
 * Converts runtime PHP values to their corresponding constant types:
 * integers become ConstantIntegerType, strings become ConstantStringType,
 * arrays become ConstantArrayType (if small enough), etc.
 *
 * @param mixed $value
 */',
        'startLine' => 201,
        'endLine' => 201,
        'startColumn' => 2,
        'endColumn' => 48,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Analyser',
        'declaringClassName' => 'PHPStan\\Analyser\\Scope',
        'implementingClassName' => 'PHPStan\\Analyser\\Scope',
        'currentClassName' => 'PHPStan\\Analyser\\Scope',
        'aliasName' => NULL,
      ),
      'hasExpressionType' => 
      array (
        'name' => 'hasExpressionType',
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
                'name' => 'PhpParser\\Node\\Expr',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 213,
            'endLine' => 213,
            'startColumn' => 36,
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
            'name' => 'PHPStan\\TrinaryLogic',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Returns whether an expression has a tracked type in this scope.
 *
 * Returns TrinaryLogic::Yes if the expression\'s type is definitely known,
 * TrinaryLogic::Maybe if it might be known, and TrinaryLogic::No if there
 * is no type information for it.
 *
 * This checks the scope\'s expression type map without computing the type
 * (unlike getType() which always computes a type).
 */',
        'startLine' => 213,
        'endLine' => 213,
        'startColumn' => 2,
        'endColumn' => 61,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Analyser',
        'declaringClassName' => 'PHPStan\\Analyser\\Scope',
        'implementingClassName' => 'PHPStan\\Analyser\\Scope',
        'currentClassName' => 'PHPStan\\Analyser\\Scope',
        'aliasName' => NULL,
      ),
      'isInClassExists' => 
      array (
        'name' => 'isInClassExists',
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
            'startLine' => 222,
            'endLine' => 222,
            'startColumn' => 34,
            'endColumn' => 50,
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
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Returns whether the given class name is being checked inside a
 * class_exists(), interface_exists(), or trait_exists() call.
 *
 * When true, rules should suppress "class not found" errors because
 * the code is explicitly checking for the class\'s existence.
 */',
        'startLine' => 222,
        'endLine' => 222,
        'startColumn' => 2,
        'endColumn' => 58,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Analyser',
        'declaringClassName' => 'PHPStan\\Analyser\\Scope',
        'implementingClassName' => 'PHPStan\\Analyser\\Scope',
        'currentClassName' => 'PHPStan\\Analyser\\Scope',
        'aliasName' => NULL,
      ),
      'isInFunctionExists' => 
      array (
        'name' => 'isInFunctionExists',
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
            'startLine' => 231,
            'endLine' => 231,
            'startColumn' => 37,
            'endColumn' => 56,
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
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Returns whether the given function name is being checked inside a
 * function_exists() call.
 *
 * When true, rules should suppress "function not found" errors because
 * the code is explicitly checking for the function\'s existence.
 */',
        'startLine' => 231,
        'endLine' => 231,
        'startColumn' => 2,
        'endColumn' => 64,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Analyser',
        'declaringClassName' => 'PHPStan\\Analyser\\Scope',
        'implementingClassName' => 'PHPStan\\Analyser\\Scope',
        'currentClassName' => 'PHPStan\\Analyser\\Scope',
        'aliasName' => NULL,
      ),
      'isInClosureBind' => 
      array (
        'name' => 'isInClosureBind',
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
        'docComment' => '/**
 * Returns whether the current analysis context is inside a Closure::bind()
 * or Closure::bindTo() call.
 *
 * When true, the closure\'s $this and self/static may refer to a different
 * class than the one where the closure was defined.
 */',
        'startLine' => 240,
        'endLine' => 240,
        'startColumn' => 2,
        'endColumn' => 41,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Analyser',
        'declaringClassName' => 'PHPStan\\Analyser\\Scope',
        'implementingClassName' => 'PHPStan\\Analyser\\Scope',
        'currentClassName' => 'PHPStan\\Analyser\\Scope',
        'aliasName' => NULL,
      ),
      'getFunctionCallStack' => 
      array (
        'name' => 'getFunctionCallStack',
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
        'docComment' => '/**
 * Returns the stack of function/method calls that are currently being analysed.
 *
 * When analysing arguments of a function call, this returns the chain of
 * enclosing calls. Used by extensions that need to know the calling context,
 * such as type-specifying extensions for functions like class_exists().
 *
 * @return list<FunctionReflection|MethodReflection>
 */',
        'startLine' => 251,
        'endLine' => 251,
        'startColumn' => 2,
        'endColumn' => 47,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Analyser',
        'declaringClassName' => 'PHPStan\\Analyser\\Scope',
        'implementingClassName' => 'PHPStan\\Analyser\\Scope',
        'currentClassName' => 'PHPStan\\Analyser\\Scope',
        'aliasName' => NULL,
      ),
      'getFunctionCallStackWithParameters' => 
      array (
        'name' => 'getFunctionCallStackWithParameters',
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
        'docComment' => '/**
 * Like getFunctionCallStack(), but also includes the parameter being passed to.
 *
 * Each entry is a tuple of the function/method reflection and the parameter
 * reflection for the argument position being analysed (or null if unknown).
 *
 * @return list<array{FunctionReflection|MethodReflection, ParameterReflection|null}>
 */',
        'startLine' => 261,
        'endLine' => 261,
        'startColumn' => 2,
        'endColumn' => 61,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Analyser',
        'declaringClassName' => 'PHPStan\\Analyser\\Scope',
        'implementingClassName' => 'PHPStan\\Analyser\\Scope',
        'currentClassName' => 'PHPStan\\Analyser\\Scope',
        'aliasName' => NULL,
      ),
      'isParameterValueNullable' => 
      array (
        'name' => 'isParameterValueNullable',
        'parameters' => 
        array (
          'parameter' => 
          array (
            'name' => 'parameter',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PhpParser\\Node\\Param',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 269,
            'endLine' => 269,
            'startColumn' => 43,
            'endColumn' => 58,
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
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Returns whether a function parameter has a default value of null.
 *
 * Checks the parameter\'s default value AST node to determine if
 * `= null` was specified. Used by function definition checks.
 */',
        'startLine' => 269,
        'endLine' => 269,
        'startColumn' => 2,
        'endColumn' => 66,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Analyser',
        'declaringClassName' => 'PHPStan\\Analyser\\Scope',
        'implementingClassName' => 'PHPStan\\Analyser\\Scope',
        'currentClassName' => 'PHPStan\\Analyser\\Scope',
        'aliasName' => NULL,
      ),
      'getFunctionType' => 
      array (
        'name' => 'getFunctionType',
        'parameters' => 
        array (
          'type' => 
          array (
            'name' => 'type',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 280,
            'endLine' => 280,
            'startColumn' => 34,
            'endColumn' => 38,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'isNullable' => 
          array (
            'name' => 'isNullable',
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
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 280,
            'endLine' => 280,
            'startColumn' => 41,
            'endColumn' => 56,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'isVariadic' => 
          array (
            'name' => 'isVariadic',
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
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 280,
            'endLine' => 280,
            'startColumn' => 59,
            'endColumn' => 74,
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
            'name' => 'PHPStan\\Type\\Type',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Resolves a type AST node (from a parameter/return type declaration) to a Type.
 *
 * Handles named types, identifier types (int, string, etc.), union types,
 * intersection types, and nullable types. The $isNullable flag adds null
 * to the type, and $isVariadic wraps the type in an array.
 *
 * @param Node\\Name|Node\\Identifier|Node\\ComplexType|null $type
 */',
        'startLine' => 280,
        'endLine' => 280,
        'startColumn' => 2,
        'endColumn' => 82,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Analyser',
        'declaringClassName' => 'PHPStan\\Analyser\\Scope',
        'implementingClassName' => 'PHPStan\\Analyser\\Scope',
        'currentClassName' => 'PHPStan\\Analyser\\Scope',
        'aliasName' => NULL,
      ),
      'isInExpressionAssign' => 
      array (
        'name' => 'isInExpressionAssign',
        'parameters' => 
        array (
          'expr' => 
          array (
            'name' => 'expr',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PhpParser\\Node\\Expr',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 291,
            'endLine' => 291,
            'startColumn' => 39,
            'endColumn' => 48,
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
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Returns whether the given expression is currently being assigned to.
 *
 * Returns true during the analysis of the right-hand side of an assignment
 * to this expression. For example, when analysing `$a = expr`, this returns
 * true for the $a variable during the analysis of `expr`.
 *
 * Used to prevent infinite recursion when resolving types during assignment.
 */',
        'startLine' => 291,
        'endLine' => 291,
        'startColumn' => 2,
        'endColumn' => 56,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Analyser',
        'declaringClassName' => 'PHPStan\\Analyser\\Scope',
        'implementingClassName' => 'PHPStan\\Analyser\\Scope',
        'currentClassName' => 'PHPStan\\Analyser\\Scope',
        'aliasName' => NULL,
      ),
      'isUndefinedExpressionAllowed' => 
      array (
        'name' => 'isUndefinedExpressionAllowed',
        'parameters' => 
        array (
          'expr' => 
          array (
            'name' => 'expr',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PhpParser\\Node\\Expr',
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
            'startColumn' => 47,
            'endColumn' => 56,
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
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Returns whether accessing the given expression in an undefined state is allowed.
 *
 * Returns true when the expression is on the left-hand side of an assignment
 * or in similar contexts where it\'s valid for the expression to be undefined
 * (e.g. `$a[\'key\'] = value` where $a[\'key\'] doesn\'t need to exist yet).
 */',
        'startLine' => 300,
        'endLine' => 300,
        'startColumn' => 2,
        'endColumn' => 64,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Analyser',
        'declaringClassName' => 'PHPStan\\Analyser\\Scope',
        'implementingClassName' => 'PHPStan\\Analyser\\Scope',
        'currentClassName' => 'PHPStan\\Analyser\\Scope',
        'aliasName' => NULL,
      ),
      'filterByTruthyValue' => 
      array (
        'name' => 'filterByTruthyValue',
        'parameters' => 
        array (
          'expr' => 
          array (
            'name' => 'expr',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PhpParser\\Node\\Expr',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 311,
            'endLine' => 311,
            'startColumn' => 38,
            'endColumn' => 47,
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
        'docComment' => '/**
 * Returns a new Scope with types narrowed by assuming the expression is truthy.
 *
 * Given an expression like `$x instanceof Foo`, returns a scope where
 * $x is known to be of type Foo. This is the scope used inside the
 * if-branch of `if ($x instanceof Foo)`.
 *
 * Uses the TypeSpecifier internally to determine type narrowing.
 */',
        'startLine' => 311,
        'endLine' => 311,
        'startColumn' => 2,
        'endColumn' => 55,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Analyser',
        'declaringClassName' => 'PHPStan\\Analyser\\Scope',
        'implementingClassName' => 'PHPStan\\Analyser\\Scope',
        'currentClassName' => 'PHPStan\\Analyser\\Scope',
        'aliasName' => NULL,
      ),
      'filterByFalseyValue' => 
      array (
        'name' => 'filterByFalseyValue',
        'parameters' => 
        array (
          'expr' => 
          array (
            'name' => 'expr',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PhpParser\\Node\\Expr',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 320,
            'endLine' => 320,
            'startColumn' => 38,
            'endColumn' => 47,
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
        'docComment' => '/**
 * Returns a new Scope with types narrowed by assuming the expression is falsy.
 *
 * The opposite of filterByTruthyValue(). Given `$x instanceof Foo`, returns
 * a scope where $x is known NOT to be of type Foo. This is the scope used
 * in the else-branch of `if ($x instanceof Foo)`.
 */',
        'startLine' => 320,
        'endLine' => 320,
        'startColumn' => 2,
        'endColumn' => 55,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Analyser',
        'declaringClassName' => 'PHPStan\\Analyser\\Scope',
        'implementingClassName' => 'PHPStan\\Analyser\\Scope',
        'currentClassName' => 'PHPStan\\Analyser\\Scope',
        'aliasName' => NULL,
      ),
      'isInFirstLevelStatement' => 
      array (
        'name' => 'isInFirstLevelStatement',
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
        'docComment' => '/**
 * Returns whether the current statement is a "first-level" statement.
 *
 * A first-level statement is one that is directly inside a function/method
 * body, not nested inside control structures like if/else, loops, or
 * try/catch. Used to determine whether certain checks should be more
 * or less strict.
 */',
        'startLine' => 330,
        'endLine' => 330,
        'startColumn' => 2,
        'endColumn' => 49,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Analyser',
        'declaringClassName' => 'PHPStan\\Analyser\\Scope',
        'implementingClassName' => 'PHPStan\\Analyser\\Scope',
        'currentClassName' => 'PHPStan\\Analyser\\Scope',
        'aliasName' => NULL,
      ),
      'getPhpVersion' => 
      array (
        'name' => 'getPhpVersion',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPStan\\Php\\PhpVersions',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Returns the PHP version(s) being analysed against.
 *
 * Returns a PhpVersions object that can represent a range of PHP versions
 * (when the exact version is not known). Use its methods like
 * supportsEnums(), supportsReadonlyProperties(), etc. to check for
 * version-specific features.
 */',
        'startLine' => 340,
        'endLine' => 340,
        'startColumn' => 2,
        'endColumn' => 46,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Analyser',
        'declaringClassName' => 'PHPStan\\Analyser\\Scope',
        'implementingClassName' => 'PHPStan\\Analyser\\Scope',
        'currentClassName' => 'PHPStan\\Analyser\\Scope',
        'aliasName' => NULL,
      ),
      'toMutatingScope' => 
      array (
        'name' => 'toMutatingScope',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPStan\\Analyser\\MutatingScope',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/** @internal */',
        'startLine' => 343,
        'endLine' => 343,
        'startColumn' => 2,
        'endColumn' => 50,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Analyser',
        'declaringClassName' => 'PHPStan\\Analyser\\Scope',
        'implementingClassName' => 'PHPStan\\Analyser\\Scope',
        'currentClassName' => 'PHPStan\\Analyser\\Scope',
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