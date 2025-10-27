<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

use Nette\DI\CompilerExtension;
use Override;
use PhpParser\NodeVisitor;
use PHPStan\Analyser\IgnoreErrorExtension;
use PHPStan\Analyser\ResultCache\ResultCacheMetaExtension;
use PHPStan\Analyser\TypeSpecifierFactory;
use PHPStan\Broker\BrokerFactory;
use PHPStan\Collectors\Collector;
use PHPStan\Collectors\RegistryFactory as CollectorRegistryFactory;
use PHPStan\DependencyInjection\Type\LazyDynamicThrowTypeExtensionProvider;
use PHPStan\DependencyInjection\Type\LazyParameterClosureThisExtensionProvider;
use PHPStan\DependencyInjection\Type\LazyParameterClosureTypeExtensionProvider;
use PHPStan\DependencyInjection\Type\LazyParameterOutTypeExtensionProvider;
use PHPStan\Diagnose\DiagnoseExtension;
use PHPStan\Parser\RichParser;
use PHPStan\PhpDoc\StubFilesExtension;
use PHPStan\PhpDoc\TypeNodeResolverExtension;
use PHPStan\Reflection\AllowedSubTypesClassReflectionExtension;
use PHPStan\Reflection\Deprecation\ClassConstantDeprecationExtension;
use PHPStan\Reflection\Deprecation\ClassDeprecationExtension;
use PHPStan\Reflection\Deprecation\EnumCaseDeprecationExtension;
use PHPStan\Reflection\Deprecation\FunctionDeprecationExtension;
use PHPStan\Reflection\Deprecation\MethodDeprecationExtension;
use PHPStan\Reflection\Deprecation\PropertyDeprecationExtension;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use PHPStan\Reflection\PropertiesClassReflectionExtension;
use PHPStan\Rules\Constants\AlwaysUsedClassConstantsExtension;
use PHPStan\Rules\Constants\AlwaysUsedClassConstantsExtensionProvider;
use PHPStan\Rules\LazyRegistry;
use PHPStan\Rules\Methods\AlwaysUsedMethodExtension;
use PHPStan\Rules\Methods\AlwaysUsedMethodExtensionProvider;
use PHPStan\Rules\Properties\ReadWritePropertiesExtension;
use PHPStan\Rules\Properties\ReadWritePropertiesExtensionProvider;
use PHPStan\Rules\RestrictedUsage\RestrictedClassConstantUsageExtension;
use PHPStan\Rules\RestrictedUsage\RestrictedClassNameUsageExtension;
use PHPStan\Rules\RestrictedUsage\RestrictedFunctionUsageExtension;
use PHPStan\Rules\RestrictedUsage\RestrictedMethodUsageExtension;
use PHPStan\Rules\RestrictedUsage\RestrictedPropertyUsageExtension;
use PHPStan\Rules\Rule;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\DynamicFunctionThrowTypeExtension;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\DynamicMethodThrowTypeExtension;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;
use PHPStan\Type\DynamicStaticMethodThrowTypeExtension;
use PHPStan\Type\ExpressionTypeResolverExtension;
use PHPStan\Type\FunctionParameterClosureThisExtension;
use PHPStan\Type\FunctionParameterClosureTypeExtension;
use PHPStan\Type\FunctionParameterOutTypeExtension;
use PHPStan\Type\FunctionTypeSpecifyingExtension;
use PHPStan\Type\MethodParameterClosureThisExtension;
use PHPStan\Type\MethodParameterClosureTypeExtension;
use PHPStan\Type\MethodParameterOutTypeExtension;
use PHPStan\Type\MethodTypeSpecifyingExtension;
use PHPStan\Type\OperatorTypeSpecifyingExtension;
use PHPStan\Type\StaticMethodParameterClosureThisExtension;
use PHPStan\Type\StaticMethodParameterClosureTypeExtension;
use PHPStan\Type\StaticMethodParameterOutTypeExtension;
use PHPStan\Type\StaticMethodTypeSpecifyingExtension;
use ReflectionClass;
use function array_flip;
use function array_key_exists;
use function array_keys;
use function count;

final class ValidateServiceTagsExtension extends CompilerExtension
{

