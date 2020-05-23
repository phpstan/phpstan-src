<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use PhpParser\NodeTraverser;
use PhpParser\Parser;
use PHPStan\File\FileReader;
use Roave\BetterReflection\SourceLocator\Located\LocatedSource;

class FileNodesFetcher
{

	private \PHPStan\Reflection\BetterReflection\SourceLocator\CachingVisitor $cachingVisitor;

	private Parser $phpParser;

	public function __construct(
		CachingVisitor $cachingVisitor,
		Parser $phpParser
	)
	{
		$this->cachingVisitor = $cachingVisitor;
		$this->phpParser = $phpParser;
	}

	public function fetchNodes(string $fileName): FetchedNodesResult
	{
		$nodeTraverser = new NodeTraverser();
		$nodeTraverser->addVisitor($this->cachingVisitor);

		$contents = FileReader::read($fileName);
		$locatedSource = new LocatedSource($contents, $fileName);

		try {
			/** @var \PhpParser\Node[] $ast */
			$ast = $this->phpParser->parse($contents);
		} catch (\PhpParser\Error $e) {
			return new FetchedNodesResult([], [], [], $locatedSource);
		}
		$this->cachingVisitor->reset($fileName);
		$nodeTraverser->traverse($ast);

		return new FetchedNodesResult(
			$this->cachingVisitor->getClassNodes(),
			$this->cachingVisitor->getFunctionNodes(),
			$this->cachingVisitor->getConstantNodes(),
			$locatedSource
		);
	}

}
