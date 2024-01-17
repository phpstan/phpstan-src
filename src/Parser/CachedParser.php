<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use PhpParser\Node;
use PHPStan\File\FileReader;

class CachedParser implements Parser
{

	/** @var LRUCache<Node\Stmt[]>*/
	private LRUCache $cache;

	/** @var array<string, true> */
	private array $parsedByString = [];

	public function __construct(
		private Parser $originalParser,
		int $cacheCapacity,
	)
	{
		$this->cache = new LRUCache($cacheCapacity);
	}

	/**
	 * @param string $file path to a file to parse
	 * @return Node\Stmt[]
	 */
	public function parseFile(string $file): array
	{
		$sourceCode = FileReader::read($file);
		if (!$this->cache->has($sourceCode) || isset($this->parsedByString[$sourceCode])) {
			$this->cache->put($sourceCode, $this->originalParser->parseFile($file));
			unset($this->parsedByString[$sourceCode]);
		}

		return $this->cache->get($sourceCode);
	}

	/**
	 * @return Node\Stmt[]
	 */
	public function parseString(string $sourceCode): array
	{
		if (!$this->cache->has($sourceCode)) {
			$this->cache->put($sourceCode, $this->originalParser->parseString($sourceCode));
			$this->parsedByString[$sourceCode] = true;
		}

		return $this->cache->get($sourceCode);
	}

	public function getCacheCapacity(): int
	{
		return $this->cache->getCapacity();
	}

	public function getCachedItemsCount(): int
	{
		return $this->cache->getCount();
	}

}
