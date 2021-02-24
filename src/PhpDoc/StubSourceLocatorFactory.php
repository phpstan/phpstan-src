<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc;

use PHPStan\BetterReflection\Reflector\FunctionReflector;
use PHPStan\BetterReflection\SourceLocator\Ast\Locator;
use PHPStan\BetterReflection\SourceLocator\SourceStubber\PhpStormStubsSourceStubber;
use PHPStan\BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use PHPStan\BetterReflection\SourceLocator\Type\MemoizingSourceLocator;
use PHPStan\BetterReflection\SourceLocator\Type\PhpInternalSourceLocator;
use PHPStan\BetterReflection\SourceLocator\Type\SourceLocator;
use PHPStan\DependencyInjection\Container;
use PHPStan\Reflection\BetterReflection\SourceLocator\OptimizedSingleFileSourceLocatorRepository;

class StubSourceLocatorFactory
{

	private \PhpParser\Parser $parser;

	private PhpStormStubsSourceStubber $phpStormStubsSourceStubber;

	private \PHPStan\Reflection\BetterReflection\SourceLocator\OptimizedSingleFileSourceLocatorRepository $optimizedSingleFileSourceLocatorRepository;

	private \PHPStan\DependencyInjection\Container $container;

	/** @var string[] */
	private array $stubFiles;

	/**
	 * @param string[] $stubFiles
	 */
	public function __construct(
		\PhpParser\Parser $parser,
		PhpStormStubsSourceStubber $phpStormStubsSourceStubber,
		OptimizedSingleFileSourceLocatorRepository $optimizedSingleFileSourceLocatorRepository,
		Container $container,
		array $stubFiles
	)
	{
		$this->parser = $parser;
		$this->phpStormStubsSourceStubber = $phpStormStubsSourceStubber;
		$this->optimizedSingleFileSourceLocatorRepository = $optimizedSingleFileSourceLocatorRepository;
		$this->container = $container;
		$this->stubFiles = $stubFiles;
	}

	public function create(): SourceLocator
	{
		$locators = [];
		$astLocator = new Locator($this->parser, function (): FunctionReflector {
			return $this->container->getService('stubFunctionReflector');
		});
		foreach ($this->stubFiles as $stubFile) {
			$locators[] = $this->optimizedSingleFileSourceLocatorRepository->getOrCreate($stubFile);
		}

		$locators[] = new PhpInternalSourceLocator($astLocator, $this->phpStormStubsSourceStubber);

		return new MemoizingSourceLocator(new AggregateSourceLocator($locators));
	}

}
