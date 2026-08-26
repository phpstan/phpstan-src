<?php declare(strict_types = 1);

namespace PHPStan\Command\Neon2Attributes;

/**
 * Everything the neon2attributes command decided to do with one NEON file.
 */
final class Neon2AttributesPlan
{

	/**
	 * @param list<ServiceConversion> $conversions
	 * @param list<SkippedEntry> $skipped
	 * @param list<string> $directoriesToDeclare paths for the attributeServicesDirectories section,
	 *                                           relative to the NEON file
	 */
	public function __construct(
		public array $conversions,
		public array $skipped,
		public array $directoriesToDeclare,
	)
	{
	}

}
