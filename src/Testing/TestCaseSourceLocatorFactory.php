<?php declare(strict_types = 1);

namespace PHPStan\Testing;

use PHPStan\DependencyInjection\Container;
use PHPStan\Reflection\BetterReflection\SourceLocator\AutoloadSourceLocator;
use Roave\BetterReflection\Reflector\FunctionReflector;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\SourceStubber\PhpStormStubsSourceStubber;
use Roave\BetterReflection\SourceLocator\SourceStubber\ReflectionSourceStubber;
use Roave\BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\EvaledCodeSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\MemoizingSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\PhpInternalSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\SourceLocator;

class TestCaseSourceLocatorFactory
{

	private Container $container;

	private AutoloadSourceLocator $autoloadSourceLocator;

	private \PhpParser\Parser $phpParser;

	private PhpStormStubsSourceStubber $phpstormStubsSourceStubber;

	private ReflectionSourceStubber $reflectionSourceStubber;

	public function __construct(
		Container $container,
		AutoloadSourceLocator $autoloadSourceLocator,
		\PhpParser\Parser $phpParser,
		PhpStormStubsSourceStubber $phpstormStubsSourceStubber,
		ReflectionSourceStubber $reflectionSourceStubber
	)
	{
		$this->container = $container;
		$this->autoloadSourceLocator = $autoloadSourceLocator;
		$this->phpParser = $phpParser;
		$this->phpstormStubsSourceStubber = $phpstormStubsSourceStubber;
		$this->reflectionSourceStubber = $reflectionSourceStubber;
	}

	public function create(): SourceLocator
	{
		$locators = [];
		$astLocator = new Locator($this->phpParser, function (): FunctionReflector {
			return $this->container->getService('testCaseFunctionReflector');
		});

		$locators[] = new PhpInternalSourceLocator($astLocator, $this->phpstormStubsSourceStubber);
		$locators[] = $this->autoloadSourceLocator;
		$locators[] = new PhpInternalSourceLocator($astLocator, $this->reflectionSourceStubber);
		$locators[] = new EvaledCodeSourceLocator($astLocator, $this->reflectionSourceStubber);

		return new MemoizingSourceLocator(new AggregateSourceLocator($locators));
	}

}
