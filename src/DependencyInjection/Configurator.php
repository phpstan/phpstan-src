<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

use Nette\DI\Config\Loader;
use Nette\DI\ContainerLoader;

class Configurator extends \Nette\Bootstrap\Configurator
{

	private LoaderFactory $loaderFactory;

	public function __construct(LoaderFactory $loaderFactory)
	{
		$this->loaderFactory = $loaderFactory;

		parent::__construct();
	}

	protected function createLoader(): Loader
	{
		return $this->loaderFactory->createLoader();
	}

	/**
	 * @return mixed[]
	 */
	protected function getDefaultParameters(): array
	{
		return [];
	}

	public function getContainerCacheDirectory(): string
	{
		return $this->getCacheDirectory() . '/nette.configurator';
	}

	public function loadContainer(): string
	{
		$loader = new ContainerLoader(
			$this->getContainerCacheDirectory(),
			$this->staticParameters['debugMode']
		);

		return $loader->load(
			[$this, 'generateContainer'],
			[$this->staticParameters, array_keys($this->dynamicParameters), $this->configs, PHP_VERSION_ID - PHP_RELEASE_VERSION, NeonAdapter::CACHE_KEY]
		);
	}

}
