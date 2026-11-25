<?php declare(strict_types = 1);

namespace PHPStan\File;

use PHPStan\DependencyInjection\GenerateFactory;
use function array_pop;
use function array_shift;
use function count;
use function fnmatch;
use function in_array;
use function is_dir;
use function is_file;
use function preg_match;
use function preg_split;
use function str_contains;
use function str_ends_with;
use function str_starts_with;
use function strlen;
use function substr;
use const DIRECTORY_SEPARATOR;
use const FNM_CASEFOLD;
use const FNM_NOESCAPE;

#[GenerateFactory(interface: FileExcluderRawFactory::class)]
final class FileExcluder
{

	/**
	 * Directories to exclude from analysing
	 *
	 * @var string[]
	 */
	private array $literalAnalyseDirectoryExcludes = [];

	/**
	 * Files to exclude from analysing
	 *
	 * @var array<string, true>
	 */
	private array $literalAnalyseFilesExcludes = [];

	/**
	 * fnmatch() patterns to use for excluding files and directories from analysing, each with
	 * text a matching path has to contain: the text before the first wildcard, which it starts
	 * with, the text after the last one, which it ends with, and the longest text between two
	 * wildcards. Checking those is much cheaper than fnmatch(), and rules out most paths.
	 *
	 * @var list<array{string, string, string, string}>
	 */
	private array $fnmatchAnalyseExcludes = [];

	private int $fnmatchFlags;

	/**
	 * @param string[] $analyseExcludes
	 */
	public function __construct(
		private FileHelper $fileHelper,
		array $analyseExcludes,
	)
	{
		$isWindows = DIRECTORY_SEPARATOR === '\\';
		$this->fnmatchFlags = $isWindows ? FNM_NOESCAPE | FNM_CASEFOLD : 0;

		foreach ($analyseExcludes as $exclude) {
			$len = strlen($exclude);
			$trailingDirSeparator = ($len > 0 && in_array($exclude[$len - 1], ['\\', '/'], true));

			$normalized = $fileHelper->normalizePath($exclude);

			if ($trailingDirSeparator) {
				$normalized .= DIRECTORY_SEPARATOR;
			}

			if (self::isFnmatchPattern($normalized)) {
				// a path that ends with a separator names a directory, and no file matches it
				if (!$trailingDirSeparator) {
					$this->fnmatchAnalyseExcludes[] = self::createFnmatchExclude($normalized, $isWindows);
				}

				// a directory the pattern matches excludes the files in it, as a literal directory does
				if (!str_ends_with($normalized, '*')) {
					$directory = $trailingDirSeparator ? $normalized : $normalized . DIRECTORY_SEPARATOR;
					$this->fnmatchAnalyseExcludes[] = self::createFnmatchExclude($directory . '*', $isWindows);
				}
			} else {
				if (is_file($normalized)) {
					$this->literalAnalyseFilesExcludes[$normalized] = true;
				} elseif (is_dir($normalized)) {
					if (!$trailingDirSeparator) {
						$normalized .= DIRECTORY_SEPARATOR;
					}

					$this->literalAnalyseDirectoryExcludes[] = $normalized;
				}
			}
		}
	}

	public function isExcludedFromAnalysing(string $file): bool
	{
		$file = $this->fileHelper->normalizePath($file);

		if (isset($this->literalAnalyseFilesExcludes[$file])) {
			return true;
		}
		foreach ($this->literalAnalyseDirectoryExcludes as $exclude) {
			if (str_starts_with($file, $exclude)) {
				return true;
			}
		}
		foreach ($this->fnmatchAnalyseExcludes as $exclude) {
			if ($this->matches($exclude, $file)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param array{string, string, string, string} $exclude
	 */
	private function matches(array $exclude, string $path): bool
	{
		[$pattern, $prefix, $infix, $suffix] = $exclude;

		return str_starts_with($path, $prefix)
			&& str_ends_with($path, $suffix)
			&& str_contains($path, $infix)
			&& fnmatch($pattern, $path, $this->fnmatchFlags);
	}

	/**
	 * @return array{string, string, string, string}
	 */
	private static function createFnmatchExclude(string $pattern, bool $isWindows): array
	{
		// matched case-insensitively on Windows, where the literal text proves nothing
		return [$pattern, ...($isWindows ? ['', '', ''] : self::getLiterals($pattern))];
	}

	/**
	 * The text before the first wildcard, after the last one, and the longest between two.
	 * Nothing for a pattern with a bracket expression, whose ends are not worth parsing here.
	 *
	 * @return array{string, string, string}
	 */
	private static function getLiterals(string $pattern): array
	{
		if (str_contains($pattern, '[')) {
			return ['', '', ''];
		}

		$literals = preg_split('~[*?]~', $pattern);
		if ($literals === false || count($literals) < 2) {
			return ['', '', ''];
		}

		$prefix = array_shift($literals);
		$suffix = array_pop($literals);
		$infix = '';
		foreach ($literals as $literal) {
			if (strlen($literal) <= strlen($infix)) {
				continue;
			}

			$infix = $literal;
		}

		return [$prefix, $infix, $suffix];
	}

	public static function isAbsolutePath(string $path): bool
	{
		if (DIRECTORY_SEPARATOR === '/') {
			if (str_starts_with($path, '/')) {
				return true;
			}
		} elseif (substr($path, 1, 1) === ':') {
			return true;
		}

		return false;
	}

	public static function isFnmatchPattern(string $path): bool
	{
		return preg_match('~[*?[\]]~', $path) > 0;
	}

}
