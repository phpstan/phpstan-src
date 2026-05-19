<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/src/Analyser/TypeSpecifierContext.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\Analyser\TypeSpecifierContext
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-5c1a489a669bae67d19ba574db0170fa1e6c284b20fec66976a8455499fc2208',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\Analyser\\TypeSpecifierContext',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/src/Analyser/TypeSpecifierContext.php',
      ),
    ),
    'namespace' => 'PHPStan\\Analyser',
    'name' => 'PHPStan\\Analyser\\TypeSpecifierContext',
    'shortName' => 'TypeSpecifierContext',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * @api
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 10,
    'endLine' => 93,
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
      'CONTEXT_TRUE' => 
      array (
        'declaringClassName' => 'PHPStan\\Analyser\\TypeSpecifierContext',
        'implementingClassName' => 'PHPStan\\Analyser\\TypeSpecifierContext',
        'name' => 'CONTEXT_TRUE',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '0b1',
          'attributes' => 
          array (
            'startLine' => 13,
            'endLine' => 13,
            'startTokenPos' => 39,
            'startFilePos' => 183,
            'endTokenPos' => 39,
            'endFilePos' => 188,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 13,
        'endLine' => 13,
        'startColumn' => 2,
        'endColumn' => 36,
      ),
      'CONTEXT_TRUTHY_BUT_NOT_TRUE' => 
      array (
        'declaringClassName' => 'PHPStan\\Analyser\\TypeSpecifierContext',
        'implementingClassName' => 'PHPStan\\Analyser\\TypeSpecifierContext',
        'name' => 'CONTEXT_TRUTHY_BUT_NOT_TRUE',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '0b10',
          'attributes' => 
          array (
            'startLine' => 14,
            'endLine' => 14,
            'startTokenPos' => 50,
            'startFilePos' => 235,
            'endTokenPos' => 50,
            'endFilePos' => 240,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 14,
        'endLine' => 14,
        'startColumn' => 2,
        'endColumn' => 51,
      ),
      'CONTEXT_TRUTHY' => 
      array (
        'declaringClassName' => 'PHPStan\\Analyser\\TypeSpecifierContext',
        'implementingClassName' => 'PHPStan\\Analyser\\TypeSpecifierContext',
        'name' => 'CONTEXT_TRUTHY',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => 'self::CONTEXT_TRUE | self::CONTEXT_TRUTHY_BUT_NOT_TRUE',
          'attributes' => 
          array (
            'startLine' => 15,
            'endLine' => 15,
            'startTokenPos' => 61,
            'startFilePos' => 274,
            'endTokenPos' => 69,
            'endFilePos' => 327,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 15,
        'endLine' => 15,
        'startColumn' => 2,
        'endColumn' => 86,
      ),
      'CONTEXT_FALSE' => 
      array (
        'declaringClassName' => 'PHPStan\\Analyser\\TypeSpecifierContext',
        'implementingClassName' => 'PHPStan\\Analyser\\TypeSpecifierContext',
        'name' => 'CONTEXT_FALSE',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '0b100',
          'attributes' => 
          array (
            'startLine' => 16,
            'endLine' => 16,
            'startTokenPos' => 80,
            'startFilePos' => 360,
            'endTokenPos' => 80,
            'endFilePos' => 365,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 16,
        'endLine' => 16,
        'startColumn' => 2,
        'endColumn' => 37,
      ),
      'CONTEXT_FALSEY_BUT_NOT_FALSE' => 
      array (
        'declaringClassName' => 'PHPStan\\Analyser\\TypeSpecifierContext',
        'implementingClassName' => 'PHPStan\\Analyser\\TypeSpecifierContext',
        'name' => 'CONTEXT_FALSEY_BUT_NOT_FALSE',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '0b1000',
          'attributes' => 
          array (
            'startLine' => 17,
            'endLine' => 17,
            'startTokenPos' => 91,
            'startFilePos' => 413,
            'endTokenPos' => 91,
            'endFilePos' => 418,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 17,
        'endLine' => 17,
        'startColumn' => 2,
        'endColumn' => 52,
      ),
      'CONTEXT_FALSEY' => 
      array (
        'declaringClassName' => 'PHPStan\\Analyser\\TypeSpecifierContext',
        'implementingClassName' => 'PHPStan\\Analyser\\TypeSpecifierContext',
        'name' => 'CONTEXT_FALSEY',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => 'self::CONTEXT_FALSE | self::CONTEXT_FALSEY_BUT_NOT_FALSE',
          'attributes' => 
          array (
            'startLine' => 18,
            'endLine' => 18,
            'startTokenPos' => 102,
            'startFilePos' => 452,
            'endTokenPos' => 110,
            'endFilePos' => 507,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 18,
        'endLine' => 18,
        'startColumn' => 2,
        'endColumn' => 88,
      ),
      'CONTEXT_BITMASK' => 
      array (
        'declaringClassName' => 'PHPStan\\Analyser\\TypeSpecifierContext',
        'implementingClassName' => 'PHPStan\\Analyser\\TypeSpecifierContext',
        'name' => 'CONTEXT_BITMASK',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '0b1111',
          'attributes' => 
          array (
            'startLine' => 19,
            'endLine' => 19,
            'startTokenPos' => 121,
            'startFilePos' => 542,
            'endTokenPos' => 121,
            'endFilePos' => 547,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 19,
        'endLine' => 19,
        'startColumn' => 2,
        'endColumn' => 39,
      ),
    ),
    'immediateProperties' => 
    array (
      'registry' => 
      array (
        'declaringClassName' => 'PHPStan\\Analyser\\TypeSpecifierContext',
        'implementingClassName' => 'PHPStan\\Analyser\\TypeSpecifierContext',
        'name' => 'registry',
        'modifiers' => 20,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'default' => NULL,
        'docComment' => '/** @var self[] */',
        'attributes' => 
        array (
        ),
        'startLine' => 22,
        'endLine' => 22,
        'startColumn' => 2,
        'endColumn' => 32,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'value' => 
      array (
        'declaringClassName' => 'PHPStan\\Analyser\\TypeSpecifierContext',
        'implementingClassName' => 'PHPStan\\Analyser\\TypeSpecifierContext',
        'name' => 'value',
        'modifiers' => 4,
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
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 24,
        'endLine' => 24,
        'startColumn' => 31,
        'endColumn' => 49,
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
          'value' => 
          array (
            'name' => 'value',
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
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 24,
            'endLine' => 24,
            'startColumn' => 31,
            'endColumn' => 49,
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
        'startLine' => 24,
        'endLine' => 26,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'PHPStan\\Analyser',
        'declaringClassName' => 'PHPStan\\Analyser\\TypeSpecifierContext',
        'implementingClassName' => 'PHPStan\\Analyser\\TypeSpecifierContext',
        'currentClassName' => 'PHPStan\\Analyser\\TypeSpecifierContext',
        'aliasName' => NULL,
      ),
      'create' => 
      array (
        'name' => 'create',
        'parameters' => 
        array (
          'value' => 
          array (
            'name' => 'value',
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
            'startLine' => 28,
            'endLine' => 28,
            'startColumn' => 33,
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
            'name' => 'self',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 28,
        'endLine' => 33,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 20,
        'namespace' => 'PHPStan\\Analyser',
        'declaringClassName' => 'PHPStan\\Analyser\\TypeSpecifierContext',
        'implementingClassName' => 'PHPStan\\Analyser\\TypeSpecifierContext',
        'currentClassName' => 'PHPStan\\Analyser\\TypeSpecifierContext',
        'aliasName' => NULL,
      ),
      'createTrue' => 
      array (
        'name' => 'createTrue',
        'parameters' => 
        array (
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
        'startLine' => 35,
        'endLine' => 38,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'PHPStan\\Analyser',
        'declaringClassName' => 'PHPStan\\Analyser\\TypeSpecifierContext',
        'implementingClassName' => 'PHPStan\\Analyser\\TypeSpecifierContext',
        'currentClassName' => 'PHPStan\\Analyser\\TypeSpecifierContext',
        'aliasName' => NULL,
      ),
      'createTruthy' => 
      array (
        'name' => 'createTruthy',
        'parameters' => 
        array (
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
        'startLine' => 40,
        'endLine' => 43,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'PHPStan\\Analyser',
        'declaringClassName' => 'PHPStan\\Analyser\\TypeSpecifierContext',
        'implementingClassName' => 'PHPStan\\Analyser\\TypeSpecifierContext',
        'currentClassName' => 'PHPStan\\Analyser\\TypeSpecifierContext',
        'aliasName' => NULL,
      ),
      'createFalse' => 
      array (
        'name' => 'createFalse',
        'parameters' => 
        array (
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
        'startLine' => 45,
        'endLine' => 48,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'PHPStan\\Analyser',
        'declaringClassName' => 'PHPStan\\Analyser\\TypeSpecifierContext',
        'implementingClassName' => 'PHPStan\\Analyser\\TypeSpecifierContext',
        'currentClassName' => 'PHPStan\\Analyser\\TypeSpecifierContext',
        'aliasName' => NULL,
      ),
      'createFalsey' => 
      array (
        'name' => 'createFalsey',
        'parameters' => 
        array (
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
        'startLine' => 50,
        'endLine' => 53,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'PHPStan\\Analyser',
        'declaringClassName' => 'PHPStan\\Analyser\\TypeSpecifierContext',
        'implementingClassName' => 'PHPStan\\Analyser\\TypeSpecifierContext',
        'currentClassName' => 'PHPStan\\Analyser\\TypeSpecifierContext',
        'aliasName' => NULL,
      ),
      'createNull' => 
      array (
        'name' => 'createNull',
        'parameters' => 
        array (
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
        'startLine' => 55,
        'endLine' => 58,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'PHPStan\\Analyser',
        'declaringClassName' => 'PHPStan\\Analyser\\TypeSpecifierContext',
        'implementingClassName' => 'PHPStan\\Analyser\\TypeSpecifierContext',
        'currentClassName' => 'PHPStan\\Analyser\\TypeSpecifierContext',
        'aliasName' => NULL,
      ),
      'negate' => 
      array (
        'name' => 'negate',
        'parameters' => 
        array (
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
        'startLine' => 60,
        'endLine' => 66,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Analyser',
        'declaringClassName' => 'PHPStan\\Analyser\\TypeSpecifierContext',
        'implementingClassName' => 'PHPStan\\Analyser\\TypeSpecifierContext',
        'currentClassName' => 'PHPStan\\Analyser\\TypeSpecifierContext',
        'aliasName' => NULL,
      ),
      'true' => 
      array (
        'name' => 'true',
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
        'startLine' => 68,
        'endLine' => 71,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Analyser',
        'declaringClassName' => 'PHPStan\\Analyser\\TypeSpecifierContext',
        'implementingClassName' => 'PHPStan\\Analyser\\TypeSpecifierContext',
        'currentClassName' => 'PHPStan\\Analyser\\TypeSpecifierContext',
        'aliasName' => NULL,
      ),
      'truthy' => 
      array (
        'name' => 'truthy',
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
        'startLine' => 73,
        'endLine' => 76,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Analyser',
        'declaringClassName' => 'PHPStan\\Analyser\\TypeSpecifierContext',
        'implementingClassName' => 'PHPStan\\Analyser\\TypeSpecifierContext',
        'currentClassName' => 'PHPStan\\Analyser\\TypeSpecifierContext',
        'aliasName' => NULL,
      ),
      'false' => 
      array (
        'name' => 'false',
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
        'startLine' => 78,
        'endLine' => 81,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Analyser',
        'declaringClassName' => 'PHPStan\\Analyser\\TypeSpecifierContext',
        'implementingClassName' => 'PHPStan\\Analyser\\TypeSpecifierContext',
        'currentClassName' => 'PHPStan\\Analyser\\TypeSpecifierContext',
        'aliasName' => NULL,
      ),
      'falsey' => 
      array (
        'name' => 'falsey',
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
        'startLine' => 83,
        'endLine' => 86,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Analyser',
        'declaringClassName' => 'PHPStan\\Analyser\\TypeSpecifierContext',
        'implementingClassName' => 'PHPStan\\Analyser\\TypeSpecifierContext',
        'currentClassName' => 'PHPStan\\Analyser\\TypeSpecifierContext',
        'aliasName' => NULL,
      ),
      'null' => 
      array (
        'name' => 'null',
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
        'startLine' => 88,
        'endLine' => 91,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Analyser',
        'declaringClassName' => 'PHPStan\\Analyser\\TypeSpecifierContext',
        'implementingClassName' => 'PHPStan\\Analyser\\TypeSpecifierContext',
        'currentClassName' => 'PHPStan\\Analyser\\TypeSpecifierContext',
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