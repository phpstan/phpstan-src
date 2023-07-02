<?php declare(strict_types = 1);

namespace PHPStan\File;

use function fnmatch;
use function in_array;
use function preg_match;
use function strlen;
use function strpos;
use const DIRECTORY_SEPARATOR;
use const FNM_CASEFOLD;
use const FNM_NOESCAPE;

class FileExcluder
{

	/**
	 * Directories to exclude from analysing
	 *
	 * @var string[]
	 */
	private array $literalAnalyseExcludes = [];

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
		FileHelper $fileHelper,
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

			if ($this->isFnmatchPattern($normalized)) {
				$this->fnmatchAnalyseExcludes[] = $normalized;
			} else {
				$this->literalAnalyseExcludes[] = $fileHelper->absolutizePath($normalized);
			}
		}

		$isWindows = DIRECTORY_SEPARATOR === '\\';
		if ($isWindows) {
			$this->fnmatchFlags = FNM_NOESCAPE | FNM_CASEFOLD;
		} else {
			$this->fnmatchFlags = 0;
		}
	}

	/**
	 * @return string[]
	 */
	public function getExcludedLiteralPaths(): array
	{
		return $this->literalAnalyseExcludes;
	}

	public function isExcludedFromAnalysing(string $file): bool
	{
		foreach ($this->literalAnalyseExcludes as $exclude) {
			if (strpos($file, $exclude) === 0) {
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

	private function isFnmatchPattern(string $path): bool
	{
		return preg_match('~[*?[\]]~', $path) > 0;
	}

}
