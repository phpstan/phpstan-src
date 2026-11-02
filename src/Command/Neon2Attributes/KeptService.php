<?php declare(strict_types = 1);

namespace PHPStan\Command\Neon2Attributes;

/**
 * A service entry that stays in the NEON config file.
 */
final class KeptService
{

	/** The attributes cannot express the entry. */
	public const CATEGORY_KEPT = 'kept';

	/** The class is registered more than once. */
	public const CATEGORY_DUPLICATE = 'duplicate';

	/** The class is not in a directory from the attributeServicesDirectories section. */
	public const CATEGORY_OUTSIDE = 'outside';

	/**
	 * @param self::CATEGORY_* $category
	 * @param string $name class name, or the service name when the entry is not a class
	 * @param int $entries how many config entries stay, more than one for a class registered several times
	 */
	public function __construct(
		public string $category,
		public string $name,
		public ?string $reason,
		public string $configFile,
		public int $line,
		public int $entries = 1,
	)
	{
	}

}
