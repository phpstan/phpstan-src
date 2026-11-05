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
	 * Reads the header of every entry in $index, not the collected data.
	 *
	 * @param array<string, array{int, int}> $index analysed file => [offset, length] of its entry
	 */
	public function locateCollectors(array $index): CollectorLocations;

	/**
	 * @param array<string, array{int, int}> $positions analysed file => [offset, length] of the data of $collectorType
	 * @return iterable<string, list<mixed>> analysed file => the collected values, one file at a time
	 */
	public function readCollector(string $collectorType, array $positions, string $signature): iterable;

	/**
	 * md5 of serialize() of each file's collected values, with absolute paths in them. That is the
	 * hash of the values as they are in memory after an analysis, so a cached file and a re-analysed
	 * one with the same data hash the same.
	 *
	 * @param array<string, array{int, int}> $positions analysed file => [offset, length] of the data of $collectorType
	 * @return array<string, string> analysed file => hash
	 */
	public function hashCollector(string $collectorType, array $positions, string $signature): array;

}
