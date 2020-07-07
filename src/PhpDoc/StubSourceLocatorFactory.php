<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc;

use PHPStan\DependencyInjection\Container;
use PHPStan\Reflection\BetterReflection\SourceLocator\OptimizedSingleFileSourceLocatorRepository;
use Roave\BetterReflection\Reflector\FunctionReflector;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\SourceStubber\SourceStubber;
use Roave\BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\MemoizingSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\PhpInternalSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\SourceLocator;

class StubSourceLocatorFactory
{

	private \PhpParser\Parser $parser;

	private SourceStubber $phpStormStubsSourceStubber;

	private \PHPStan\Reflection\BetterReflection\SourceLocator\OptimizedSingleFileSourceLocatorRepository $optimizedSingleFileSourceLocatorRepository;

	private \PHPStan\DependencyInjection\Container $container;

	/** @var string[] */
	private array $stubFiles;

	/**
	 * @param string[] $stubFiles
	 */
	public function __construct(
		\PhpParser\Parser $parser,
		SourceStubber $phpStormStubsSourceStubber,
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
