<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\File\FilesystemHelper;
use function array_fill_keys;
use function array_map;
use function strtolower;

final class AnalysedFilesResolver
{

	/** @var bool[] filePath(string) => bool(true) */
	private array $analysedFiles;

	/**
	 * @param string[] $files
	 */
	public function __construct(array $files = [])
	{
		$this->setAnalysedFiles($files);
	}

	/**
	 * @param string[] $files
	 */
	public function setAnalysedFiles(array $files): void
	{
		if (FilesystemHelper::isCaseSensitive() === false) {
			$files = array_map(static fn (string $file): string => strtolower($file), $files);
		}
		$this->analysedFiles = array_fill_keys($files, true);
	}

	public function isInAnalyzedFiles(string $file): bool
	{
		if (FilesystemHelper::isCaseSensitive() === false) {
			$file = strtolower($file);
		}

		return isset($this->analysedFiles[$file]);
	}

}
