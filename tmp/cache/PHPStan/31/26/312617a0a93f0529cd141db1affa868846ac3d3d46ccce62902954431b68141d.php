<?php declare(strict_types = 1);

// odsl-/home/runner/work/phpstan-src/phpstan-src/src/DependencyInjection/ValidateServiceTagsExtension.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PHPStan\DependencyInjection\ValidateServiceTagsExtension
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.1-8.4.21-443065313cfd899dc9007a28f52f1fe0f9e210e657275e347e24aafece55ef50',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PHPStan\\DependencyInjection\\ValidateServiceTagsExtension',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/src/DependencyInjection/ValidateServiceTagsExtension.php',
      ),
    ),
    'namespace' => 'PHPStan\\DependencyInjection',
    'name' => 'PHPStan\\DependencyInjection\\ValidateServiceTagsExtension',
    'shortName' => 'ValidateServiceTagsExtension',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => NULL,
    'attributes' => 
    array (
    ),
    'startLine' => 73,
    'endLine' => 161,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'Nette\\DI\\CompilerExtension',
    'implementsClassNames' => 
    array (
    ),
    'traitClassNames' => 
    array (
    ),
    'immediateConstants' => 
    array (
      'INTERFACE_TAG_MAPPING' => 
      array (
        'declaringClassName' => 'PHPStan\\DependencyInjection\\ValidateServiceTagsExtension',
        'implementingClassName' => 'PHPStan\\DependencyInjection\\ValidateServiceTagsExtension',
        'name' => 'INTERFACE_TAG_MAPPING',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\\PHPStan\\Reflection\\PropertiesClassReflectionExtension::class => \\PHPStan\\Broker\\BrokerFactory::PROPERTIES_CLASS_REFLECTION_EXTENSION_TAG, \\PHPStan\\Reflection\\MethodsClassReflectionExtension::class => \\PHPStan\\Broker\\BrokerFactory::METHODS_CLASS_REFLECTION_EXTENSION_TAG, \\PHPStan\\Reflection\\AllowedSubTypesClassReflectionExtension::class => \\PHPStan\\Broker\\BrokerFactory::ALLOWED_SUB_TYPES_CLASS_REFLECTION_EXTENSION_TAG, \\PHPStan\\Type\\DynamicMethodReturnTypeExtension::class => \\PHPStan\\Broker\\BrokerFactory::DYNAMIC_METHOD_RETURN_TYPE_EXTENSION_TAG, \\PHPStan\\Type\\DynamicStaticMethodReturnTypeExtension::class => \\PHPStan\\Broker\\BrokerFactory::DYNAMIC_STATIC_METHOD_RETURN_TYPE_EXTENSION_TAG, \\PHPStan\\Type\\DynamicFunctionReturnTypeExtension::class => \\PHPStan\\Broker\\BrokerFactory::DYNAMIC_FUNCTION_RETURN_TYPE_EXTENSION_TAG, \\PHPStan\\Type\\OperatorTypeSpecifyingExtension::class => \\PHPStan\\Broker\\BrokerFactory::OPERATOR_TYPE_SPECIFYING_EXTENSION_TAG, \\PHPStan\\Type\\UnaryOperatorTypeSpecifyingExtension::class => \\PHPStan\\Broker\\BrokerFactory::UNARY_OPERATOR_TYPE_SPECIFYING_EXTENSION_TAG, \\PHPStan\\Type\\ExpressionTypeResolverExtension::class => \\PHPStan\\Broker\\BrokerFactory::EXPRESSION_TYPE_RESOLVER_EXTENSION_TAG, \\PHPStan\\PhpDoc\\TypeNodeResolverExtension::class => \\PHPStan\\PhpDoc\\TypeNodeResolverExtension::EXTENSION_TAG, \\PHPStan\\Rules\\Rule::class => \\PHPStan\\Rules\\LazyRegistry::RULE_TAG, \\PHPStan\\PhpDoc\\StubFilesExtension::class => \\PHPStan\\PhpDoc\\StubFilesExtension::EXTENSION_TAG, \\PHPStan\\Rules\\Constants\\AlwaysUsedClassConstantsExtension::class => \\PHPStan\\Rules\\Constants\\AlwaysUsedClassConstantsExtensionProvider::EXTENSION_TAG, \\PHPStan\\Rules\\Methods\\AlwaysUsedMethodExtension::class => \\PHPStan\\Rules\\Methods\\AlwaysUsedMethodExtensionProvider::EXTENSION_TAG, \\PHPStan\\Rules\\Properties\\ReadWritePropertiesExtension::class => \\PHPStan\\Rules\\Properties\\ReadWritePropertiesExtensionProvider::EXTENSION_TAG, \\PHPStan\\Type\\FunctionTypeSpecifyingExtension::class => \\PHPStan\\Analyser\\TypeSpecifierFactory::FUNCTION_TYPE_SPECIFYING_EXTENSION_TAG, \\PHPStan\\Type\\MethodTypeSpecifyingExtension::class => \\PHPStan\\Analyser\\TypeSpecifierFactory::METHOD_TYPE_SPECIFYING_EXTENSION_TAG, \\PHPStan\\Type\\StaticMethodTypeSpecifyingExtension::class => \\PHPStan\\Analyser\\TypeSpecifierFactory::STATIC_METHOD_TYPE_SPECIFYING_EXTENSION_TAG, \\PHPStan\\Type\\DynamicFunctionThrowTypeExtension::class => \\PHPStan\\DependencyInjection\\Type\\LazyDynamicThrowTypeExtensionProvider::FUNCTION_TAG, \\PHPStan\\Type\\DynamicMethodThrowTypeExtension::class => \\PHPStan\\DependencyInjection\\Type\\LazyDynamicThrowTypeExtensionProvider::METHOD_TAG, \\PHPStan\\Type\\DynamicStaticMethodThrowTypeExtension::class => \\PHPStan\\DependencyInjection\\Type\\LazyDynamicThrowTypeExtensionProvider::STATIC_METHOD_TAG, \\PHPStan\\Type\\FunctionParameterClosureThisExtension::class => \\PHPStan\\DependencyInjection\\Type\\LazyParameterClosureThisExtensionProvider::FUNCTION_TAG, \\PHPStan\\Type\\MethodParameterClosureThisExtension::class => \\PHPStan\\DependencyInjection\\Type\\LazyParameterClosureThisExtensionProvider::METHOD_TAG, \\PHPStan\\Type\\StaticMethodParameterClosureThisExtension::class => \\PHPStan\\DependencyInjection\\Type\\LazyParameterClosureThisExtensionProvider::STATIC_METHOD_TAG, \\PHPStan\\Type\\FunctionParameterClosureTypeExtension::class => \\PHPStan\\DependencyInjection\\Type\\LazyParameterClosureTypeExtensionProvider::FUNCTION_TAG, \\PHPStan\\Type\\MethodParameterClosureTypeExtension::class => \\PHPStan\\DependencyInjection\\Type\\LazyParameterClosureTypeExtensionProvider::METHOD_TAG, \\PHPStan\\Type\\StaticMethodParameterClosureTypeExtension::class => \\PHPStan\\DependencyInjection\\Type\\LazyParameterClosureTypeExtensionProvider::STATIC_METHOD_TAG, \\PHPStan\\Type\\FunctionParameterOutTypeExtension::class => \\PHPStan\\DependencyInjection\\Type\\LazyParameterOutTypeExtensionProvider::FUNCTION_TAG, \\PHPStan\\Type\\MethodParameterOutTypeExtension::class => \\PHPStan\\DependencyInjection\\Type\\LazyParameterOutTypeExtensionProvider::METHOD_TAG, \\PHPStan\\Type\\StaticMethodParameterOutTypeExtension::class => \\PHPStan\\DependencyInjection\\Type\\LazyParameterOutTypeExtensionProvider::STATIC_METHOD_TAG, \\PHPStan\\Analyser\\ResultCache\\ResultCacheMetaExtension::class => \\PHPStan\\Analyser\\ResultCache\\ResultCacheMetaExtension::EXTENSION_TAG, \\PHPStan\\Reflection\\Deprecation\\ClassConstantDeprecationExtension::class => \\PHPStan\\Reflection\\Deprecation\\ClassConstantDeprecationExtension::CLASS_CONSTANT_EXTENSION_TAG, \\PHPStan\\Reflection\\Deprecation\\ClassDeprecationExtension::class => \\PHPStan\\Reflection\\Deprecation\\ClassDeprecationExtension::CLASS_EXTENSION_TAG, \\PHPStan\\Reflection\\Deprecation\\EnumCaseDeprecationExtension::class => \\PHPStan\\Reflection\\Deprecation\\EnumCaseDeprecationExtension::ENUM_CASE_EXTENSION_TAG, \\PHPStan\\Reflection\\Deprecation\\FunctionDeprecationExtension::class => \\PHPStan\\Reflection\\Deprecation\\FunctionDeprecationExtension::FUNCTION_EXTENSION_TAG, \\PHPStan\\Reflection\\Deprecation\\MethodDeprecationExtension::class => \\PHPStan\\Reflection\\Deprecation\\MethodDeprecationExtension::METHOD_EXTENSION_TAG, \\PHPStan\\Reflection\\Deprecation\\PropertyDeprecationExtension::class => \\PHPStan\\Reflection\\Deprecation\\PropertyDeprecationExtension::PROPERTY_EXTENSION_TAG, \\PHPStan\\Rules\\RestrictedUsage\\RestrictedMethodUsageExtension::class => \\PHPStan\\Rules\\RestrictedUsage\\RestrictedMethodUsageExtension::METHOD_EXTENSION_TAG, \\PHPStan\\Rules\\RestrictedUsage\\RestrictedClassNameUsageExtension::class => \\PHPStan\\Rules\\RestrictedUsage\\RestrictedClassNameUsageExtension::CLASS_NAME_EXTENSION_TAG, \\PHPStan\\Rules\\RestrictedUsage\\RestrictedFunctionUsageExtension::class => \\PHPStan\\Rules\\RestrictedUsage\\RestrictedFunctionUsageExtension::FUNCTION_EXTENSION_TAG, \\PHPStan\\Rules\\RestrictedUsage\\RestrictedPropertyUsageExtension::class => \\PHPStan\\Rules\\RestrictedUsage\\RestrictedPropertyUsageExtension::PROPERTY_EXTENSION_TAG, \\PHPStan\\Rules\\RestrictedUsage\\RestrictedClassConstantUsageExtension::class => \\PHPStan\\Rules\\RestrictedUsage\\RestrictedClassConstantUsageExtension::CLASS_CONSTANT_EXTENSION_TAG, \\PhpParser\\NodeVisitor::class => \\PHPStan\\Parser\\RichParser::VISITOR_SERVICE_TAG, \\PHPStan\\Collectors\\Collector::class => \\PHPStan\\Collectors\\RegistryFactory::COLLECTOR_TAG, \\PHPStan\\Diagnose\\DiagnoseExtension::class => \\PHPStan\\Diagnose\\DiagnoseExtension::EXTENSION_TAG, \\PHPStan\\Analyser\\IgnoreErrorExtension::class => \\PHPStan\\Analyser\\IgnoreErrorExtension::EXTENSION_TAG, \\PHPStan\\Analyser\\ExprHandler::class => \\PHPStan\\Analyser\\ExprHandler::EXTENSION_TAG]',
          'attributes' => 
          array (
            'startLine' => 76,
            'endLine' => 124,
            'startTokenPos' => 383,
            'startFilePos' => 3666,
            'endTokenPos' => 902,
            'endFilePos' => 8158,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 76,
        'endLine' => 124,
        'startColumn' => 2,
        'endColumn' => 3,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'beforeCompile' => 
      array (
        'name' => 'beforeCompile',
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
          0 => 
          array (
            'name' => 'Override',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => '/**
 * @throws MissingImplementedInterfaceInServiceWithTagException
 */',
        'startLine' => 129,
        'endLine' => 159,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PHPStan\\DependencyInjection',
        'declaringClassName' => 'PHPStan\\DependencyInjection\\ValidateServiceTagsExtension',
        'implementingClassName' => 'PHPStan\\DependencyInjection\\ValidateServiceTagsExtension',
        'currentClassName' => 'PHPStan\\DependencyInjection\\ValidateServiceTagsExtension',
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