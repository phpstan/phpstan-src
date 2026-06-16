<?php declare(strict_types = 1);

namespace PHPStan\File;

use PHPStan\DependencyInjection\GenerateFactory;
use function fnmatch;
use function in_array;
use function is_dir;
use function is_file;
use function preg_match;
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
	 * fnmatch() patterns to use for excluding files and directories from analysing
	 * @var string[]
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
		foreach ($analyseExcludes as $exclude) {
			$len = strlen($exclude);
			$trailingDirSeparator = ($len > 0 && in_array($exclude[$len - 1], ['\\', '/'], true));

			$normalized = $fileHelper->normalizePath($exclude);

			if ($trailingDirSeparator) {
				$normalized .= DIRECTORY_SEPARATOR;
			}

			if (self::isFnmatchPattern($normalized)) {
				$this->fnmatchAnalyseExcludes[] = $normalized;
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

		$isWindows = DIRECTORY_SEPARATOR === '\\';
		if ($isWindows) {
			$this->fnmatchFlags = FNM_NOESCAPE | FNM_CASEFOLD;
		} else {
			$this->fnmatchFlags = 0;
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
			if (fnmatch($exclude, $file, $this->fnmatchFlags)) {
				return true;
			}
		}

		return false;
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
