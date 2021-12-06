<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc;

use PhpParser\Parser;
use PHPStan\BetterReflection\SourceLocator\Ast\Locator;
use PHPStan\BetterReflection\SourceLocator\SourceStubber\PhpStormStubsSourceStubber;
use PHPStan\BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use PHPStan\BetterReflection\SourceLocator\Type\MemoizingSourceLocator;
use PHPStan\BetterReflection\SourceLocator\Type\PhpInternalSourceLocator;
use PHPStan\BetterReflection\SourceLocator\Type\SourceLocator;
use PHPStan\Reflection\BetterReflection\SourceLocator\OptimizedSingleFileSourceLocatorRepository;

class StubSourceLocatorFactory
{

	private Parser $php8Parser;

	private PhpStormStubsSourceStubber $phpStormStubsSourceStubber;

	private OptimizedSingleFileSourceLocatorRepository $optimizedSingleFileSourceLocatorRepository;

	/** @var string[] */
	private array $stubFiles;

	/**
	 * @param string[] $stubFiles
	 */
	public function __construct(
		Parser $php8Parser,
		PhpStormStubsSourceStubber $phpStormStubsSourceStubber,
		OptimizedSingleFileSourceLocatorRepository $optimizedSingleFileSourceLocatorRepository,
		array $stubFiles
	)
	{
		$this->php8Parser = $php8Parser;
		$this->phpStormStubsSourceStubber = $phpStormStubsSourceStubber;
		$this->optimizedSingleFileSourceLocatorRepository = $optimizedSingleFileSourceLocatorRepository;
		$this->stubFiles = $stubFiles;
	}

	public function create(): SourceLocator
	{
		$locators = [];
		$astPhp8Locator = new Locator($this->php8Parser);
		foreach ($this->stubFiles as $stubFile) {
			$locators[] = $this->optimizedSingleFileSourceLocatorRepository->getOrCreate($stubFile);
		}

		$locators[] = new PhpInternalSourceLocator($astPhp8Locator, $this->phpStormStubsSourceStubber);

		return new MemoizingSourceLocator(new AggregateSourceLocator($locators));
	}

}
