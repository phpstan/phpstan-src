<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

use Nette\DI\Config\Loader;
use PHPStan\File\FileHelper;
use function getenv;

final class LoaderFactory
{

	public function __construct(
		private FileHelper $fileHelper,
		private string $rootDir,
		private string $currentWorkingDirectory,
		private ?string $generateBaselineFile,
	)
	{
	}

	public function createLoader(): Loader
	{
		$loader = new NeonLoader($this->fileHelper, $this->generateBaselineFile);
		$loader->addAdapter('dist', NeonAdapter::class);
		$loader->addAdapter('neon', NeonAdapter::class);
		$loader->addAdapter('php', PhpAdapter::class);
		$loader->setParameters([
			'rootDir' => $this->rootDir,
			'currentWorkingDirectory' => $this->currentWorkingDirectory,
			'env' => getenv(),
		]);

		return $loader;
	}

}
