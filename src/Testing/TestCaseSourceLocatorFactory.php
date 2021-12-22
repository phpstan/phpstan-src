<?php declare(strict_types = 1);

namespace PHPStan\Testing;

use Composer\Autoload\ClassLoader;
use PhpParser\Parser;
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
use PHPStan\ShouldNotHappenException;
use ReflectionClass;
use function dirname;
use function is_file;
use function sprintf;

class TestCaseSourceLocatorFactory
{

	private ComposerJsonAndInstalledJsonSourceLocatorMaker $composerJsonAndInstalledJsonSourceLocatorMaker;

	private AutoloadSourceLocator $autoloadSourceLocator;

	private Parser $phpParser;

	private Parser $php8Parser;

	private PhpStormStubsSourceStubber $phpstormStubsSourceStubber;

	private ReflectionSourceStubber $reflectionSourceStubber;

	public function __construct(
		ComposerJsonAndInstalledJsonSourceLocatorMaker $composerJsonAndInstalledJsonSourceLocatorMaker,
		AutoloadSourceLocator $autoloadSourceLocator,
		Parser $phpParser,
		Parser $php8Parser,
		PhpStormStubsSourceStubber $phpstormStubsSourceStubber,
		ReflectionSourceStubber $reflectionSourceStubber,
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
		$classLoaders = ClassLoader::getRegisteredLoaders();
		$classLoaderReflection = new ReflectionClass(ClassLoader::class);
		$locators = [];
		if ($classLoaderReflection->hasProperty('vendorDir')) {
			$vendorDirProperty = $classLoaderReflection->getProperty('vendorDir');
			$vendorDirProperty->setAccessible(true);
			foreach ($classLoaders as $classLoader) {
				$composerProjectPath = dirname($vendorDirProperty->getValue($classLoader));
				if (!is_file($composerProjectPath . '/composer.json')) {
					throw new ShouldNotHappenException(sprintf('composer.json not found in directory %s', $composerProjectPath));
				}

				$composerSourceLocator = $this->composerJsonAndInstalledJsonSourceLocatorMaker->create($composerProjectPath);
				if ($composerSourceLocator === null) {
					throw new ShouldNotHappenException('Could not create composer source locator');
				}
				$locators[] = $composerSourceLocator;
			}
		}

		$astLocator = new Locator($this->phpParser);
		$astPhp8Locator = new Locator($this->php8Parser);

		$locators[] = new PhpInternalSourceLocator($astPhp8Locator, $this->phpstormStubsSourceStubber);
		$locators[] = $this->autoloadSourceLocator;
		$locators[] = new PhpVersionBlacklistSourceLocator(new PhpInternalSourceLocator($astLocator, $this->reflectionSourceStubber), $this->phpstormStubsSourceStubber);
		$locators[] = new PhpVersionBlacklistSourceLocator(new EvaledCodeSourceLocator($astLocator, $this->reflectionSourceStubber), $this->phpstormStubsSourceStubber);

		return new MemoizingSourceLocator(new AggregateSourceLocator($locators));
	}

}
