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
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\BetterReflection\SourceLocator\AutoloadSourceLocator;
use PHPStan\Reflection\BetterReflection\SourceLocator\ComposerJsonAndInstalledJsonSourceLocatorMaker;
use PHPStan\Reflection\BetterReflection\SourceLocator\FileNodesFetcher;
use PHPStan\Reflection\BetterReflection\SourceLocator\PhpVersionBlacklistSourceLocator;
use ReflectionClass;
use function dirname;
use function is_file;
use function serialize;
use function sha1;

final class TestCaseSourceLocatorFactory
{

	/** @var array<string, list<SourceLocator>> */
	private static array $composerSourceLocatorsCache = [];

	/**
	 * @param string[] $fileExtensions
	 * @param string[] $obsoleteExcludesAnalyse
	 * @param array{analyse?: array<int, string>, analyseAndScan?: array<int, string>}|null $excludePaths
	 */
	public function __construct(
		private ComposerJsonAndInstalledJsonSourceLocatorMaker $composerJsonAndInstalledJsonSourceLocatorMaker,
		private Parser $phpParser,
		private Parser $php8Parser,
		private FileNodesFetcher $fileNodesFetcher,
		private PhpStormStubsSourceStubber $phpstormStubsSourceStubber,
		private ReflectionSourceStubber $reflectionSourceStubber,
		private PhpVersion $phpVersion,
		private array $fileExtensions,
		private array $obsoleteExcludesAnalyse,
		private ?array $excludePaths,
	)
	{
	}

	public function create(): SourceLocator
	{
		$classLoaders = ClassLoader::getRegisteredLoaders();
		$classLoaderReflection = new ReflectionClass(ClassLoader::class);
		$cacheKey = sha1(serialize([
			$this->phpVersion->getVersionId(),
			$this->fileExtensions,
			$this->obsoleteExcludesAnalyse,
			$this->excludePaths,
		]));
		if ($classLoaderReflection->hasProperty('vendorDir') && ! isset(self::$composerSourceLocatorsCache[$cacheKey])) {
			$composerLocators = [];
			$vendorDirProperty = $classLoaderReflection->getProperty('vendorDir');
			$vendorDirProperty->setAccessible(true);
			foreach ($classLoaders as $classLoader) {
				$composerProjectPath = dirname($vendorDirProperty->getValue($classLoader));
				if (!is_file($composerProjectPath . '/composer.json')) {
					continue;
				}

				$composerSourceLocator = $this->composerJsonAndInstalledJsonSourceLocatorMaker->create($composerProjectPath);
				if ($composerSourceLocator === null) {
					continue;
				}
				$composerLocators[] = $composerSourceLocator;
			}

			self::$composerSourceLocatorsCache[$cacheKey] = $composerLocators;
		}

		$locators = self::$composerSourceLocatorsCache[$cacheKey] ?? [];
		$astLocator = new Locator($this->phpParser);
		$astPhp8Locator = new Locator($this->php8Parser);

		$locators[] = new PhpInternalSourceLocator($astPhp8Locator, $this->phpstormStubsSourceStubber);
		$locators[] = new AutoloadSourceLocator($this->fileNodesFetcher, true);
		$locators[] = new PhpVersionBlacklistSourceLocator(new PhpInternalSourceLocator($astLocator, $this->reflectionSourceStubber), $this->phpstormStubsSourceStubber);
		$locators[] = new PhpVersionBlacklistSourceLocator(new EvaledCodeSourceLocator($astLocator, $this->reflectionSourceStubber), $this->phpstormStubsSourceStubber);

		return new MemoizingSourceLocator(new AggregateSourceLocator($locators));
	}

}
