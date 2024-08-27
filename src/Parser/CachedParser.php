<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use PhpParser\Node;
use PHPStan\File\FileReader;
use function array_slice;

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
		if ($this->cachedNodesByStringCountMax !== 0 && $this->cachedNodesByStringCount >= $this->cachedNodesByStringCountMax) {
			$this->cachedNodesByString = array_slice(
				$this->cachedNodesByString,
				1,
				null,
				true,
			);

			--$this->cachedNodesByStringCount;
		}

		$sourceCode = FileReader::read($file);
		if (!isset($this->cachedNodesByString[$sourceCode]) || isset($this->parsedByString[$sourceCode])) {
			$this->cachedNodesByString[$sourceCode] = $this->originalParser->parseFile($file);
			$this->cachedNodesByStringCount++;
			unset($this->parsedByString[$sourceCode]);
		}

		return $this->cachedNodesByString[$sourceCode];
	}

	/**
	 * @return Node\Stmt[]
	 */
	public function parseString(string $sourceCode): array
	{
		if ($this->cachedNodesByStringCountMax !== 0 && $this->cachedNodesByStringCount >= $this->cachedNodesByStringCountMax) {
			$this->cachedNodesByString = array_slice(
				$this->cachedNodesByString,
				1,
				null,
				true,
			);

			--$this->cachedNodesByStringCount;
		}

		if (!isset($this->cachedNodesByString[$sourceCode])) {
			$this->cachedNodesByString[$sourceCode] = $this->originalParser->parseString($sourceCode);
			$this->cachedNodesByStringCount++;
			$this->parsedByString[$sourceCode] = true;
		}

		return $this->cachedNodesByString[$sourceCode];
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
