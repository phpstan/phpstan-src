<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use PhpParser\Node\Stmt;
use PhpParser\NodeTraverser;
use PHPStan\Php\PhpVersion;

final class CleaningParser implements Parser
{

	private NodeTraverser $traverser;

	public function __construct(private Parser $wrappedParser, PhpVersion $phpVersion)
	{
		$this->traverser = new NodeTraverser();
		$this->traverser->addVisitor(new CleaningVisitor());
		$this->traverser->addVisitor(new RemoveUnusedCodeByPhpVersionIdVisitor($phpVersion->getVersionString()));
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
