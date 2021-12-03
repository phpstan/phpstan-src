<?php declare(strict_types = 1);

namespace PHPStan\Testing;

use Composer\Autoload\ClassLoader;
use PHPStan\BetterReflection\SourceLocator\Ast\Locator;
use PHPStan\BetterReflection\SourceLocator\SourceStubber\PhpStormStubsSourceStubber;
use PHPStan\BetterReflection\SourceLocator\SourceStubber\ReflectionSourceStubber;
use PHPStan\BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use PHPStan\BetterReflection\SourceLocator\Type\EvaledCodeSourceLocator;
use PHPStan\BetterReflection\SourceLocator\Type\MemoizingSourceLocator;
use PHPStan\BetterReflection\SourceLocator\Type\PhpInternalSourceLocator;
use PHPStan\BetterReflection\SourceLocator\Type\SourceLocator;
use PHPStan\Reflection\BetterReflection\SourceLocator\AutoloadSourceLocator;
use PHPStan\Reflection\BetterReflection\SourceLocator\ComposerJsonAndInstalledJsonSourceLocatorMaker;
use PHPStan\Reflection\BetterReflection\SourceLocator\PhpVersionBlacklistSourceLocator;

class TestCaseSourceLocatorFactory
{

	private ComposerJsonAndInstalledJsonSourceLocatorMaker $composerJsonAndInstalledJsonSourceLocatorMaker;

	private AutoloadSourceLocator $autoloadSourceLocator;

	private \PhpParser\Parser $phpParser;

	private \PhpParser\Parser $php8Parser;

	private PhpStormStubsSourceStubber $phpstormStubsSourceStubber;

	private ReflectionSourceStubber $reflectionSourceStubber;

	public function __construct(
		ComposerJsonAndInstalledJsonSourceLocatorMaker $composerJsonAndInstalledJsonSourceLocatorMaker,
		AutoloadSourceLocator $autoloadSourceLocator,
		\PhpParser\Parser $phpParser,
		\PhpParser\Parser $php8Parser,
		PhpStormStubsSourceStubber $phpstormStubsSourceStubber,
		ReflectionSourceStubber $reflectionSourceStubber
	)
	{
		$this->composerJsonAndInstalledJsonSourceLocatorMaker = $composerJsonAndInstalledJsonSourceLocatorMaker;
		$this->autoloadSourceLocator = $autoloadSourceLocator;
		$this->phpParser = $phpParser;
		$this->php8Parser = $php8Parser;
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
		$astLocator = new Locator($this->phpParser);
		$astPhp8Locator = new Locator($this->php8Parser);

		$locators[] = new PhpInternalSourceLocator($astPhp8Locator, $this->phpstormStubsSourceStubber);
		$locators[] = $this->autoloadSourceLocator;
		$locators[] = new PhpVersionBlacklistSourceLocator(new PhpInternalSourceLocator($astLocator, $this->reflectionSourceStubber), $this->phpstormStubsSourceStubber);
		$locators[] = new PhpVersionBlacklistSourceLocator(new EvaledCodeSourceLocator($astLocator, $this->reflectionSourceStubber), $this->phpstormStubsSourceStubber);

		return new MemoizingSourceLocator(new AggregateSourceLocator($locators));
	}

}
