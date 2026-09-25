<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use PhpParser\NodeTraverser;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\File\DeduplicatingFileReader;
use PHPStan\Parser\Parser;
use PHPStan\Parser\ParserErrorsException;

#[AutowiredService]
final class FileNodesFetcher
{

	public function __construct(
		private CachingVisitor $cachingVisitor,
		#[AutowiredParameter(ref: '@defaultAnalysisParser')]
		private Parser $parser,
		private DeduplicatingFileReader $fileReader,
	)
	{
	}

	public function fetchNodes(string $fileName): FetchedNodesResult
	{
		$nodeTraverser = new NodeTraverser();
		$nodeTraverser->addVisitor($this->cachingVisitor);

		$contents = $this->fileReader->read($fileName);

		try {
			$ast = $this->parser->parseFile($fileName);
		} catch (ParserErrorsException) {
			return new FetchedNodesResult([], [], []);
		}
		$this->cachingVisitor->reset($fileName, $contents);
		$nodeTraverser->traverse($ast);

		$result = new FetchedNodesResult(
			$this->cachingVisitor->getClassNodes(),
			$this->cachingVisitor->getFunctionNodes(),
			$this->cachingVisitor->getConstantNodes(),
		);

		$this->cachingVisitor->reset($fileName, $contents);

		return $result;
	}

}
