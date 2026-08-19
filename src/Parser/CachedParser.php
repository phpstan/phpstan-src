<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use PhpParser\Node;
use PHPStan\File\FileReader;
use PHPStan\Internal\LruCache;
use function clearstatcache;
use function filemtime;
use function filesize;
use function strlen;

final class CachedParser implements Parser
{

	/**
	 * Size-based eviction never shrinks the AST cache below this many entries.
	 * Without this floor, a single source larger than $cachedSourceBytesMax
	 * would flush the whole cache on every insertion, degenerating the cache
	 * to a single entry whenever such a source is hot.
	 */
	private const SIZE_EVICTION_FLOOR_LIMIT = 32;

	/**
	 * Default for $cachedSourceBytesMax, must match the default of
	 * cache.nodesByStringSourceBytesMax in config.neon. It doubles as the
	 * constructor default because third-party extensions (e.g. Larastan's
	 * migrationsParser service) instantiate this class without the parameter.
	 */
	private const CACHED_SOURCE_BYTES_DEFAULT_LIMIT = 4_194_304;

	/**
	 * parseFile() is called once per class using a trait, so the same file
	 * is read from disk over and over (one hot trait file was read 94,000
	 * times in a cold run of a large Laravel project). Memoizing the contents
	 * by path skips those redundant reads; the total memoized source size is
	 * bounded the same way as the AST cache.
	 */
	private const MEMOIZED_SOURCE_BYTES_LIMIT = 524_288;

	/** @var LruCache<Node\Stmt[]> keyed by source code, weighing the length of that source */
	private LruCache $cachedNodesByString;

	/** @var array<string, true> */
	private array $parsedByString = [];

	/** @var LruCache<array{int, int, string}> path => [mtime, size, source code] */
	private LruCache $cachedSourceByFile;

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
		private int $cachedSourceBytesMax = self::CACHED_SOURCE_BYTES_DEFAULT_LIMIT,
	)
	{
		$this->cachedNodesByString = new LruCache(
			$this->cachedNodesByStringCountMax,
			$this->cachedSourceBytesMax,
			self::SIZE_EVICTION_FLOOR_LIMIT,
		);
		$this->cachedSourceByFile = new LruCache(maxWeight: self::MEMOIZED_SOURCE_BYTES_LIMIT);
	}

	/**
	 * @param string $file path to a file to parse
	 * @return Node\Stmt[]
	 */
	public function parseFile(string $file): array
	{
		$sourceCode = $this->readFile($file);
		$cachedNodes = $this->cachedNodesByString->get($sourceCode);
		if ($cachedNodes !== null && !isset($this->parsedByString[$sourceCode])) {
			return $cachedNodes;
		}

		$nodes = $this->originalParser->parseFile($file);
		if ($cachedNodes !== null) {
			// upgrade an entry previously produced by parseString() in place -
			// no net change to the entry count, just refresh its LRU position
			$this->cachedNodesByString->replace($sourceCode, $nodes);
			unset($this->parsedByString[$sourceCode]);

			return $nodes;
		}

		$this->store($sourceCode, $nodes);

		return $nodes;
	}

	/**
	 * @return Node\Stmt[]
	 */
	public function parseString(string $sourceCode): array
	{
		$cachedNodes = $this->cachedNodesByString->get($sourceCode);
		if ($cachedNodes !== null) {
			return $cachedNodes;
		}

		$nodes = $this->originalParser->parseString($sourceCode);
		$this->store($sourceCode, $nodes);
		$this->parsedByString[$sourceCode] = true;

		return $nodes;
	}

	/**
	 * @param Node\Stmt[] $nodes
	 */
	private function store(string $sourceCode, array $nodes): void
	{
		foreach ($this->cachedNodesByString->set($sourceCode, $nodes, strlen($sourceCode)) as $evictedSourceCode) {
			unset($this->parsedByString[$evictedSourceCode]);
		}
	}

	private function readFile(string $file): string
	{
		// mtime alone has one-second granularity, so a same-second edit could be
		// served stale in a long-running process (PHPStan Pro, fixer worker);
		// keying by size as well catches edits that change the length. filesize()
		// is served from PHP's stat cache populated by filemtime(), so it costs
		// no extra syscall.
		clearstatcache(true, $file);
		$mtime = @filemtime($file);
		$size = @filesize($file);
		if ($mtime === false || $size === false) {
			return FileReader::read($file);
		}

		$cached = $this->cachedSourceByFile->get($file);
		if ($cached !== null && $cached[0] === $mtime && $cached[1] === $size) {
			return $cached[2];
		}

		$sourceCode = FileReader::read($file);
		if (strlen($sourceCode) <= self::MEMOIZED_SOURCE_BYTES_LIMIT) {
			// set() replaces a stale entry for this path (mtime or size changed) as well
			$this->cachedSourceByFile->set($file, [$mtime, $size, $sourceCode], strlen($sourceCode));
		}

		return $sourceCode;
	}

	public function getCachedNodesByStringCount(): int
	{
		return $this->cachedNodesByString->count();
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
		return $this->cachedNodesByString->all();
	}

}
