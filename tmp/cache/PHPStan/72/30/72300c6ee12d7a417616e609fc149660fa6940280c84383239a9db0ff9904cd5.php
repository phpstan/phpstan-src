<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/src/Reflection/ParametersAcceptor.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\Reflection\ParametersAcceptor
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-c4967ee25a6d5e420cc88af0effc7c39ea0505f0d7113e26640ce1409cedd350',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\Reflection\\ParametersAcceptor',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/src/Reflection/ParametersAcceptor.php',
      ),
    ),
    'namespace' => 'PHPStan\\Reflection',
    'name' => 'PHPStan\\Reflection\\ParametersAcceptor',
    'shortName' => 'ParametersAcceptor',
    'isInterface' => true,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Describes one signature variant of a function or method.
 *
 * A function/method may have multiple ParametersAcceptor variants — for example,
 * the built-in `strtok` function has different signatures depending on argument count.
 * Each variant describes the template type parameters, positional parameters, variadicity,
 * and return type.
 *
 * This is the base interface. ExtendedParametersAcceptor adds separate PHPDoc/native
 * return types and extended parameter reflection. CallableParametersAcceptor adds
 * throw points, impure points, and purity information.
 *
 * Use ParametersAcceptorSelector to choose the best variant for a given call site.
 *
 * @api
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 24,
    'endLine' => 48,
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
      'VARIADIC_FUNCTIONS' => 
      array (
        'declaringClassName' => 'PHPStan\\Reflection\\ParametersAcceptor',
        'implementingClassName' => 'PHPStan\\Reflection\\ParametersAcceptor',
        'name' => 'VARIADIC_FUNCTIONS',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'func_get_args\', \'func_get_arg\', \'func_num_args\']',
          'attributes' => 
          array (
            'startLine' => 27,
            'endLine' => 31,
            'startTokenPos' => 42,
            'startFilePos' => 879,
            'endTokenPos' => 53,
            'endFilePos' => 938,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 27,
        'endLine' => 31,
        'startColumn' => 2,
        'endColumn' => 3,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'getTemplateTypeMap' => 
      array (
        'name' => 'getTemplateTypeMap',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPStan\\Type\\Generic\\TemplateTypeMap',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 33,
        'endLine' => 33,
        'startColumn' => 2,
        'endColumn' => 55,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection',
        'declaringClassName' => 'PHPStan\\Reflection\\ParametersAcceptor',
        'implementingClassName' => 'PHPStan\\Reflection\\ParametersAcceptor',
        'currentClassName' => 'PHPStan\\Reflection\\ParametersAcceptor',
        'aliasName' => NULL,
      ),
      'getResolvedTemplateTypeMap' => 
      array (
        'name' => 'getResolvedTemplateTypeMap',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPStan\\Type\\Generic\\TemplateTypeMap',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * After template type inference at a call site, this map contains the
 * concrete types inferred for each template parameter.
 */',
        'startLine' => 39,
        'endLine' => 39,
        'startColumn' => 2,
        'endColumn' => 63,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection',
        'declaringClassName' => 'PHPStan\\Reflection\\ParametersAcceptor',
        'implementingClassName' => 'PHPStan\\Reflection\\ParametersAcceptor',
        'currentClassName' => 'PHPStan\\Reflection\\ParametersAcceptor',
        'aliasName' => NULL,
      ),
      'getParameters' => 
      array (
        'name' => 'getParameters',
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
        'docComment' => '/** @return list<ParameterReflection> */',
        'startLine' => 42,
        'endLine' => 42,
        'startColumn' => 2,
        'endColumn' => 40,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection',
        'declaringClassName' => 'PHPStan\\Reflection\\ParametersAcceptor',
        'implementingClassName' => 'PHPStan\\Reflection\\ParametersAcceptor',
        'currentClassName' => 'PHPStan\\Reflection\\ParametersAcceptor',
        'aliasName' => NULL,
      ),
      'isVariadic' => 
      array (
        'name' => 'isVariadic',
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
        'startLine' => 44,
        'endLine' => 44,
        'startColumn' => 2,
        'endColumn' => 36,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection',
        'declaringClassName' => 'PHPStan\\Reflection\\ParametersAcceptor',
        'implementingClassName' => 'PHPStan\\Reflection\\ParametersAcceptor',
        'currentClassName' => 'PHPStan\\Reflection\\ParametersAcceptor',
        'aliasName' => NULL,
      ),
      'getReturnType' => 
      array (
        'name' => 'getReturnType',
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
        'startLine' => 46,
        'endLine' => 46,
        'startColumn' => 2,
        'endColumn' => 39,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Reflection',
        'declaringClassName' => 'PHPStan\\Reflection\\ParametersAcceptor',
        'implementingClassName' => 'PHPStan\\Reflection\\ParametersAcceptor',
        'currentClassName' => 'PHPStan\\Reflection\\ParametersAcceptor',
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