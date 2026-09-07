<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\Reflection\ParameterReflection;

final class ClosureParameterTypes
{

	/**
	 * @param ParameterReflection[]|null $parameters
	 * @param ParameterReflection[]|null $nativeParameters
	 */
	public function __construct(
		public readonly ?array $parameters,
		public readonly ?array $nativeParameters,
	)
	{
	}

}
