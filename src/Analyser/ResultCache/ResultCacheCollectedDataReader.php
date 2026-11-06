<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ResultCache;

use Closure;
use PHPStan\Collectors\CollectedData;

/**
 * Reads the collected data of the result cache file through ResultCacheManager, which owns the file
 * format.
 *
 * @phpstan-import-type CollectorData from CollectedData
 */
final class ResultCacheCollectedDataReader implements CollectedDataReader
{

	/**
	 * @param Closure(array<string, array{int, int}>): CollectorData $read
	 * @param Closure(array<string, array{int, int}>): CollectorLocations $locateCollectors
	 * @param Closure(string, array<string, array{int, int}>, string): iterable<string, list<mixed>> $readCollector
	 */
	public function __construct(
		private Closure $read,
		private Closure $locateCollectors,
		private Closure $readCollector,
	)
	{
	}

	public function read(array $index): array
	{
		return ($this->read)($index);
	}

	public function locateCollectors(array $index): CollectorLocations
	{
		return ($this->locateCollectors)($index);
	}

	public function readCollector(string $collectorType, array $positions, string $signature): iterable
	{
		return ($this->readCollector)($collectorType, $positions, $signature);
	}

}
