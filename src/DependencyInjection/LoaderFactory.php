<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

use Nette\DI\Config\Loader;
use PHPStan\Command\Environment;
use PHPStan\File\FileHelper;

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
		// known before the container is compiled, so the adapter can expand them in path entries
		// and the loader in included file names
		$parameters = [
			'rootDir' => $this->rootDir,
			'currentWorkingDirectory' => $this->currentWorkingDirectory,
			'env' => Environment::getCleanedArray(),
		];
		$neonAdapter = new NeonCachedFileReader($this->expandRelativePaths, $parameters);

		$loader = new NeonLoader($this->fileHelper, $this->generateBaselineFile);
		$loader->addAdapter('dist', $neonAdapter);
		$loader->addAdapter('neon', $neonAdapter);
		$loader->setParameters($parameters);

		return $loader;
	}

}
