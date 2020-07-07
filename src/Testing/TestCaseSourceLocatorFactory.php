<?php declare(strict_types = 1);

namespace PHPStan\Testing;

use Composer\Autoload\ClassLoader;
use PHPStan\DependencyInjection\Container;
use PHPStan\Reflection\BetterReflection\SourceLocator\AutoloadSourceLocator;
use PHPStan\Reflection\BetterReflection\SourceLocator\ComposerJsonAndInstalledJsonSourceLocatorMaker;
use Roave\BetterReflection\Reflector\FunctionReflector;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\SourceStubber\SourceStubber;
use Roave\BetterReflection\SourceLocator\SourceStubber\ReflectionSourceStubber;
use Roave\BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\EvaledCodeSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\MemoizingSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\PhpInternalSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\SourceLocator;

class TestCaseSourceLocatorFactory
{

	private Container $container;

	private ComposerJsonAndInstalledJsonSourceLocatorMaker $composerJsonAndInstalledJsonSourceLocatorMaker;

	private AutoloadSourceLocator $autoloadSourceLocator;

	private \PhpParser\Parser $phpParser;

	private SourceStubber $phpstormStubsSourceStubber;

	private ReflectionSourceStubber $reflectionSourceStubber;

	public function __construct(
		Container $container,
		ComposerJsonAndInstalledJsonSourceLocatorMaker $composerJsonAndInstalledJsonSourceLocatorMaker,
		AutoloadSourceLocator $autoloadSourceLocator,
		\PhpParser\Parser $phpParser,
		SourceStubber $phpstormStubsSourceStubber,
		ReflectionSourceStubber $reflectionSourceStubber
	)
	{
		$this->container = $container;
		$this->composerJsonAndInstalledJsonSourceLocatorMaker = $composerJsonAndInstalledJsonSourceLocatorMaker;
		$this->autoloadSourceLocator = $autoloadSourceLocator;
		$this->phpParser = $phpParser;
		$this->phpstormStubsSourceStubber = $phpstormStubsSourceStubber;
		$this->reflectionSourceStubber = $reflectionSourceStubber;
	}

	public function create(): SourceLocator
	{
		$classLoaderReflection = new \ReflectionClass(ClassLoader::class);
		if ($classLoaderReflection->getFileName() === false) {
			throw new \PHPStan\ShouldNotHappenException('Unknown ClassLoader filename');
		}

		$composerProjectPath = dirname($classLoaderReflection->getFileName(), 3);
		if (!is_file($composerProjectPath . '/composer.json')) {
			throw new \PHPStan\ShouldNotHappenException(sprintf('composer.json not found in directory %s', $composerProjectPath));
		}

		$composerSourceLocator = $this->composerJsonAndInstalledJsonSourceLocatorMaker->create($composerProjectPath);
		if ($composerSourceLocator === null) {
			throw new \PHPStan\ShouldNotHappenException('Could not create composer source locator');
		}

		$locators = [
			$composerSourceLocator,
		];
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
