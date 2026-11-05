<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ResultCache;

use Generator;
use PHPStan\Collectors\CollectedData;
use PHPStan\ShouldNotHappenException;
use function array_diff_key;
use function array_key_exists;
use function count;
use function ksort;
use function md5;
use function serialize;
use const SORT_STRING;

/**
 * Collected data that is read from the result cache file only when something asks for it.
 *
 * The cached part is an index of where each analysed file's entry sits in the cache file; the
 * fresh part is what this run's analysis produced. Until something reads it, the main process
 * holds a few bytes per file, which is all the forked workers inherit.
 *
 * iterate() reads the data of one collector, one file at a time. toArray() reads all of it.
 *
 * @phpstan-import-type CollectorData from CollectedData
 */
final class LazyCollectedData
{

	private ?CollectorLocations $collectorLocations = null;

	/**
	 * @param array<string, array{int, int}> $cachedIndex analysed file => [offset, length] of its entry in the cache file
	 * @param CollectorData $fresh
	 */
	public function __construct(
		private array $cachedIndex,
		private ?CollectedDataReader $cachedReader,
		private array $fresh,
	)
	{
		if ($cachedIndex !== [] && $cachedReader === null) {
			throw new ShouldNotHappenException();
		}
	}

	/**
	 * @param CollectorData $data
	 */
	public static function fromArray(array $data): self
	{
		return new self([], null, $data);
	}

	/**
	 * Editor mode analyses a temporary copy of a file and reports it under the file's own path. Only
	 * the fresh side can hold the temporary path: ResultCacheManager::mergeCollectedData() takes both
	 * paths out of the cached index, and a cached entry is verified against its file on reading, so it
	 * could not be renamed anyway.
	 */
	public function withRenamedFile(string $from, string $to): self
	{
		if (array_key_exists($from, $this->cachedIndex)) {
			throw new ShouldNotHappenException();
		}

		$fresh = [];
		foreach ($this->fresh as $file => $data) {
			$fresh[$file === $from ? $to : $file] = $data;
		}

		return new self($this->cachedIndex, $this->cachedReader, $fresh);
	}

	/**
	 * @return array<string, array{int, int}>
	 */
	public function getCachedIndex(): array
	{
		return $this->cachedIndex;
	}

	/**
	 * @return CollectorData
	 */
	public function getFresh(): array
	{
		return $this->fresh;
	}

	public function isEmpty(): bool
	{
		return count($this->cachedIndex) === 0 && count($this->fresh) === 0;
	}

	/**
	 * The values of one collector, one analysed file at a time: the cached files first, then the
	 * fresh ones, in the order toArray() has them. Holds only the file being yielded in memory.
	 *
	 * @return Generator<string, list<mixed>>
	 */
	public function iterate(string $collectorType): Generator
	{
		$positions = $this->getCachedPositions($collectorType);
		if ($positions !== [] && $this->cachedReader !== null) {
			yield from $this->cachedReader->readCollector($collectorType, $positions, $this->getCollectorLocations()->getSignature());
		}

		foreach ($this->fresh as $file => $data) {
			if (!array_key_exists($collectorType, $data)) {
				continue;
			}

			yield $file => $data[$collectorType];
		}
	}

	/**
	 * A hash of the values of one collector. It does not depend on which files are cached and which
	 * are fresh, or on their order, so the same data hashes the same on every run.
	 */
	public function hashCollector(string $collectorType): string
	{
		$hashes = [];
		$positions = $this->getCachedPositions($collectorType);
		if ($positions !== [] && $this->cachedReader !== null) {
			$hashes = $this->cachedReader->hashCollector($collectorType, $positions, $this->getCollectorLocations()->getSignature());
		}

		foreach ($this->fresh as $file => $data) {
			if (!array_key_exists($collectorType, $data)) {
				continue;
			}

			$hashes[$file] = md5(serialize($data[$collectorType]));
		}

		ksort($hashes, SORT_STRING);

		return md5(serialize($hashes));
	}

	/**
	 * Cached entries first, then the fresh ones; a fresh entry replaces a cached one for the same
	 * file. Reads the cache file every time: nothing is kept, so the caller decides how long the
	 * decoded data lives.
	 *
	 * @return CollectorData
	 */
	public function toArray(): array
	{
		if (count($this->cachedIndex) === 0 || $this->cachedReader === null) {
			return $this->fresh;
		}

		$result = $this->cachedReader->read($this->cachedIndex);
		foreach ($this->fresh as $file => $data) {
			$result[$file] = $data;
		}

		return $result;
	}

	/**
	 * @return array<string, array{int, int}>
	 */
	private function getCachedPositions(string $collectorType): array
	{
		if ($this->cachedIndex === []) {
			return [];
		}

		return array_diff_key($this->getCollectorLocations()->getPositions($collectorType), $this->fresh);
	}

	/**
	 * Read once, on the first iterate() or hashCollector(): those run in the main process after the
	 * analysis, so the forked workers never inherit the positions.
	 */
	private function getCollectorLocations(): CollectorLocations
	{
		if ($this->collectorLocations !== null) {
			return $this->collectorLocations;
		}

		if ($this->cachedReader === null) {
			throw new ShouldNotHappenException();
		}

		return $this->collectorLocations = $this->cachedReader->locateCollectors($this->cachedIndex);
	}

}
