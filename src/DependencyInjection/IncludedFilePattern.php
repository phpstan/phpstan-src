<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

use PHPStan\File\FileExcluder;
use function array_filter;
use function array_values;
use function glob;
use function is_file;
use function sort;
use const SORT_STRING;

/**
 * Lets an entry in `includes` be a glob pattern, like `baselines/*.neon`,
 * which includes every matching file in alphabetical order.
 *
 * A path that names an existing file is never treated as a pattern,
 * so a file whose name contains a glob metacharacter can still be included.
 */
final class IncludedFilePattern
{

	/**
	 * @param string $includedFile already expanded to an absolute path
	 * @return list<string>|null the matched files, or null when $includedFile is not a pattern
	 */
	public static function expand(string $includedFile): ?array
	{
		if (is_file($includedFile) || !FileExcluder::isFnmatchPattern($includedFile)) {
			return null;
		}

		$matches = glob($includedFile);
		if ($matches === false) {
			return [];
		}

		// glob() sorts with the locale's collation, while the order of includes decides how values merge
		$files = array_values(array_filter($matches, static fn (string $match): bool => is_file($match)));
		sort($files, SORT_STRING);

		return $files;
	}

}
