<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use PhpParser\Node;
use PhpParser\NodeTraverser;
use PHPStan\File\FileReader;
use PHPStan\Parser\Parser;
use PHPStan\Parser\ParserErrorsException;

class FileNodesFetcher
{

	private CachingVisitor $cachingVisitor;

	private Parser $parser;

	public function __construct(
		CachingVisitor $cachingVisitor,
		Parser $parser,
	)
	{
		$this->cachingVisitor = $cachingVisitor;
		$this->parser = $parser;
	}

	public function fetchNodes(string $fileName): FetchedNodesResult
	{
		$nodeTraverser = new NodeTraverser();
		$nodeTraverser->addVisitor($this->cachingVisitor);

		$contents = FileReader::read($fileName);

		try {
			/** @var Node[] $ast */
			$ast = $this->parser->parseFile($fileName);
		} catch (ParserErrorsException $e) {
			return new FetchedNodesResult([], [], []);
		}
		$this->cachingVisitor->reset($fileName, $contents);
		$nodeTraverser->traverse($ast);

		return new FetchedNodesResult(
			$this->cachingVisitor->getClassNodes(),
			$this->cachingVisitor->getFunctionNodes(),
			$this->cachingVisitor->getConstantNodes(),
		);
	}

}
