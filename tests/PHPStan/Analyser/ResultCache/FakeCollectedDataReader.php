<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ResultCache;

use Generator;
use PHPStan\Collectors\CollectedData;
use function array_intersect_key;
use function array_keys;
use function md5;
use function serialize;

/**
 * @phpstan-import-type CollectorData from CollectedData
 */
final class FakeCollectedDataReader implements CollectedDataReader
{

	public int $reads = 0;

	public int $locates = 0;

	/** @var array<string, list<string>> */
	public array $readCollectors = [];

	/**
	 * @param CollectorData $data
	 */
	public function __construct(private array $data)
	{
	}

	public function read(array $index): array
	{
		$this->reads++;

		return array_intersect_key($this->data, $index);
	}

	public function locateCollectors(array $index): CollectorLocations
	{
		$this->locates++;
		$positions = [];
		foreach (array_keys($index) as $file) {
			foreach (array_keys($this->data[$file] ?? []) as $collectorType) {
				$positions[$collectorType][$file] = $index[$file];
			}
		}

		return new CollectorLocations('signature', $positions);
	}

	/**
	 * @return Generator<string, list<mixed>>
	 */
	public function readCollector(string $collectorType, array $positions, string $signature): Generator
	{
		foreach (array_keys($positions) as $file) {
			$this->readCollectors[$collectorType][] = $file;
			yield $file => $this->data[$file][$collectorType];
		}
	}

	public function hashCollector(string $collectorType, array $positions, string $signature): array
	{
		$hashes = [];
		foreach (array_keys($positions) as $file) {
			$hashes[$file] = md5(serialize($this->data[$file][$collectorType]));
		}

		return $hashes;
	}

}
