<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use PhpParser\Node;
use PhpParser\NodeTraverser;
use PHPStan\File\FileReader;
use PHPStan\Parser\Parser;
use PHPStan\Parser\ParserErrorsException;

class FileNodesFetcher
{

	private NodeTraverser $nodeTraverser;

	public function __construct(
		private CachingVisitor $cachingVisitor,
		private Parser $parser,
	)
	{
		$this->nodeTraverser = new NodeTraverser();
		$this->nodeTraverser->addVisitor($this->cachingVisitor);
	}

	public function fetchNodes(string $fileName): FetchedNodesResult
	{
		$contents = FileReader::read($fileName);

		try {
			/** @var Node[] $ast */
			$ast = $this->parser->parseFile($fileName);
		} catch (ParserErrorsException) {
			return new FetchedNodesResult([], [], []);
		}

		$this->cachingVisitor->prepare($fileName, $contents);
		$this->nodeTraverser->traverse($ast);

		$result = new FetchedNodesResult(
			$this->cachingVisitor->getClassNodes(),
			$this->cachingVisitor->getFunctionNodes(),
			$this->cachingVisitor->getConstantNodes(),
		);

		$this->cachingVisitor->reset();

		return $result;
	}

}
