<?php declare(strict_types = 1);

namespace PHPStan\Command\Neon2Attributes;

/**
 * One configuration entry that has to stay in the NEON file, and why.
 */
final class SkippedEntry
{

	/**
	 * @param 'services'|'rules' $section
	 */
	public function __construct(
		public string $section,
		public string $description,
		public string $reason,
	)
	{
	}

}
