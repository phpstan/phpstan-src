<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection\ProjectConfig;

final class FeatureTogglesConfig implements Arrayable
{

	use ArrayableTrait;

	public function __construct(
		private readonly bool|Undefined $bleedingEdge = Undefined::Undefined,
		private readonly bool|Undefined $checkParameterCastableToNumberFunctions = Undefined::Undefined,
		/** @var list<string> */
		private readonly array|Undefined $skipCheckGenericClasses = Undefined::Undefined,
		private readonly bool|Undefined $stricterFunctionMap = Undefined::Undefined,
	)
	{
	}

}
