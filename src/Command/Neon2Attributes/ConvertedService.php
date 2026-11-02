<?php declare(strict_types = 1);

namespace PHPStan\Command\Neon2Attributes;

/**
 * A service entry of a NEON config file replaced by attributes on its class.
 */
final class ConvertedService
{

	/**
	 * @param list<string> $attributes short names of the added attributes
	 */
	public function __construct(
		public string $className,
		public array $attributes,
		public string $configFile,
	)
	{
	}

}
