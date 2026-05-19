<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/src/Analyser/Traverser/ConstructorClassTemplateTraverser.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\Analyser\Traverser\ConstructorClassTemplateTraverser
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-2ab59a1cdb4a8141b9dbe65dac0ea752611157030f25fae4b35bf4094283fee4',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\Analyser\\Traverser\\ConstructorClassTemplateTraverser',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/src/Analyser/Traverser/ConstructorClassTemplateTraverser.php',
      ),
    ),
    'namespace' => 'PHPStan\\Analyser\\Traverser',
    'name' => 'PHPStan\\Analyser\\Traverser\\ConstructorClassTemplateTraverser',
    'shortName' => 'ConstructorClassTemplateTraverser',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => NULL,
    'attributes' => 
    array (
    ),
    'startLine' => 10,
    'endLine' => 46,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
      0 => 'PHPStan\\Type\\TypeTraverserCallable',
    ),
    'traitClassNames' => 
    array (
    ),
    'immediateConstants' => 
    array (
    ),
    'immediateProperties' => 
    array (
      'classTemplateTypes' => 
      array (
        'declaringClassName' => 'PHPStan\\Analyser\\Traverser\\ConstructorClassTemplateTraverser',
        'implementingClassName' => 'PHPStan\\Analyser\\Traverser\\ConstructorClassTemplateTraverser',
        'name' => 'classTemplateTypes',
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
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 17,
        'endLine' => 17,
        'startColumn' => 3,
        'endColumn' => 35,
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
          'classTemplateTypes' => 
          array (
            'name' => 'classTemplateTypes',
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
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 17,
            'endLine' => 17,
            'startColumn' => 3,
            'endColumn' => 35,
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
 * @param array<string, Type> $classTemplateTypes
 */',
        'startLine' => 16,
        'endLine' => 20,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Analyser\\Traverser',
        'declaringClassName' => 'PHPStan\\Analyser\\Traverser\\ConstructorClassTemplateTraverser',
        'implementingClassName' => 'PHPStan\\Analyser\\Traverser\\ConstructorClassTemplateTraverser',
        'currentClassName' => 'PHPStan\\Analyser\\Traverser\\ConstructorClassTemplateTraverser',
        'aliasName' => NULL,
      ),
      'traverse' => 
      array (
        'name' => 'traverse',
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
            'startLine' => 25,
            'endLine' => 25,
            'startColumn' => 27,
            'endColumn' => 36,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'traverse' => 
          array (
            'name' => 'traverse',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'callable',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 25,
            'endLine' => 25,
            'startColumn' => 39,
            'endColumn' => 56,
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
        'docComment' => '/**
 * @param callable(Type): Type $traverse
 */',
        'startLine' => 25,
        'endLine' => 36,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Analyser\\Traverser',
        'declaringClassName' => 'PHPStan\\Analyser\\Traverser\\ConstructorClassTemplateTraverser',
        'implementingClassName' => 'PHPStan\\Analyser\\Traverser\\ConstructorClassTemplateTraverser',
        'currentClassName' => 'PHPStan\\Analyser\\Traverser\\ConstructorClassTemplateTraverser',
        'aliasName' => NULL,
      ),
      'getClassTemplateTypes' => 
      array (
        'name' => 'getClassTemplateTypes',
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
 * @return array<string, Type>
 */',
        'startLine' => 41,
        'endLine' => 44,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Analyser\\Traverser',
        'declaringClassName' => 'PHPStan\\Analyser\\Traverser\\ConstructorClassTemplateTraverser',
        'implementingClassName' => 'PHPStan\\Analyser\\Traverser\\ConstructorClassTemplateTraverser',
        'currentClassName' => 'PHPStan\\Analyser\\Traverser\\ConstructorClassTemplateTraverser',
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