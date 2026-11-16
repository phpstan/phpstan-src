<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

use PHPStan\File\FileExcluder;
use function array_filter;
use function array_map;
use function array_values;
use function glob;
use function in_array;
use function is_file;
use function realpath;
use function sort;
use const SORT_STRING;

/**
 * Lets an entry in `includes` be a glob pattern, like `baselines/*.neon`,
 * which includes every matching file in alphabetical order.
 *
 * A path that names an existing file is never treated as a pattern,
 * so a file whose name contains a glob metacharacter can still be included.
 *
 * The files that are being loaded at the time are left out of the matches,
 * so that a loader like `baselines/_loader.neon` can include `*.neon`
 * without including itself.
 */
final class IncludedFilePattern
{

	/**
	 * @param string $includedFile already expanded to an absolute path
	 * @param list<string> $loadingFiles the file with the includes, and the files that include it in turn
	 * @return list<string>|null the matched files, or null when $includedFile is not a pattern
	 */
	public static function expand(string $includedFile, array $loadingFiles = []): ?array
	{
		if (is_file($includedFile) || !FileExcluder::isFnmatchPattern($includedFile)) {
			return null;
		}

		$matches = glob($includedFile);
		if ($matches === false) {
			return [];
		}

		// glob() sorts with the locale's collation, while the order of includes decides how values merge
		$loadingPaths = array_map(static fn (string $file): string|false => realpath($file), $loadingFiles);
		$files = array_values(array_filter(
			$matches,
			static fn (string $match): bool => is_file($match) && !in_array(realpath($match), $loadingPaths, true),
		));
		sort($files, SORT_STRING);

		return $files;
	}

}
