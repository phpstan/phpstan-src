<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use PhpParser\Node;
use function array_slice;

final class CachedParser implements Parser
{

	/** @var array<string, Node\Stmt[]>*/
	private array $cachedNodesByFile = [];

	private int $cachedNodesByFileCount = 0;

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
		if ($this->cachedNodesByFileCount !== 0 && $this->cachedNodesByFileCount >= $this->cachedNodesByStringCountMax) {
			$this->cachedNodesByFile = array_slice(
				$this->cachedNodesByFile,
				1,
				preserve_keys: true,
			);

			--$this->cachedNodesByFileCount;
		}

		if (!isset($this->cachedNodesByFile[$file])) {
			$this->cachedNodesByFile[$file] = $this->originalParser->parseFile($file);
			$this->cachedNodesByFileCount++;
		}

		return $this->cachedNodesByFile[$file];
	}

	/**
	 * @return Node\Stmt[]
	 */
	public function parseString(string $sourceCode): array
	{
		return $this->originalParser->parseString($sourceCode);
	}

	public function getCachedNodesByStringCountMax(): int
	{
		return $this->cachedNodesByStringCountMax;
	}

}
