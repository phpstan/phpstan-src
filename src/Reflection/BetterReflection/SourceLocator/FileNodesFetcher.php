<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use PhpParser\NodeTraverser;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\File\FileReader;
use PHPStan\Internal\LruCache;
use PHPStan\Parser\Parser;
use PHPStan\Parser\ParserErrorsException;
use function strlen;

#[AutowiredService]
final class FileNodesFetcher
{

	/**
	 * Every located symbol keeps its file's contents in its LocatedSource, and the
	 * locators fetch a file once per symbol. Handing out one string per unchanged
	 * file keeps a large stub file in memory once instead of once per symbol.
	 */
	private const CONTENTS_COUNT_LIMIT = 256;

	/** @var LruCache<string> path => contents */
	private LruCache $contentsByFile;

	public function __construct(
		private CachingVisitor $cachingVisitor,
		#[AutowiredParameter(ref: '@defaultAnalysisParser')]
		private Parser $parser,
	)
	{
		$this->contentsByFile = new LruCache(self::CONTENTS_COUNT_LIMIT);
	}

	public function fetchNodes(string $fileName): FetchedNodesResult
	{
		$nodeTraverser = new NodeTraverser();
		$nodeTraverser->addVisitor($this->cachingVisitor);

		$contents = FileReader::read($fileName);
		$previousContents = $this->contentsByFile->get($fileName);
		if ($previousContents === $contents) {
			$contents = $previousContents;
		} else {
			$this->contentsByFile->set($fileName, $contents, strlen($contents));
		}

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
