<?php declare(strict_types = 1);

namespace PHPStan\Internal;

use const PHP_VERSION_ID;

final class FileHashing
{

	/**
	 * Algorithm for file content hashes used as cache-invalidation keys.
	 *
	 * xxh128 is ~8.5x faster than sha256 but only available since PHP 8.1.
	 */
	public const ALGORITHM = PHP_VERSION_ID >= 80100 ? 'xxh128' : 'sha256';

}
