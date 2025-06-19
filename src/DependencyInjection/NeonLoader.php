<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

use Nette\DI\Config\Loader;
use Override;
use PHPStan\File\FileHelper;

final class NeonLoader extends Loader
{

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
		if ($this->generateBaselineFile === null) {
			return parent::load($file, $merge);
		}

		$normalizedFile = $this->fileHelper->normalizePath($file);
		if ($this->generateBaselineFile === $normalizedFile) {
			return [];
		}

		return parent::load($file, $merge);
	}

}
