<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ResultCache;

use Generator;
use PHPStan\Collectors\CollectedData;
use PHPStan\ShouldNotHappenException;
use function array_diff_key;
use function array_key_exists;
use function array_keys;
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
 * holds a few bytes per file, which is all the forked workers inherit. A parallel analysis writes
 * its fresh data to a CollectedDataSpool (the spooled part); other analyses keep it in memory.
 *
 * iterate() reads the data of one collector, one file at a time. toArray() reads all of it.
 *
 * @phpstan-import-type CollectorData from CollectedData
 */
final class LazyCollectedData
{

	private ?CollectorLocations $collectorLocations = null;

	private ?UnreadableCollectedDataException $unreadableException = null;

	/**
	 * @param array<string, array{int, int}> $cachedIndex analysed file => [offset, length] of its entry in the cache file
	 * @param CollectorData $fresh
	 */
	public function __construct(
		private array $cachedIndex,
		private ?CollectedDataReader $cachedReader,
		private array $fresh,
		private ?SpooledCollectedData $spooled = null,
	)
	{
		if ($spooled !== null && $spooled->isEmpty()) {
			$this->spooled = null;
		}

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

		return new self($this->cachedIndex, $this->cachedReader, $fresh, $this->spooled !== null ? $this->spooled->withRenamedFile($from, $to) : null);
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

	public function getSpooled(): ?SpooledCollectedData
	{
		return $this->spooled;
	}

	public function isEmpty(): bool
	{
		return count($this->cachedIndex) === 0 && count($this->fresh) === 0 && $this->spooled === null;
	}

	/**
	 * The values of one collector, one analysed file at a time: the cached files in the order of the
	 * cache file, then the spooled ones, then the fresh ones. A spooled or fresh file replaces its
	 * cached entry. Holds only the file being yielded in memory.
	 *
	 * @return Generator<string, list<mixed>>
	 */
	public function iterate(string $collectorType): Generator
	{
		$positions = $this->getCachedPositions($collectorType);
		if ($positions !== [] && $this->cachedReader !== null) {
			try {
				yield from $this->cachedReader->readCollector($collectorType, $positions, $this->getCollectorLocations()->getSignature());
			} catch (UnreadableCollectedDataException $e) {
				$this->unreadableException = $e;

				throw $e;
			}
		}

		if ($this->spooled !== null) {
			yield from $this->spooled->iterate($collectorType);
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
	 * are fresh, or on their order, so the same data hashes the same on every run. The hashes of the
	 * cached files are stored in the cache file, so nothing is read but the entry headers.
	 */
	public function hashCollector(string $collectorType): string
	{
		$hashes = [];
		if ($this->cachedIndex !== []) {
			$hashes = $this->withoutSpooledAndFresh($this->getCollectorLocations()->getHashes($collectorType));
		}

		if ($this->spooled !== null) {
			$hashes = $this->spooled->getHashes($collectorType) + $hashes;
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
	 * The read error of the cache file, if a read failed. A rule may catch the exception and go on with
	 * partial data, so the caller checks this after the rule, not only for the exception.
	 */
	public function getUnreadableException(): ?UnreadableCollectedDataException
	{
		return $this->unreadableException;
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
		$result = [];
		if (count($this->cachedIndex) !== 0 && $this->cachedReader !== null) {
			try {
				$result = $this->cachedReader->read($this->cachedIndex);
			} catch (UnreadableCollectedDataException $e) {
				$this->unreadableException = $e;

				throw $e;
			}
		}

		if ($this->spooled !== null) {
			foreach (array_keys($this->spooled->getFiles()) as $file) {
				$result[$file] = $this->spooled->read($file);
			}
		}

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

		return $this->withoutSpooledAndFresh($this->getCollectorLocations()->getPositions($collectorType));
	}

	/**
	 * @template T
	 * @param array<string, T> $cached
	 * @return array<string, T>
	 */
	private function withoutSpooledAndFresh(array $cached): array
	{
		$cached = array_diff_key($cached, $this->fresh);
		if ($this->spooled !== null) {
			$cached = array_diff_key($cached, $this->spooled->getFiles());
		}

		return $cached;
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

		try {
			return $this->collectorLocations = $this->cachedReader->locateCollectors($this->cachedIndex);
		} catch (UnreadableCollectedDataException $e) {
			$this->unreadableException = $e;

			throw $e;
		}
	}

}
