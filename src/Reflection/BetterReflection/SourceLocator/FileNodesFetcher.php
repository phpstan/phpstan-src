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

		$classNodes = [];
		foreach ($this->cachingVisitor->getClassNodes() as $className => $fetchedClassNode) {
			$classNodes[$className] = $fetchedClassNode;
		}

		$functionNodes = [];
		foreach ($this->cachingVisitor->getFunctionNodes() as $functionName => $fetchedFunctionNode) {
			$functionNodes[$functionName] = $fetchedFunctionNode;
		}

		$constantNodes = [];
		foreach ($this->cachingVisitor->getConstantNodes() as $constantName => $fetchedConstantNode) {
			$constantNodes[$constantName] = $fetchedConstantNode;
		}

		return new FetchedNodesResult(
			$classNodes,
			$functionNodes,
			$constantNodes,
			$locatedSource
		);
	}

}
