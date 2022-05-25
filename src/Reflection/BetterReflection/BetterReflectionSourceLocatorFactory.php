<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection;

use Composer\Autoload\ClassLoader;
use Phar;
use PhpParser\Parser;
use PHPStan\BetterReflection\SourceLocator\Ast\Locator;
use PHPStan\BetterReflection\SourceLocator\SourceStubber\PhpStormStubsSourceStubber;
use PHPStan\BetterReflection\SourceLocator\SourceStubber\ReflectionSourceStubber;
use PHPStan\BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use PHPStan\BetterReflection\SourceLocator\Type\Composer\Psr\Psr4Mapping;
use PHPStan\BetterReflection\SourceLocator\Type\EvaledCodeSourceLocator;
use PHPStan\BetterReflection\SourceLocator\Type\MemoizingSourceLocator;
use PHPStan\BetterReflection\SourceLocator\Type\PhpInternalSourceLocator;
use PHPStan\BetterReflection\SourceLocator\Type\SourceLocator;
use PHPStan\Reflection\BetterReflection\SourceLocator\AutoloadSourceLocator;
use PHPStan\Reflection\BetterReflection\SourceLocator\ComposerJsonAndInstalledJsonSourceLocatorMaker;
use PHPStan\Reflection\BetterReflection\SourceLocator\OptimizedDirectorySourceLocatorRepository;
use PHPStan\Reflection\BetterReflection\SourceLocator\OptimizedPsrAutoloaderLocatorFactory;
use PHPStan\Reflection\BetterReflection\SourceLocator\OptimizedSingleFileSourceLocatorRepository;
use PHPStan\Reflection\BetterReflection\SourceLocator\PhpVersionBlacklistSourceLocator;
use PHPStan\Reflection\BetterReflection\SourceLocator\SkipClassAliasSourceLocator;
use ReflectionClass;
use function array_merge;
use function array_unique;
use function dirname;
use function extension_loaded;
use function is_dir;
use function is_file;

class BetterReflectionSourceLocatorFactory
{

	/**
	 * @param string[] $scanFiles
	 * @param string[] $scanDirectories
	 * @param string[] $analysedPaths
	 * @param string[] $composerAutoloaderProjectPaths
	 * @param string[] $analysedPathsFromConfig
	 */
	public function __construct(
		private Parser $parser,
		private Parser $php8Parser,
		private PhpStormStubsSourceStubber $phpstormStubsSourceStubber,
		private ReflectionSourceStubber $reflectionSourceStubber,
		private OptimizedSingleFileSourceLocatorRepository $optimizedSingleFileSourceLocatorRepository,
		private OptimizedDirectorySourceLocatorRepository $optimizedDirectorySourceLocatorRepository,
		private ComposerJsonAndInstalledJsonSourceLocatorMaker $composerJsonAndInstalledJsonSourceLocatorMaker,
		private AutoloadSourceLocator $autoloadSourceLocator,
		private OptimizedPsrAutoloaderLocatorFactory $optimizedPsrAutoloaderLocatorFactory,
		private array $scanFiles,
		private array $scanDirectories,
		private array $analysedPaths,
		private array $composerAutoloaderProjectPaths,
		private array $analysedPathsFromConfig,
		private ?string $singleReflectionFile,
	)
	{
	}

	public function create(): SourceLocator
	{
		$locators = [];

		if ($this->singleReflectionFile !== null) {
			$locators[] = $this->optimizedSingleFileSourceLocatorRepository->getOrCreate($this->singleReflectionFile);
		}

		$analysedDirectories = [];
		$analysedFiles = [];

		foreach (array_merge($this->analysedPaths, $this->analysedPathsFromConfig) as $analysedPath) {
			if (is_file($analysedPath)) {
				$analysedFiles[] = $analysedPath;
				continue;
			}

			if (!is_dir($analysedPath)) {
				continue;
			}

			$analysedDirectories[] = $analysedPath;
		}

		$analysedFiles = array_unique(array_merge($analysedFiles, $this->scanFiles));
		foreach ($analysedFiles as $analysedFile) {
			$locators[] = $this->optimizedSingleFileSourceLocatorRepository->getOrCreate($analysedFile);
		}

		$directories = array_unique(array_merge($analysedDirectories, $this->scanDirectories));
		foreach ($directories as $directory) {
			$locators[] = $this->optimizedDirectorySourceLocatorRepository->getOrCreate($directory);
		}

		$astLocator = new Locator($this->parser);
		$astPhp8Locator = new Locator($this->php8Parser);

		$locators[] = new SkipClassAliasSourceLocator(new PhpInternalSourceLocator($astPhp8Locator, $this->phpstormStubsSourceStubber));

		$classLoaders = ClassLoader::getRegisteredLoaders();
		$classLoaderReflection = new ReflectionClass(ClassLoader::class);
		$locators = [];
		if ($classLoaderReflection->hasProperty('vendorDir')) {
			$vendorDirProperty = $classLoaderReflection->getProperty('vendorDir');
			$vendorDirProperty->setAccessible(true);
			foreach ($classLoaders as $classLoader) {
				$composerProjectPath = dirname($vendorDirProperty->getValue($classLoader));
				if ($composerProjectPath === dirname(__DIR__, 3)) {
					continue;
				}
				var_dump($composerProjectPath);
				if (!is_file($composerProjectPath . '/composer.json')) {
					continue;
				}

				$composerSourceLocator = $this->composerJsonAndInstalledJsonSourceLocatorMaker->create($composerProjectPath);
				if ($composerSourceLocator === null) {
					continue;
				}
				$locators[] = $composerSourceLocator;
			}
		}

		/*foreach ($this->composerAutoloaderProjectPaths as $composerAutoloaderProjectPath) {
			$locator = $this->composerJsonAndInstalledJsonSourceLocatorMaker->create($composerAutoloaderProjectPath);
			if ($locator === null) {
				continue;
			}
			$locators[] = $locator;
		}*/

		if (extension_loaded('phar')) {
			$pharProtocolPath = Phar::running();
			if ($pharProtocolPath !== '') {
				$locators[] = $this->optimizedPsrAutoloaderLocatorFactory->create(
					Psr4Mapping::fromArrayMappings([
						'PHPStan\\Testing\\' => [$pharProtocolPath . '/src/Testing/'],
					]),
				);
			}
		}

		$locators[] = $this->autoloadSourceLocator;
		$locators[] = new PhpVersionBlacklistSourceLocator(new PhpInternalSourceLocator($astLocator, $this->reflectionSourceStubber), $this->phpstormStubsSourceStubber);
		$locators[] = new PhpVersionBlacklistSourceLocator(new EvaledCodeSourceLocator($astLocator, $this->reflectionSourceStubber), $this->phpstormStubsSourceStubber);

		return new MemoizingSourceLocator(new AggregateSourceLocator($locators));
	}

}
