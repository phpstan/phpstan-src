<?php declare(strict_types = 1);

namespace PHPStan\Command\Neon2Attributes;

use ReflectionClass;

final class ClassAttributes
{

	/**
	 * @param ReflectionClass<object> $reflection
	 * @param array<string, AttributeSpec> $parameterAttributes by constructor parameter name
	 */
	public function __construct(
		public ReflectionClass $reflection,
		public AttributeSpec $attribute,
		public array $parameterAttributes,
	)
	{
	}

}
