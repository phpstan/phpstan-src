<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use PhpParser\Node\Stmt;
use PhpParser\NodeTraverser;

class CleaningParser implements Parser
{

	private NodeTraverser $traverser;

	public function __construct(private Parser $wrappedParser)
	{
		$this->traverser = new NodeTraverser();
		$this->traverser->addVisitor(new CleaningVisitor());
	}

	public function parseFile(string $file): array
	{
		return $this->clean($this->wrappedParser->parseFile($file));
	}

	public function parseString(string $sourceCode): array
	{
		return $this->clean($this->wrappedParser->parseString($sourceCode));
	}

	/**
	 * @param Stmt[] $ast
	 * @return Stmt[]
	 */
	private function clean(array $ast): array
	{
		/** @var Stmt[] */
		return $this->traverser->traverse($ast);
	}

}
