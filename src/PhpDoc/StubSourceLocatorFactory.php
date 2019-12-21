<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc;

use PHPStan\DependencyInjection\Container;
use Roave\BetterReflection\Reflector\FunctionReflector;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\SourceStubber\PhpStormStubsSourceStubber;
use Roave\BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\MemoizingSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\PhpInternalSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\SourceLocator;

class StubSourceLocatorFactory
{

	/** @var \PhpParser\Parser */
	private $parser;

	/** @var \PHPStan\DependencyInjection\Container */
	private $container;

	/** @var string[] */
	private $stubFiles;

	/**
	 * @param string[] $stubFiles
	 */
	public function __construct(
		\PhpParser\Parser $parser,
		Container $container,
		array $stubFiles
	)
	{
		$this->parser = $parser;
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
			$locators[] = new SingleFileSourceLocator(
				$stubFile,
				$astLocator
			);
		}

		$locators[] = new PhpInternalSourceLocator($astLocator, new PhpStormStubsSourceStubber($this->parser));

		return new MemoizingSourceLocator(new AggregateSourceLocator($locators));
	}

}
