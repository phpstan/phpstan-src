<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/src/Type/Generic/TemplateObjectWithoutClassType.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\Type\Generic\TemplateObjectWithoutClassType
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-516fe61c4582c9b4ba1c1f89a79b7d9d5bbd6a815914f34d901292728a9ae6dc',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\Type\\Generic\\TemplateObjectWithoutClassType',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/src/Type/Generic/TemplateObjectWithoutClassType.php',
      ),
    ),
    'namespace' => 'PHPStan\\Type\\Generic',
    'name' => 'PHPStan\\Type\\Generic\\TemplateObjectWithoutClassType',
    'shortName' => 'TemplateObjectWithoutClassType',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/** @api */',
    'attributes' => 
    array (
    ),
    'startLine' => 10,
    'endLine' => 44,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'PHPStan\\Type\\ObjectWithoutClassType',
    'implementsClassNames' => 
    array (
      0 => 'PHPStan\\Type\\Generic\\TemplateType',
    ),
    'traitClassNames' => 
    array (
      0 => 'PHPStan\\Type\\Traits\\UndecidedComparisonCompoundTypeTrait',
      1 => 'PHPStan\\Type\\Generic\\TemplateTypeTrait',
    ),
    'immediateConstants' => 
    array (
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      '__construct' => 
      array (
        'name' => '__construct',
        'parameters' => 
        array (
          'scope' => 
          array (
            'name' => 'scope',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\Type\\Generic\\TemplateTypeScope',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 21,
            'endLine' => 21,
            'startColumn' => 3,
            'endColumn' => 26,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'templateTypeStrategy' => 
          array (
            'name' => 'templateTypeStrategy',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\Type\\Generic\\TemplateTypeStrategy',
                'isIdentifier' => false,
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
            'startColumn' => 3,
            'endColumn' => 44,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'templateTypeVariance' => 
          array (
            'name' => 'templateTypeVariance',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\Type\\Generic\\TemplateTypeVariance',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 23,
            'endLine' => 23,
            'startColumn' => 3,
            'endColumn' => 44,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'name' => 
          array (
            'name' => 'name',
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
            'startLine' => 24,
            'endLine' => 24,
            'startColumn' => 3,
            'endColumn' => 14,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
          'bound' => 
          array (
            'name' => 'bound',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'PHPStan\\Type\\ObjectWithoutClassType',
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
            'startColumn' => 3,
            'endColumn' => 31,
            'parameterIndex' => 4,
            'isOptional' => false,
          ),
          'default' => 
          array (
            'name' => 'default',
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
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 26,
            'endLine' => 26,
            'startColumn' => 3,
            'endColumn' => 16,
            'parameterIndex' => 5,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @param non-empty-string $name
 */',
        'startLine' => 20,
        'endLine' => 37,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type\\Generic',
        'declaringClassName' => 'PHPStan\\Type\\Generic\\TemplateObjectWithoutClassType',
        'implementingClassName' => 'PHPStan\\Type\\Generic\\TemplateObjectWithoutClassType',
        'currentClassName' => 'PHPStan\\Type\\Generic\\TemplateObjectWithoutClassType',
        'aliasName' => NULL,
      ),
      'getClassStringType' => 
      array (
        'name' => 'getClassStringType',
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
        'startLine' => 39,
        'endLine' => 42,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\Type\\Generic',
        'declaringClassName' => 'PHPStan\\Type\\Generic\\TemplateObjectWithoutClassType',
        'implementingClassName' => 'PHPStan\\Type\\Generic\\TemplateObjectWithoutClassType',
        'currentClassName' => 'PHPStan\\Type\\Generic\\TemplateObjectWithoutClassType',
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