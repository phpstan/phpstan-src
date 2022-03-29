<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc;

use PhpParser\Parser;
use PHPStan\BetterReflection\SourceLocator\Ast\Locator;
use PHPStan\BetterReflection\SourceLocator\SourceStubber\PhpStormStubsSourceStubber;
use PHPStan\BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use PHPStan\BetterReflection\SourceLocator\Type\MemoizingSourceLocator;
use PHPStan\BetterReflection\SourceLocator\Type\PhpInternalSourceLocator;
use PHPStan\BetterReflection\SourceLocator\Type\SourceLocator;
use PHPStan\Reflection\BetterReflection\SourceLocator\CachingVisitor;
use PHPStan\Reflection\BetterReflection\SourceLocator\FileNodesFetcher;
use PHPStan\Reflection\BetterReflection\SourceLocator\OptimizedSingleFileSourceLocator;

class StubSourceLocatorFactory
{

	/**
	 * @param string[] $stubFiles
	 */
	public function __construct(
		private Parser $php8PhpParser,
		private \PHPStan\Parser\Parser $php8Parser,
		private CachingVisitor $cachingVisitor,
		private PhpStormStubsSourceStubber $phpStormStubsSourceStubber,
		private array $stubFiles,
	)
	{
	}

	public function create(): SourceLocator
	{
		$locators = [];
		$astPhp8Locator = new Locator($this->php8PhpParser);
		$fetcher = new FileNodesFetcher($this->cachingVisitor, $this->php8Parser);
		foreach ($this->stubFiles as $stubFile) {
			$locators[] = new OptimizedSingleFileSourceLocator($fetcher, $stubFile);
		}

		$locators[] = new PhpInternalSourceLocator($astPhp8Locator, $this->phpStormStubsSourceStubber);

		return new MemoizingSourceLocator(new AggregateSourceLocator($locators));
	}

}
