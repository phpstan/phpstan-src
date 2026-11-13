<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

use PHPStan\DependencyInjection\Container;
use PHPStan\DependencyInjection\ContainerFactory;
use ReflectionClass;
use Tally\ScratchDirectory;
use function dirname;

/**
 * The container the microbenchmarks need for reflection and the static accessors types use.
 * The native run Tally starts before the counted one compiles it into the scratch directory,
 * the counted run loads it from there.
 */
final class BenchContainer
{

	public static function create(ScratchDirectory $scratch): Container
	{
		// the root of the PHPStan that is loaded, so the benchmarks also run against other versions
		$containerFactory = new ContainerFactory(dirname((string) (new ReflectionClass(ContainerFactory::class))->getFileName(), 3));

		return $containerFactory->create($scratch->path(), [$containerFactory->getConfigDirectory() . '/config.level8.neon'], []);
	}

}
