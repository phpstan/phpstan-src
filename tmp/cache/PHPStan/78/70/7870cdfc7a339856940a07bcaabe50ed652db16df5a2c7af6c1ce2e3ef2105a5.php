<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/src/Analyser/Traverser/GenericTypeTemplateTraverser.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\Analyser\Traverser\GenericTypeTemplateTraverser
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-147835c19ff6a1fb492741f3718376fc02345a32d86e3badc2397acb4e45cbdd',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\Analyser\\Traverser\\GenericTypeTemplateTraverser',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/src/Analyser/Traverser/GenericTypeTemplateTraverser.php',
      ),
    ),
    'namespace' => 'PHPStan\\Analyser\\Traverser',
    'name' => 'PHPStan\\Analyser\\Traverser\\GenericTypeTemplateTraverser',
    'shortName' => 'GenericTypeTemplateTraverser',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => NULL,
    'attributes' => 
    array (
    ),
    'startLine' => 12,
    'endLine' => 38,
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
      'resolvedTemplateTypeMap' => 
      array (
        'declaringClassName' => 'PHPStan\\Analyser\\Traverser\\GenericTypeTemplateTraverser',
        'implementingClassName' => 'PHPStan\\Analyser\\Traverser\\GenericTypeTemplateTraverser',
        'name' => 'resolvedTemplateTypeMap',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'PHPStan\\Type\\Generic\\TemplateTypeMap',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 16,
        'endLine' => 16,
        'startColumn' => 3,
        'endColumn' => 59,
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
          'resolvedTemplateTypeMap' => 
          array (
            'name' => 'resolvedTemplateTypeMap',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\Type\\Generic\\TemplateTypeMap',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 16,
            'endLine' => 16,
            'startColumn' => 3,
            'endColumn' => 59,
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
        'startLine' => 15,
        'endLine' => 19,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Analyser\\Traverser',
        'declaringClassName' => 'PHPStan\\Analyser\\Traverser\\GenericTypeTemplateTraverser',
        'implementingClassName' => 'PHPStan\\Analyser\\Traverser\\GenericTypeTemplateTraverser',
        'currentClassName' => 'PHPStan\\Analyser\\Traverser\\GenericTypeTemplateTraverser',
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
            'startLine' => 24,
            'endLine' => 24,
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
            'startLine' => 24,
            'endLine' => 24,
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
        'startLine' => 24,
        'endLine' => 36,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Analyser\\Traverser',
        'declaringClassName' => 'PHPStan\\Analyser\\Traverser\\GenericTypeTemplateTraverser',
        'implementingClassName' => 'PHPStan\\Analyser\\Traverser\\GenericTypeTemplateTraverser',
        'currentClassName' => 'PHPStan\\Analyser\\Traverser\\GenericTypeTemplateTraverser',
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