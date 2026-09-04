<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ResultCache;

use Closure;
use PHPStan\Collectors\CollectedData;
use PHPStan\ShouldNotHappenException;
use function array_key_exists;
use function count;

/**
 * Collected data that is read from the result cache file only when something asks for it.
 *
 * The cached part is an index of where each analysed file's entry sits in the cache file. The
 * fresh part is what this run's analysis produced and holds the actual values. toArray() reads the
 * cached entries and lays the fresh ones over them - the only point where the cached values exist
 * in memory. Until then, the main process holds a few bytes per file, which is what the forked
 * workers inherit; restore() used to hand them the whole decoded section.
 *
 * @phpstan-import-type CollectorData from CollectedData
 */
final class LazyCollectedData
{

	/**
	 * @param array<string, array{int, int}> $cachedIndex analysed file => [offset, length] of its entry in the cache file
	 * @param Closure(array<string, array{int, int}>): CollectorData $cachedReader
	 * @param CollectorData $fresh
	 */
	public function __construct(
		private array $cachedIndex,
		private Closure $cachedReader,
		private array $fresh,
	)
	{
	}

	/**
	 * @param CollectorData $data
	 */
	public static function fromArray(array $data): self
	{
		return new self([], static fn (): array => [], $data);
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
	 * Cached entries first, then the fresh ones; a fresh entry replaces a cached one for the same
	 * file. Reads the cache file every time: nothing is kept, so the caller decides how long the
	 * decoded data lives.
	 *
	 * @return CollectorData
	 */
	public function toArray(): array
	{
		if (count($this->cachedIndex) === 0) {
			return $this->fresh;
		}

		$result = ($this->cachedReader)($this->cachedIndex);
		foreach ($this->fresh as $file => $data) {
			$result[$file] = $data;
		}

		return $result;
	}

}
