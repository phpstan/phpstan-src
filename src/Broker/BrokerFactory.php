<?php declare(strict_types = 1);

namespace PHPStan\Broker;

use PHPStan\DependencyInjection\Container;
use PHPStan\Reflection\ReflectionProvider;

class BrokerFactory
{

	public const PROPERTIES_CLASS_REFLECTION_EXTENSION_TAG = 'phpstan.broker.propertiesClassReflectionExtension';
	public const METHODS_CLASS_REFLECTION_EXTENSION_TAG = 'phpstan.broker.methodsClassReflectionExtension';
	public const ALLOWED_SUB_TYPES_CLASS_REFLECTION_EXTENSION_TAG = 'phpstan.broker.allowedSubTypesClassReflectionExtension';
	public const DYNAMIC_METHOD_RETURN_TYPE_EXTENSION_TAG = 'phpstan.broker.dynamicMethodReturnTypeExtension';
	public const DYNAMIC_STATIC_METHOD_RETURN_TYPE_EXTENSION_TAG = 'phpstan.broker.dynamicStaticMethodReturnTypeExtension';
	public const DYNAMIC_FUNCTION_RETURN_TYPE_EXTENSION_TAG = 'phpstan.broker.dynamicFunctionReturnTypeExtension';
	public const OPERATOR_TYPE_SPECIFYING_EXTENSION_TAG = 'phpstan.broker.operatorTypeSpecifyingExtension';
	public const EXPRESSION_TYPE_RESOLVER_EXTENSION_TAG = 'phpstan.broker.expressionTypeResolverExtension';

	public function __construct(private Container $container)
	{
	}

	public function create(): Broker
	{
		return new Broker(
			$this->container->getByType(ReflectionProvider::class),
			$this->container->getParameter('universalObjectCratesClasses'),
		);
	}

}
