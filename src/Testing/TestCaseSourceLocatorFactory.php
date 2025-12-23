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
use PHPStan\Reflection\BetterReflection\SourceLocator\OptimizedSingleFileSourceLocatorRepository;
use PHPStan\Reflection\BetterReflection\SourceLocator\PhpVersionBlacklistSourceLocator;
use PHPStan\Reflection\BetterReflection\SourceLocator\SkipPolyfillSourceLocator;
use ReflectionClass;
use function dirname;
use function hash;
use function is_file;
use function serialize;
use const PHP_VERSION_ID;

final class TestCaseSourceLocatorFactory
{

	/** @var array<string, list<SourceLocator>> */
	private static array $composerSourceLocatorsCache = [];

	/**
	 * @param string[] $fileExtensions
	 * @param array{analyse?: array<int, string>, analyseAndScan?: array<int, string>}|null $excludePaths
	 */
	public function __construct(
		private ComposerJsonAndInstalledJsonSourceLocatorMaker $composerJsonAndInstalledJsonSourceLocatorMaker,
		private OptimizedSingleFileSourceLocatorRepository $optimizedSingleFileSourceLocatorRepository,
		private Parser $phpParser,
		private Parser $php8Parser,
		private FileNodesFetcher $fileNodesFetcher,
		private PhpStormStubsSourceStubber $phpstormStubsSourceStubber,
		private ReflectionSourceStubber $reflectionSourceStubber,
		private PhpVersion $phpVersion,
		private array $fileExtensions,
		private ?array $excludePaths,
	)
	{
	}

	public function create(): SourceLocator
	{
		$classLoaders = ClassLoader::getRegisteredLoaders();
		$classLoaderReflection = new ReflectionClass(ClassLoader::class);
		$cacheKey = hash('sha256', serialize([
			$this->phpVersion->getVersionId(),
			$this->fileExtensions,
			$this->excludePaths,
		]));
		if ($classLoaderReflection->hasProperty('vendorDir') && ! isset(self::$composerSourceLocatorsCache[$cacheKey])) {
			$composerLocators = [
				$this->optimizedSingleFileSourceLocatorRepository->getOrCreate(
					PHP_VERSION_ID < 80500
						? __DIR__ . '/../../stubs/runtime/Attribute84.php'
						: __DIR__ . '/../../stubs/runtime/Attribute85.php',
				),
			];
			$vendorDirProperty = $classLoaderReflection->getProperty('vendorDir');
			if (PHP_VERSION_ID < 80100) {
				$vendorDirProperty->setAccessible(true);
			}
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

			self::$composerSourceLocatorsCache[$cacheKey] = [new SkipPolyfillSourceLocator(new AggregateSourceLocator($composerLocators), $this->phpVersion)];
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
