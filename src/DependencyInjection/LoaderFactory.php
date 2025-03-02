<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

use Nette\DI\Config\Loader;
use function getenv;

final class LoaderFactory
{

	public function __construct(
		private string $rootDir,
		private string $currentWorkingDirectory,
	)
	{
	}

	public function createLoader(): Loader
	{
		$loader = new Loader();
		$loader->addAdapter('dist', NeonAdapter::class);
		$loader->addAdapter('neon', NeonAdapter::class);
		$loader->setParameters([
			'rootDir' => $this->rootDir,
			'currentWorkingDirectory' => $this->currentWorkingDirectory,
			'env' => getenv(),
		]);

		return $loader;
	}

}
