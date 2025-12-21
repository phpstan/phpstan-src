<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

use Nette\DI\Config\Loader;
use PHPStan\File\FileHelper;
use function getenv;

final class LoaderFactory
{

	/**
	 * @param list<string> $expandRelativePaths
	 */
	public function __construct(
		private FileHelper $fileHelper,
		private string $rootDir,
		private string $currentWorkingDirectory,
		private ?string $generateBaselineFile,
		private array $expandRelativePaths,
	)
	{
	}

	public function createLoader(): Loader
	{
		$neonAdapter = new NeonCachedFileReader($this->expandRelativePaths);

		$loader = new NeonLoader($this->fileHelper, $this->generateBaselineFile);
		$loader->addAdapter('dist', $neonAdapter);
		$loader->addAdapter('neon', $neonAdapter);
		$loader->setParameters([
			'rootDir' => $this->rootDir,
			'currentWorkingDirectory' => $this->currentWorkingDirectory,
			'env' => getenv(),
		]);

		return $loader;
	}

}