	public const INTERFACE_TAG_MAPPING = [
		PropertiesClassReflectionExtension::class => BrokerFactory::PROPERTIES_CLASS_REFLECTION_EXTENSION_TAG,
		MethodsClassReflectionExtension::class => BrokerFactory::METHODS_CLASS_REFLECTION_EXTENSION_TAG,
		AllowedSubTypesClassReflectionExtension::class => BrokerFactory::ALLOWED_SUB_TYPES_CLASS_REFLECTION_EXTENSION_TAG,
		DynamicMethodReturnTypeExtension::class => BrokerFactory::DYNAMIC_METHOD_RETURN_TYPE_EXTENSION_TAG,
		DynamicStaticMethodReturnTypeExtension::class => BrokerFactory::DYNAMIC_STATIC_METHOD_RETURN_TYPE_EXTENSION_TAG,
		DynamicFunctionReturnTypeExtension::class => BrokerFactory::DYNAMIC_FUNCTION_RETURN_TYPE_EXTENSION_TAG,
		OperatorTypeSpecifyingExtension::class => BrokerFactory::OPERATOR_TYPE_SPECIFYING_EXTENSION_TAG,
		ExpressionTypeResolverExtension::class => BrokerFactory::EXPRESSION_TYPE_RESOLVER_EXTENSION_TAG,
		TypeNodeResolverExtension::class => TypeNodeResolverExtension::EXTENSION_TAG,
		Rule::class => LazyRegistry::RULE_TAG,
		StubFilesExtension::class => StubFilesExtension::EXTENSION_TAG,
		AlwaysUsedClassConstantsExtension::class => AlwaysUsedClassConstantsExtensionProvider::EXTENSION_TAG,
		AlwaysUsedMethodExtension::class => AlwaysUsedMethodExtensionProvider::EXTENSION_TAG,
		ReadWritePropertiesExtension::class => ReadWritePropertiesExtensionProvider::EXTENSION_TAG,
		FunctionTypeSpecifyingExtension::class => TypeSpecifierFactory::FUNCTION_TYPE_SPECIFYING_EXTENSION_TAG,
		MethodTypeSpecifyingExtension::class => TypeSpecifierFactory::METHOD_TYPE_SPECIFYING_EXTENSION_TAG,
		StaticMethodTypeSpecifyingExtension::class => TypeSpecifierFactory::STATIC_METHOD_TYPE_SPECIFYING_EXTENSION_TAG,
		DynamicFunctionThrowTypeExtension::class => LazyDynamicThrowTypeExtensionProvider::FUNCTION_TAG,
		DynamicMethodThrowTypeExtension::class => LazyDynamicThrowTypeExtensionProvider::METHOD_TAG,
		DynamicStaticMethodThrowTypeExtension::class => LazyDynamicThrowTypeExtensionProvider::STATIC_METHOD_TAG,
		FunctionParameterClosureThisExtension::class => LazyParameterClosureThisExtensionProvider::FUNCTION_TAG,
		MethodParameterClosureThisExtension::class => LazyParameterClosureThisExtensionProvider::METHOD_TAG,
		StaticMethodParameterClosureThisExtension::class => LazyParameterClosureThisExtensionProvider::STATIC_METHOD_TAG,
		FunctionParameterClosureTypeExtension::class => LazyParameterClosureTypeExtensionProvider::FUNCTION_TAG,
		MethodParameterClosureTypeExtension::class => LazyParameterClosureTypeExtensionProvider::METHOD_TAG,
		StaticMethodParameterClosureTypeExtension::class => LazyParameterClosureTypeExtensionProvider::STATIC_METHOD_TAG,
		FunctionParameterOutTypeExtension::class => LazyParameterOutTypeExtensionProvider::FUNCTION_TAG,
		MethodParameterOutTypeExtension::class => LazyParameterOutTypeExtensionProvider::METHOD_TAG,
		StaticMethodParameterOutTypeExtension::class => LazyParameterOutTypeExtensionProvider::STATIC_METHOD_TAG,
		ResultCacheMetaExtension::class => ResultCacheMetaExtension::EXTENSION_TAG,
		ClassConstantDeprecationExtension::class => ClassConstantDeprecationExtension::CLASS_CONSTANT_EXTENSION_TAG,
		ClassDeprecationExtension::class => ClassDeprecationExtension::CLASS_EXTENSION_TAG,
		EnumCaseDeprecationExtension::class => EnumCaseDeprecationExtension::ENUM_CASE_EXTENSION_TAG,
		FunctionDeprecationExtension::class => FunctionDeprecationExtension::FUNCTION_EXTENSION_TAG,
		MethodDeprecationExtension::class => MethodDeprecationExtension::METHOD_EXTENSION_TAG,
		PropertyDeprecationExtension::class => PropertyDeprecationExtension::PROPERTY_EXTENSION_TAG,
		RestrictedMethodUsageExtension::class => RestrictedMethodUsageExtension::METHOD_EXTENSION_TAG,
		RestrictedClassNameUsageExtension::class => RestrictedClassNameUsageExtension::CLASS_NAME_EXTENSION_TAG,
		RestrictedFunctionUsageExtension::class => RestrictedFunctionUsageExtension::FUNCTION_EXTENSION_TAG,
		RestrictedPropertyUsageExtension::class => RestrictedPropertyUsageExtension::PROPERTY_EXTENSION_TAG,
		RestrictedClassConstantUsageExtension::class => RestrictedClassConstantUsageExtension::CLASS_CONSTANT_EXTENSION_TAG,
		NodeVisitor::class => RichParser::VISITOR_SERVICE_TAG,
		Collector::class => CollectorRegistryFactory::COLLECTOR_TAG,
		DiagnoseExtension::class => DiagnoseExtension::EXTENSION_TAG,
		IgnoreErrorExtension::class => IgnoreErrorExtension::EXTENSION_TAG,
	];

	/**
	 * @throws MissingImplementedInterfaceInServiceWithTagException
	 */
	#[Override]
	public function beforeCompile(): void
	{
		$builder = $this->getContainerBuilder();
		$mappingCount = count(self::INTERFACE_TAG_MAPPING);
		$flippedMapping = array_flip(self::INTERFACE_TAG_MAPPING);

		if (count($flippedMapping) !== $mappingCount) { // @phpstan-ignore notIdentical.alwaysFalse
			throw new ShouldNotHappenException('A tag is mapped to multiple interfaces');
		}

		foreach ($builder->getDefinitions() as $definition) {
			/** @var class-string|null $className */
			$className = $definition->getType();
			if ($className === null) {
				continue;
			}
			$reflection = new ReflectionClass($className);
			foreach (array_keys($definition->getTags()) as $tag) {
				if (!array_key_exists($tag, $flippedMapping)) {
					continue;
				}

				if ($reflection->implementsInterface($flippedMapping[$tag])) {
					continue;
				}

				throw new MissingImplementedInterfaceInServiceWithTagException($className, $tag, $flippedMapping[$tag]);
			}
		}
	}

}
