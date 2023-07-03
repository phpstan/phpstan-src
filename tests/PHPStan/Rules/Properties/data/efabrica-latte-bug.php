<?php // lint >= 7.4

namespace EfabricaLatteBug;

use Nette\Utils\Finder;
use PHPStan\File\FileExcluder;
use SplFileInfo;

final class AnalysedTemplatesRegistry
{
	private FileExcluder $fileExcluder;

	/** @var string[] */
	private array $analysedPaths = [];

	private bool $reportUnanalysedTemplates;

	/** @var array<string, bool>  */
	private array $templateFiles = [];

	/**
	 * @param string[] $analysedPaths
	 */
	public function __construct(FileExcluder $fileExcluder, array $analysedPaths, bool $reportUnanalysedTemplates)
	{
		$this->fileExcluder = $fileExcluder;
		$this->analysedPaths = $analysedPaths;
		$this->reportUnanalysedTemplates = $reportUnanalysedTemplates;
		foreach ($this->getExistingTemplates() as $file) {
			$this->templateFiles[$file] = false;
		}
	}

	public function isExcludedFromAnalysing(string $path): bool
	{
		return $this->fileExcluder->isExcludedFromAnalysing($path);
	}

	public function templateAnalysed(string $path): void
	{
		$path = realpath($path) ?: $path;
		$this->templateFiles[$path] = true;
	}

	/**
	 * @return string[]
	 */
	public function getExistingTemplates(): array
	{
		$files = [];
		foreach ($this->analysedPaths as $analysedPath) {
			if (!is_dir($analysedPath)) {
				continue;
			}
			/** @var SplFileInfo $file */
			foreach (Finder::findFiles('*.latte')->from($analysedPath) as $file) {
				$filePath = (string)$file;
				if ($this->isExcludedFromAnalysing($filePath)) {
					continue;
				}
				$files[] = $filePath;
			}
		}
		$files = array_unique($files);
		sort($files);
		return $files;
	}

	/**
	 * @return string[]
	 */
	public function getAnalysedTemplates(): array
	{
		return array_keys(array_filter($this->templateFiles, function (bool $val) {
			return $val;
		}));
	}

	/**
	 * @return string[]
	 */
	public function getUnanalysedTemplates(): array
	{
		return array_keys(array_filter($this->templateFiles, function (bool $val) {
			return !$val;
		}));
	}

	/**
	 * @return string[]
	 */
	public function getReportedUnanalysedTemplates(): array
	{
		if ($this->reportUnanalysedTemplates) {
			return $this->getUnanalysedTemplates();
		} else {
			return [];
		}
	}
}
