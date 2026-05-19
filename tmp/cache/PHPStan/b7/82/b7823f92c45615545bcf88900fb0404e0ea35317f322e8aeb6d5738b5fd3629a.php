<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/src/Reflection/ExtendedPropertyReflection.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\Reflection\ExtendedPropertyReflection
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-7da3b56308e657ab26237d6304923b334e6ad35cb890cabc13da35aa46406f93',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\Reflection\\ExtendedPropertyReflection',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/src/Reflection/ExtendedPropertyReflection.php',
      ),
    ),
    'namespace' => 'PHPStan\\Reflection',
    'name' => 'PHPStan\\Reflection\\ExtendedPropertyReflection',
    'shortName' => 'ExtendedPropertyReflection',
    'isInterface' => true,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Extended property reflection with additional metadata beyond PropertyReflection.
 *
 * This interface exists to allow PHPStan to add new property query methods in minor
 * versions without breaking existing PropertiesClassReflectionExtension implementations.
 * Extension developers should implement PropertyReflection, not this interface — PHPStan
 * wraps PropertyReflection implementations to provide ExtendedPropertyReflection.
 *
 * Provides access to:
 * - Separate PHPDoc type vs native type (for resolving the effective type)
 * - Property hooks (PHP 8.4+) — get/set hooks with their own method reflections
 * - Asymmetric visibility (PHP 8.4+) — different read/write visibility
 * - Abstract/final/virtual modifiers
 * - PHP attributes
 *
 * This is the return type of Type::getProperty(), Type::getInstanceProperty(),
 * and Type::getStaticProperty().
 *
 * @api
 * @api-do-not-implement
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 29,
    'endLine' => 82,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
      0 => 'PHPStan\\Reflection\\PropertyReflection',
    ),
    'traitClassNames' => 
    array (
    ),
    'immediateConstants' => 
    array (
      'HOOK_GET' => 
      array (
        'declaringClassName' => 'PHPStan\\Reflection\\ExtendedPropertyReflection',
        'implementingClassName' => 'PHPStan\\Reflection\\ExtendedPropertyReflection',
        'name' => 'HOOK_GET',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'get\'',
          'attributes' => 
          array (
            'startLine' => 32,
            'endLine' => 32,
            'startTokenPos' => 46,
            'startFilePos' => 1122,
            'endTokenPos' => 46,
            'endFilePos' => 1126,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 32,
        'endLine' => 32,
        'startColumn' => 2,
        'endColumn' => 31,
      ),
      'HOOK_SET' => 
      array (
        'declaringClassName' => 'PHPStan\\Reflection\\ExtendedPropertyReflection',
        'implementingClassName' => 'PHPStan\\Reflection\\ExtendedPropertyReflection',
        'name' => 'HOOK_SET',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'set\'',
          'attributes' => 
          array (
            'startLine' => 34,
            'endLine' => 34,
            'startTokenPos' => 57,
            'startFilePos' => 1155,
            'endTokenPos' => 57,
            'endFilePos' => 1159,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 34,
        'endLine' => 34,
        'startColumn' => 2,
        'endColumn' => 31,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'getName' => 
      array (
        'name' => 'getName',
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
        'startLine' => 36,
        'endLine' => 36,
        'startColumn' => 2,
        'endColumn' => 35,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection',
        'declaringClassName' => 'PHPStan\\Reflection\\ExtendedPropertyReflection',
        'implementingClassName' => 'PHPStan\\Reflection\\ExtendedPropertyReflection',
        'currentClassName' => 'PHPStan\\Reflection\\ExtendedPropertyReflection',
        'aliasName' => NULL,
      ),
      'hasPhpDocType' => 
      array (
        'name' => 'hasPhpDocType',
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
        'startLine' => 38,
        'endLine' => 38,
        'startColumn' => 2,
        'endColumn' => 39,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection',
        'declaringClassName' => 'PHPStan\\Reflection\\ExtendedPropertyReflection',
        'implementingClassName' => 'PHPStan\\Reflection\\ExtendedPropertyReflection',
        'currentClassName' => 'PHPStan\\Reflection\\ExtendedPropertyReflection',
        'aliasName' => NULL,
      ),
      'getPhpDocType' => 
      array (
        'name' => 'getPhpDocType',
        'parameters' => 
        array (
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
        'startLine' => 40,
        'endLine' => 40,
        'startColumn' => 2,
        'endColumn' => 39,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection',
        'declaringClassName' => 'PHPStan\\Reflection\\ExtendedPropertyReflection',
        'implementingClassName' => 'PHPStan\\Reflection\\ExtendedPropertyReflection',
        'currentClassName' => 'PHPStan\\Reflection\\ExtendedPropertyReflection',
        'aliasName' => NULL,
      ),
      'hasNativeType' => 
      array (
        'name' => 'hasNativeType',
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
        'startLine' => 42,
        'endLine' => 42,
        'startColumn' => 2,
        'endColumn' => 39,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection',
        'declaringClassName' => 'PHPStan\\Reflection\\ExtendedPropertyReflection',
        'implementingClassName' => 'PHPStan\\Reflection\\ExtendedPropertyReflection',
        'currentClassName' => 'PHPStan\\Reflection\\ExtendedPropertyReflection',
        'aliasName' => NULL,
      ),
      'getNativeType' => 
      array (
        'name' => 'getNativeType',
        'parameters' => 
        array (
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
        'startLine' => 44,
        'endLine' => 44,
        'startColumn' => 2,
        'endColumn' => 39,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection',
        'declaringClassName' => 'PHPStan\\Reflection\\ExtendedPropertyReflection',
        'implementingClassName' => 'PHPStan\\Reflection\\ExtendedPropertyReflection',
        'currentClassName' => 'PHPStan\\Reflection\\ExtendedPropertyReflection',
        'aliasName' => NULL,
      ),
      'isAbstract' => 
      array (
        'name' => 'isAbstract',
        'parameters' => 
        array (
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
        'startLine' => 46,
        'endLine' => 46,
        'startColumn' => 2,
        'endColumn' => 44,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection',
        'declaringClassName' => 'PHPStan\\Reflection\\ExtendedPropertyReflection',
        'implementingClassName' => 'PHPStan\\Reflection\\ExtendedPropertyReflection',
        'currentClassName' => 'PHPStan\\Reflection\\ExtendedPropertyReflection',
        'aliasName' => NULL,
      ),
      'isFinalByKeyword' => 
      array (
        'name' => 'isFinalByKeyword',
        'parameters' => 
        array (
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
        'startLine' => 48,
        'endLine' => 48,
        'startColumn' => 2,
        'endColumn' => 50,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection',
        'declaringClassName' => 'PHPStan\\Reflection\\ExtendedPropertyReflection',
        'implementingClassName' => 'PHPStan\\Reflection\\ExtendedPropertyReflection',
        'currentClassName' => 'PHPStan\\Reflection\\ExtendedPropertyReflection',
        'aliasName' => NULL,
      ),
      'isFinal' => 
      array (
        'name' => 'isFinal',
        'parameters' => 
        array (
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
        'startLine' => 50,
        'endLine' => 50,
        'startColumn' => 2,
        'endColumn' => 41,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection',
        'declaringClassName' => 'PHPStan\\Reflection\\ExtendedPropertyReflection',
        'implementingClassName' => 'PHPStan\\Reflection\\ExtendedPropertyReflection',
        'currentClassName' => 'PHPStan\\Reflection\\ExtendedPropertyReflection',
        'aliasName' => NULL,
      ),
      'isVirtual' => 
      array (
        'name' => 'isVirtual',
        'parameters' => 
        array (
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
 * Virtual properties (PHP 8.4+) exist only through their get/set hooks
 * and don\'t occupy memory in the object.
 */',
        'startLine' => 56,
        'endLine' => 56,
        'startColumn' => 2,
        'endColumn' => 43,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection',
        'declaringClassName' => 'PHPStan\\Reflection\\ExtendedPropertyReflection',
        'implementingClassName' => 'PHPStan\\Reflection\\ExtendedPropertyReflection',
        'currentClassName' => 'PHPStan\\Reflection\\ExtendedPropertyReflection',
        'aliasName' => NULL,
      ),
      'hasHook' => 
      array (
        'name' => 'hasHook',
        'parameters' => 
        array (
          'hookType' => 
          array (
            'name' => 'hookType',
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
            'startLine' => 59,
            'endLine' => 59,
            'startColumn' => 26,
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
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/** @param self::HOOK_* $hookType */',
        'startLine' => 59,
        'endLine' => 59,
        'startColumn' => 2,
        'endColumn' => 49,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection',
        'declaringClassName' => 'PHPStan\\Reflection\\ExtendedPropertyReflection',
        'implementingClassName' => 'PHPStan\\Reflection\\ExtendedPropertyReflection',
        'currentClassName' => 'PHPStan\\Reflection\\ExtendedPropertyReflection',
        'aliasName' => NULL,
      ),
      'getHook' => 
      array (
        'name' => 'getHook',
        'parameters' => 
        array (
          'hookType' => 
          array (
            'name' => 'hookType',
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
            'startLine' => 66,
            'endLine' => 66,
            'startColumn' => 26,
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
            'name' => 'PHPStan\\Reflection\\ExtendedMethodReflection',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Property hooks (PHP 8.4+) are internally represented as methods.
 *
 * @param self::HOOK_* $hookType
 */',
        'startLine' => 66,
        'endLine' => 66,
        'startColumn' => 2,
        'endColumn' => 69,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection',
        'declaringClassName' => 'PHPStan\\Reflection\\ExtendedPropertyReflection',
        'implementingClassName' => 'PHPStan\\Reflection\\ExtendedPropertyReflection',
        'currentClassName' => 'PHPStan\\Reflection\\ExtendedPropertyReflection',
        'aliasName' => NULL,
      ),
      'isProtectedSet' => 
      array (
        'name' => 'isProtectedSet',
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
        'endLine' => 68,
        'startColumn' => 2,
        'endColumn' => 40,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection',
        'declaringClassName' => 'PHPStan\\Reflection\\ExtendedPropertyReflection',
        'implementingClassName' => 'PHPStan\\Reflection\\ExtendedPropertyReflection',
        'currentClassName' => 'PHPStan\\Reflection\\ExtendedPropertyReflection',
        'aliasName' => NULL,
      ),
      'isPrivateSet' => 
      array (
        'name' => 'isPrivateSet',
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
        'startLine' => 70,
        'endLine' => 70,
        'startColumn' => 2,
        'endColumn' => 38,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection',
        'declaringClassName' => 'PHPStan\\Reflection\\ExtendedPropertyReflection',
        'implementingClassName' => 'PHPStan\\Reflection\\ExtendedPropertyReflection',
        'currentClassName' => 'PHPStan\\Reflection\\ExtendedPropertyReflection',
        'aliasName' => NULL,
      ),
      'getAttributes' => 
      array (
        'name' => 'getAttributes',
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
        'docComment' => '/** @return list<AttributeReflection> */',
        'startLine' => 73,
        'endLine' => 73,
        'startColumn' => 2,
        'endColumn' => 40,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection',
        'declaringClassName' => 'PHPStan\\Reflection\\ExtendedPropertyReflection',
        'implementingClassName' => 'PHPStan\\Reflection\\ExtendedPropertyReflection',
        'currentClassName' => 'PHPStan\\Reflection\\ExtendedPropertyReflection',
        'aliasName' => NULL,
      ),
      'isDummy' => 
      array (
        'name' => 'isDummy',
        'parameters' => 
        array (
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
 * Returns yes() for properties that represent possibly-defined properties
 * on non-final classes, mixed, object, etc. — placeholders PHPStan creates
 * when it cannot prove a property doesn\'t exist.
 */',
        'startLine' => 80,
        'endLine' => 80,
        'startColumn' => 2,
        'endColumn' => 41,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection',
        'declaringClassName' => 'PHPStan\\Reflection\\ExtendedPropertyReflection',
        'implementingClassName' => 'PHPStan\\Reflection\\ExtendedPropertyReflection',
        'currentClassName' => 'PHPStan\\Reflection\\ExtendedPropertyReflection',
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