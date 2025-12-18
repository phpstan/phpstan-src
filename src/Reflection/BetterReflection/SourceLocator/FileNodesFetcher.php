<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use PhpParser\NodeTraverser;
use PHPStan\Cache\Cache;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\File\FileReader;
use PHPStan\Parser\Parser;
use PHPStan\Parser\ParserErrorsException;

#[AutowiredService]
final class FileNodesFetcher
{

	private const CACHE_ROOT_KEY = 'vendor-reflections';
	private const CACHE_VARIABLE_KEY = 'v2';

	private array $data = [];

	public function __construct(
		private CachingVisitor $cachingVisitor,
		#[AutowiredParameter(ref: '@defaultAnalysisParser')]
		private Parser $parser,
		private Cache $cache,
	)
	{
	}

	private function persist(): void
	{
		$this->cache->save(self::CACHE_ROOT_KEY, self::CACHE_VARIABLE_KEY, $this->data);
	}

	private function loadCache(): void
	{
		$cached = $this->cache->load(self::CACHE_ROOT_KEY, self::CACHE_VARIABLE_KEY);
		if ($cached !== null) {
			$this->data = $cached;
		}
	}


	public function fetchNodes(string $fileName): FetchedNodesResult
	{
		if ($this->data === []) {
			$this->loadCache();
		}

		if (isset($this->data[$fileName])) {
			return unserialize($this->data[$fileName]);
		}

		$nodeTraverser = new NodeTraverser();
		$nodeTraverser->addVisitor($this->cachingVisitor);

		$contents = FileReader::read($fileName);

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

		$this->data[$fileName] = serialize($result);
		$this->persist();

		return $result;
	}

}
