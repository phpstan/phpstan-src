<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc;

use PHPStan\DependencyInjection\Container;
use PHPStan\Reflection\BetterReflection\SourceLocator\OptimizedDirectorySourceLocatorFactory;
use Roave\BetterReflection\Reflector\FunctionReflector;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\SourceStubber\PhpStormStubsSourceStubber;
use Roave\BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\MemoizingSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\PhpInternalSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\SourceLocator;

class StubSourceLocatorFactory
{

	private \PhpParser\Parser $parser;

	private PhpStormStubsSourceStubber $phpStormStubsSourceStubber;

	private \PHPStan\Reflection\BetterReflection\SourceLocator\OptimizedDirectorySourceLocatorFactory $optimizedDirectorySourceLocatorFactory;

	private \PHPStan\DependencyInjection\Container $container;

	/** @var string[] */
	private array $stubFiles;

	/**
	 * @param string[] $stubFiles
	 */
	public function __construct(
		\PhpParser\Parser $parser,
		PhpStormStubsSourceStubber $phpStormStubsSourceStubber,
		OptimizedDirectorySourceLocatorFactory $optimizedDirectorySourceLocatorFactory,
		Container $container,
		array $stubFiles
	)
	{
		$this->parser = $parser;
		$this->phpStormStubsSourceStubber = $phpStormStubsSourceStubber;
		$this->optimizedDirectorySourceLocatorFactory = $optimizedDirectorySourceLocatorFactory;
		$this->container = $container;
		$this->stubFiles = $stubFiles;
	}

	public function create(): SourceLocator
	{
		$locators = [];
		$astLocator = new Locator($this->parser, function (): FunctionReflector {
			return $this->container->getService('stubFunctionReflector');
		});
		if (count($this->stubFiles) > 0) {
			$locators[] = $this->optimizedDirectorySourceLocatorFactory->createByFiles($this->stubFiles);
		}

		$locators[] = new PhpInternalSourceLocator($astLocator, $this->phpStormStubsSourceStubber);

		return new MemoizingSourceLocator(new AggregateSourceLocator($locators));
	}

}
