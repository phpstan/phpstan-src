<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

use Nette\DI\Config\Loader;
use Nette\DI\ContainerLoader;
use PHPStan\File\FileReader;
use function array_keys;
use function sha1;
use const PHP_RELEASE_VERSION;
use const PHP_VERSION_ID;

class Configurator extends \Nette\Bootstrap\Configurator
{

	/** @var string[] */
	private array $allConfigFiles = [];

	public function __construct(private LoaderFactory $loaderFactory)
	{
		parent::__construct();
	}

	protected function createLoader(): Loader
	{
		return $this->loaderFactory->createLoader();
	}

	/**
	 * @param string[] $allConfigFiles
	 */
	public function setAllConfigFiles(array $allConfigFiles): void
	{
		$this->allConfigFiles = $allConfigFiles;
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
			$this->staticParameters['debugMode'],
		);

		return $loader->load(
			[$this, 'generateContainer'],
			[$this->staticParameters, array_keys($this->dynamicParameters), $this->configs, PHP_VERSION_ID - PHP_RELEASE_VERSION, NeonAdapter::CACHE_KEY, $this->getAllConfigFilesHashes()],
		);
	}

	/**
	 * @return string[]
	 */
	private function getAllConfigFilesHashes(): array
	{
		$hashes = [];
		foreach ($this->allConfigFiles as $file) {
			$hashes[$file] = sha1(FileReader::read($file));
		}

		return $hashes;
	}

}
