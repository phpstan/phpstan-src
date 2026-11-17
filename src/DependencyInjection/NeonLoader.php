<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

use Nette\DI\Config\Loader;
use Nette\Schema\Helpers;
use Override;
use PHPStan\File\FileHelper;
use PHPStan\ShouldNotHappenException;
use function array_pop;
use function is_array;
use function strpbrk;

final class NeonLoader extends Loader
{

	/** @var list<string> */
	private array $loadingFiles = [];

	/**
	 * The files matched by each included pattern, by the path that expandIncludedFile() returned for it.
	 *
	 * @var array<string, list<string>>
	 */
	private array $patternMatches = [];

	public function __construct(
		private FileHelper $fileHelper,
		private ?string $generateBaselineFile,
	)
	{
	}

	/**
	 * @return mixed[]
	 */
	#[Override]
	public function load(string $file, ?bool $merge = true): array
	{
		if (isset($this->patternMatches[$file])) {
			$matchedFiles = $this->patternMatches[$file];
			unset($this->patternMatches[$file]);

			$res = [];
			foreach ($matchedFiles as $matchedFile) {
				// same merge as for consecutive entries in includes, so later files override earlier ones
				$res = Helpers::merge($this->load($matchedFile, $merge), $res);
				if (!is_array($res)) {
					throw new ShouldNotHappenException();
				}
			}

			return $res;
		}

		if ($this->generateBaselineFile !== null && $this->generateBaselineFile === $this->fileHelper->normalizePath($file)) {
			return [];
		}

		// like Loader::$loadedFiles, which the parent does not unset either when loading fails,
		// as a config that failed to load leaves the loader with nothing to continue with
		$this->loadingFiles[] = $file;
		$res = parent::load($file, $merge);
		array_pop($this->loadingFiles);

		return $res;
	}

	/**
	 * Loader::load() calls this for each entry in includes, and passes the result back to load().
	 *
	 * The pattern is matched here rather than in load(), because only here is the entry known as written,
	 * apart from the directory of $mainFile it is resolved against.
	 */
	#[Override]
	public function expandIncludedFile(string $includedFile, string $mainFile): string
	{
		$expandedFile = parent::expandIncludedFile($includedFile, $mainFile);

		// checked here too, so that the common case of a plain include does not pay for the call
		if (strpbrk($includedFile, IncludedFilePattern::METACHARACTERS) === false) {
			return $expandedFile;
		}

		$matchedFiles = IncludedFilePattern::expand($includedFile, $mainFile, $this->loadingFiles);
		if ($matchedFiles !== null) {
			$this->patternMatches[$expandedFile] = $matchedFiles;
		}

		return $expandedFile;
	}

}
