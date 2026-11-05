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
		$hashes = [];
		foreach (array_keys($index) as $file) {
			foreach ($this->data[$file] ?? [] as $collectorType => $values) {
				$positions[$collectorType][$file] = $index[$file];
				$hashes[$collectorType][$file] = md5(serialize($values));
			}
		}

		return new CollectorLocations('signature', $positions, $hashes);
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

}
