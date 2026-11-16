<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

use Nette\DI\Config\Loader;
use Nette\Schema\Helpers;
use Override;
use PHPStan\File\FileHelper;
use PHPStan\ShouldNotHappenException;
use function array_pop;
use function is_array;

final class NeonLoader extends Loader
{

	/** @var list<string> */
	private array $loadingFiles = [];

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
		// Loader::load() passes each entry in includes back here, after expanding it to an absolute path
		$matchedFiles = IncludedFilePattern::expand($file, $this->loadingFiles);
		if ($matchedFiles !== null) {
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

		$this->loadingFiles[] = $file;
		try {
			return parent::load($file, $merge);
		} finally {
			array_pop($this->loadingFiles);
		}
	}

}
