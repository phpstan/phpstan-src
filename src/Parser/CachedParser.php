<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use PhpParser\Node;
use PHPStan\File\FileReader;
use function array_key_first;
use function strlen;

final class CachedParser implements Parser
{

	/**
	 * Size-based eviction never shrinks the cache below this many entries.
	 * Without this floor, a single source larger than $cachedSourceBytesMax
	 * would flush the whole cache on every insertion, degenerating the cache
	 * to a single entry whenever such a source is hot.
	 */
	private const SIZE_EVICTION_FLOOR_LIMIT = 32;

	/** @var array<string, Node\Stmt[]>*/
	private array $cachedNodesByString = [];

	private int $cachedNodesByStringCount = 0;

	private int $cachedSourceBytes = 0;

	/** @var array<string, true> */
	private array $parsedByString = [];

	/**
	 * The AST of a parsed file takes up roughly 50-60x more memory than the
	 * source code itself, so alongside the entry count limit, the total source
	 * size of the cached ASTs is capped by $cachedSourceBytesMax (0 = unlimited)
	 * so that large files cannot pin hundreds of megabytes in each worker
	 * process. The cap has to be generous enough to hold a big project's hot
	 * working set (WordPress needs ~4 MB) because evicting a hot file costs
	 * a re-parse proportional to the very bytes the eviction saved.
	 */
	public function __construct(
		private Parser $originalParser,
		private int $cachedNodesByStringCountMax,
		private int $cachedSourceBytesMax,
	)
	{
	}

	/**
	 * @param string $file path to a file to parse
	 * @return Node\Stmt[]
	 */
	public function parseFile(string $file): array
	{
		$sourceCode = FileReader::read($file);
		$isCached = isset($this->cachedNodesByString[$sourceCode]);
		if ($isCached && !isset($this->parsedByString[$sourceCode])) {
			return $this->markRecentlyUsed($sourceCode);
		}

		$nodes = $this->originalParser->parseFile($file);
		if ($isCached) {
			// upgrade an entry previously produced by parseString() in place -
			// no net change to the entry count, just refresh its LRU position
			unset($this->cachedNodesByString[$sourceCode], $this->parsedByString[$sourceCode]);
		} else {
			$this->evictLeastRecentlyUsed(strlen($sourceCode));
			$this->cachedNodesByStringCount++;
			$this->cachedSourceBytes += strlen($sourceCode);
		}

		$this->cachedNodesByString[$sourceCode] = $nodes;

		return $nodes;
	}

	/**
	 * @return Node\Stmt[]
	 */
	public function parseString(string $sourceCode): array
	{
		if (isset($this->cachedNodesByString[$sourceCode])) {
			return $this->markRecentlyUsed($sourceCode);
		}

		$nodes = $this->originalParser->parseString($sourceCode);
		$this->evictLeastRecentlyUsed(strlen($sourceCode));
		$this->cachedNodesByString[$sourceCode] = $nodes;
		$this->cachedNodesByStringCount++;
		$this->cachedSourceBytes += strlen($sourceCode);
		$this->parsedByString[$sourceCode] = true;

		return $nodes;
	}

	/**
	 * LRU bookkeeping: re-insert the entry at the end so genuinely cold sources
	 * are evicted first, not the ones inserted earliest.
	 *
	 * @return Node\Stmt[]
	 */
	private function markRecentlyUsed(string $sourceCode): array
	{
		$nodes = $this->cachedNodesByString[$sourceCode];
		unset($this->cachedNodesByString[$sourceCode]);
		$this->cachedNodesByString[$sourceCode] = $nodes;

		return $nodes;
	}

	private function evictLeastRecentlyUsed(int $incomingSourceBytes): void
	{
		if ($this->cachedNodesByStringCountMax === 0) {
			return;
		}

		while (
			$this->cachedNodesByStringCount >= $this->cachedNodesByStringCountMax
			|| (
				$this->cachedSourceBytesMax > 0
				&& $this->cachedSourceBytes + $incomingSourceBytes > $this->cachedSourceBytesMax
				&& $this->cachedNodesByStringCount > self::SIZE_EVICTION_FLOOR_LIMIT
			)
		) {
			$oldestKey = array_key_first($this->cachedNodesByString);
			if ($oldestKey === null) {
				break;
			}

			unset($this->cachedNodesByString[$oldestKey], $this->parsedByString[$oldestKey]);
			$this->cachedNodesByStringCount--;
			$this->cachedSourceBytes -= strlen($oldestKey);
		}
	}

	public function getCachedNodesByStringCount(): int
	{
		return $this->cachedNodesByStringCount;
	}

	public function getCachedNodesByStringCountMax(): int
	{
		return $this->cachedNodesByStringCountMax;
	}

	/**
	 * @return array<string, Node[]>
	 */
	public function getCachedNodesByString(): array
	{
		return $this->cachedNodesByString;
	}

}
