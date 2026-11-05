<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ResultCache;

use PHPStan\Collectors\CollectedData;

/**
 * Reads the collected data entries of a result cache file. Every method throws
 * UnreadableCollectedDataException when the file does not hold what the index says, after it has
 * discarded the file.
 *
 * @phpstan-import-type CollectorData from CollectedData
 */
interface CollectedDataReader
{

	/**
	 * @param array<string, array{int, int}> $index analysed file => [offset, length] of its entry
	 * @return CollectorData
	 */
	public function read(array $index): array;

	/**
	 * Reads the header of every entry in $index, not the collected data. The headers also hold the
	 * hash of each collector's values.
	 *
	 * @param array<string, array{int, int}> $index analysed file => [offset, length] of its entry
	 */
	public function locateCollectors(array $index): CollectorLocations;

	/**
	 * @param array<string, array{int, int}> $positions analysed file => [offset, length] of the data of $collectorType
	 * @return iterable<string, list<mixed>> analysed file => the collected values, one file at a time
	 */
	public function readCollector(string $collectorType, array $positions, string $signature): iterable;

}
