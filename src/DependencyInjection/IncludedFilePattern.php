<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

use Nette\FileNotFoundException;
use PHPStan\File\FileExcluder;
use function array_filter;
use function array_map;
use function array_values;
use function dirname;
use function glob;
use function in_array;
use function is_dir;
use function is_file;
use function preg_match;
use function realpath;
use function sort;
use function sprintf;
use function strtr;
use const SORT_STRING;

/**
 * Lets an entry in `includes` be a glob pattern, like `baselines/*.neon`,
 * which includes every matching file in alphabetical order.
 *
 * Only the entry as written is a pattern. The directory of the file with the includes,
 * which a relative entry is resolved against, is matched literally,
 * even if its path contains a glob metacharacter.
 *
 * An entry that names an existing file is never treated as a pattern,
 * so a file whose name contains a glob metacharacter can still be included.
 *
 * A pattern that matches nothing includes nothing,
 * but the directory it is matched in has to exist.
 *
 * The files that are being loaded at the time are left out of the matches,
 * so that a loader like `baselines/_loader.neon` can include `*.neon`
 * without including itself.
 */
final class IncludedFilePattern
{

	/**
	 * @param string $include the entry in includes, with its parameters already expanded
	 * @param string $mainFile the file with the includes
	 * @param list<string> $loadingFiles $mainFile, and the files that include it in turn
	 * @return list<string>|null the matched files, or null when $include is not a pattern
	 * @throws FileNotFoundException when the directory the pattern is matched in is missing
	 */
	public static function expand(string $include, string $mainFile, array $loadingFiles): ?array
	{
		if (!FileExcluder::isFnmatchPattern($include)) {
			return null;
		}

		// same check as Loader::expandIncludedFile()
		$baseDirectory = preg_match('#([a-z]+:)?[/\\\\]#Ai', $include) === 1 ? '' : dirname($mainFile) . '/';
		if (is_file($baseDirectory . $include)) {
			return null;
		}

		// a pattern that matches nothing includes nothing, so that the last matching file can be deleted,
		// but one whose directory is missing is more likely a mistake, like a missing plain include
		$patternDirectory = $baseDirectory . self::getDirectoryBeforePattern($include);
		if (!is_dir($patternDirectory)) {
			throw new FileNotFoundException(sprintf("Directory '%s' of the included pattern '%s' is missing.", $patternDirectory, $include));
		}

		$matches = glob(self::escape($baseDirectory) . $include);
		if ($matches === false) {
			return [];
		}

		// realpath() fails on files that cannot be resolved, like the ones inside phpstan.phar,
		// and a false among these would leave out every match that cannot be resolved either
		$loadingPaths = array_filter(array_map(static fn (string $file): string|false => realpath($file), $loadingFiles), static fn (string|false $path): bool => $path !== false);
		$files = array_values(array_filter(
			$matches,
			static fn (string $match): bool => is_file($match) && !in_array(realpath($match), $loadingPaths, true),
		));

		// glob() sorts with the locale's collation, while the order of includes decides how values merge
		sort($files, SORT_STRING);

		return $files;
	}

	/**
	 * Returns the part of $include up to the directory separator before the segment with its first metacharacter.
	 */
	private static function getDirectoryBeforePattern(string $include): string
	{
		if (preg_match('~^((?:[^*?[\]]*[/\\\\])?)[^/\\\\*?[\]]*[*?[\]]~', $include, $matches) !== 1) {
			return '';
		}

		return $matches[1];
	}

	/**
	 * Makes glob() match $path literally, by putting each metacharacter in a bracket expression of its own.
	 */
	private static function escape(string $path): string
	{
		return strtr($path, [
			'[' => '[[]',
			'*' => '[*]',
			'?' => '[?]',
		]);
	}

}
