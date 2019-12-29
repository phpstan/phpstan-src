<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc;

use PHPStan\DependencyInjection\Container;
use PHPStan\Reflection\BetterReflection\SourceLocator\OptimizedSingleFileSourceLocatorFactory;
use Roave\BetterReflection\Reflector\FunctionReflector;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\SourceStubber\PhpStormStubsSourceStubber;
use Roave\BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\MemoizingSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\PhpInternalSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\SourceLocator;

class StubSourceLocatorFactory
{

	/** @var \PhpParser\Parser */
	private $parser;

	/** @var PhpStormStubsSourceStubber */
	private $phpStormStubsSourceStubber;

	/** @var \PHPStan\Reflection\BetterReflection\SourceLocator\OptimizedSingleFileSourceLocatorFactory */
	private $optimizedSingleFileSourceLocatorFactory;

	/** @var \PHPStan\DependencyInjection\Container */
	private $container;

	/** @var string[] */
	private $stubFiles;

	/**
	 * @param string[] $stubFiles
	 */
	public function __construct(
		\PhpParser\Parser $parser,
		PhpStormStubsSourceStubber $phpStormStubsSourceStubber,
		OptimizedSingleFileSourceLocatorFactory $optimizedSingleFileSourceLocatorFactory,
		Container $container,
		array $stubFiles
	)
	{
		$this->parser = $parser;
		$this->phpStormStubsSourceStubber = $phpStormStubsSourceStubber;
		$this->optimizedSingleFileSourceLocatorFactory = $optimizedSingleFileSourceLocatorFactory;
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
			$locators[] = $this->optimizedSingleFileSourceLocatorFactory->create($stubFile);
		}

		$locators[] = new PhpInternalSourceLocator($astLocator, $this->phpStormStubsSourceStubber);

		return new MemoizingSourceLocator(new AggregateSourceLocator($locators));
	}

}
