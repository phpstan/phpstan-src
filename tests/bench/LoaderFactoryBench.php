<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

use LogicException;
use PHPStan\DependencyInjection\ContainerFactory;
use PHPStan\DependencyInjection\LoaderFactory;
use PHPStan\File\FileHelper;
use ReflectionClass;
use Tally\Attribute\Before;
use Tally\ScratchDirectory;
use function dirname;

final class LoaderFactoryBench
{

	private ?LoaderFactory $loaderFactory = null;

	private string $configFile = '';

	public function __construct(private ScratchDirectory $scratch)
	{
	}

	#[Before]
	public function boot(): void
	{
		BenchContainer::create($this->scratch);
		$root = dirname((string) (new ReflectionClass(ContainerFactory::class))->getFileName(), 3);
		$this->loaderFactory = new LoaderFactory(new FileHelper($root), $root, $root, null, []);
		$this->configFile = $root . '/conf/config.level8.neon';
		$this->loaderFactory->createLoader()->load($this->configFile);
	}

	/**
	 * Building the container loads the same config files more than once, with all they include.
	 *
	 * @return mixed[]
	 */
	public function benchLoadConfigAgain(): array
	{
		if ($this->loaderFactory === null) {
			throw new LogicException('boot() did not run.');
		}

		return $this->loaderFactory->createLoader()->load($this->configFile);
	}

}
