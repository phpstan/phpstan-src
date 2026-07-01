<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use PhpParser\Node;
use PHPStan\File\FileReader;
use function array_key_first;

final class CachedParser implements Parser
{

	/** @var array<string, Node\Stmt[]>*/
	private array $cachedNodesByString = [];

	private int $cachedNodesByStringCount = 0;

	/** @var array<string, true> */
	private array $parsedByString = [];

	public function __construct(
		private Parser $originalParser,
		private int $cachedNodesByStringCountMax,
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
			$this->evictLeastRecentlyUsed();
			$this->cachedNodesByStringCount++;
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
		$this->evictLeastRecentlyUsed();
		$this->cachedNodesByString[$sourceCode] = $nodes;
		$this->cachedNodesByStringCount++;
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

	private function evictLeastRecentlyUsed(): void
	{
		if ($this->cachedNodesByStringCountMax === 0) {
			return;
		}

		while ($this->cachedNodesByStringCount >= $this->cachedNodesByStringCountMax) {
			$oldestKey = array_key_first($this->cachedNodesByString);
			if ($oldestKey === null) {
				break;
			}

			unset($this->cachedNodesByString[$oldestKey], $this->parsedByString[$oldestKey]);
			$this->cachedNodesByStringCount--;
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
