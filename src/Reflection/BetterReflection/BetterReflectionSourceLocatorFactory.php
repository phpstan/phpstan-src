<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection;

use Phar;
use PhpParser\Parser;
use PHPStan\BetterReflection\SourceLocator\Ast\Locator;
use PHPStan\BetterReflection\SourceLocator\SourceStubber\PhpStormStubsSourceStubber;
use PHPStan\BetterReflection\SourceLocator\SourceStubber\ReflectionSourceStubber;
use PHPStan\BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use PHPStan\BetterReflection\SourceLocator\Type\Composer\Psr\Psr4Mapping;
use PHPStan\BetterReflection\SourceLocator\Type\EvaledCodeSourceLocator;
use PHPStan\BetterReflection\SourceLocator\Type\PhpInternalSourceLocator;
use PHPStan\BetterReflection\SourceLocator\Type\SourceLocator;
use PHPStan\Cache\Cache;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\BetterReflection\SourceLocator\AutoloadFunctionsSourceLocator;
use PHPStan\Reflection\BetterReflection\SourceLocator\AutoloadSourceLocator;
use PHPStan\Reflection\BetterReflection\SourceLocator\CachedPhpInternalSourceLocator;
use PHPStan\Reflection\BetterReflection\SourceLocator\ComposerJsonAndInstalledJsonSourceLocatorMaker;
use PHPStan\Reflection\BetterReflection\SourceLocator\FileNodesFetcher;
use PHPStan\Reflection\BetterReflection\SourceLocator\LazySourceLocator;
use PHPStan\Reflection\BetterReflection\SourceLocator\OptimizedDirectorySourceLocatorRepository;
use PHPStan\Reflection\BetterReflection\SourceLocator\OptimizedPsrAutoloaderLocatorFactory;
use PHPStan\Reflection\BetterReflection\SourceLocator\OptimizedSingleFileSourceLocatorRepository;
use PHPStan\Reflection\BetterReflection\SourceLocator\PhpVersionBlacklistSourceLocator;
use PHPStan\Reflection\BetterReflection\SourceLocator\ReflectionClassSourceLocator;
use PHPStan\Reflection\BetterReflection\SourceLocator\RewriteClassAliasSourceLocator;
use PHPStan\Reflection\BetterReflection\SourceLocator\SkipClassAliasSourceLocator;
use PHPStan\Reflection\BetterReflection\SourceLocator\SkipPolyfillSourceLocator;
use PHPStan\Turbo\TurboExtensionEnabler;
use function array_merge;
use function array_unique;
use function count;
use function extension_loaded;
use function is_dir;
use function is_file;
use const PHP_VERSION_ID;

#[AutowiredService]
final class BetterReflectionSourceLocatorFactory
{

	/**
	 * @param string[] $scanFiles
	 * @param string[] $scanDirectories
	 * @param string[] $analysedPaths
	 * @param string[] $composerAutoloaderProjectPaths
	 * @param string[] $analysedPathsFromConfig
	 */
	public function __construct(
		#[AutowiredParameter(ref: '@phpParserDecorator')]
		private Parser $parser,
		#[AutowiredParameter(ref: '@php8PhpParser')]
		private Parser $php8Parser,
		private Cache $cache,
		private PhpVersion $phpVersion,
		private PhpStormStubsSourceStubber $phpstormStubsSourceStubber,
		private SourceStubber\ExtensionVersionProvider $extensionVersionProvider,
		private ReflectionSourceStubber $reflectionSourceStubber,
		private OptimizedSingleFileSourceLocatorRepository $optimizedSingleFileSourceLocatorRepository,
		private OptimizedDirectorySourceLocatorRepository $optimizedDirectorySourceLocatorRepository,
		private ComposerJsonAndInstalledJsonSourceLocatorMaker $composerJsonAndInstalledJsonSourceLocatorMaker,
		private OptimizedPsrAutoloaderLocatorFactory $optimizedPsrAutoloaderLocatorFactory,
		private FileNodesFetcher $fileNodesFetcher,
		#[AutowiredParameter]
		private array $scanFiles,
		#[AutowiredParameter]
		private array $scanDirectories,
		#[AutowiredParameter]
		private array $analysedPaths,
		#[AutowiredParameter]
		private array $composerAutoloaderProjectPaths,
		#[AutowiredParameter]
		private array $analysedPathsFromConfig,
		#[AutowiredParameter(ref: '%sourceLocatorPlaygroundMode%')]
		private bool $playgroundMode, // makes all PHPStan classes in the PHAR discoverable with PSR-4
		#[AutowiredParameter]
		private ?string $singleReflectionFile,
	)
	{
	}

	public function create(): SourceLocator
	{
		$initializer = function () {
			$locators = [
				$this->optimizedSingleFileSourceLocatorRepository->getOrCreate(
					PHP_VERSION_ID < 80500
						? __DIR__ . '/../../../stubs/runtime/Attribute84.php'
						: __DIR__ . '/../../../stubs/runtime/Attribute85.php',
				),
			];

			if ($this->singleReflectionFile !== null) {
				$locators[] = $this->optimizedSingleFileSourceLocatorRepository->getOrCreate($this->singleReflectionFile);
			}

			$astLocator = new Locator($this->parser);

			// Custom autoloaders that bootstrap files registered *before* Composer's
			// class loader (e.g. spl_autoload_register($fn, true, true)) run before
			// Composer resolves the class at runtime, so they are consulted before
			// the static source locators below - mirroring that runtime order.
			$locators[] = new AutoloadFunctionsSourceLocator(
				new AutoloadSourceLocator($this->fileNodesFetcher, false),
				new ReflectionClassSourceLocator(
					$astLocator,
					$this->reflectionSourceStubber,
				),
				true,
			);

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

			$fileLocators = [];
			$analysedFiles = array_unique(array_merge($analysedFiles, $this->scanFiles));
			foreach ($analysedFiles as $analysedFile) {
				$fileLocators[] = $this->optimizedSingleFileSourceLocatorRepository->getOrCreate($analysedFile);
			}

			if (TurboExtensionEnabler::isActive()) {
				// With the turbo extension active, the shadowed class names are
				// declared by the stub shells extending the native classes;
				// reflection has to keep seeing the real implementations, which
				// the AutoloadSourceLocator below can no longer provide (it
				// would resolve the class names to the stub files).
				foreach (TurboExtensionEnabler::getShadowedClassSourceFiles() as $shadowedClassSourceFile) {
					$fileLocators[] = $this->optimizedSingleFileSourceLocatorRepository->getOrCreate($shadowedClassSourceFile);
				}
			}

			$directories = array_unique(array_merge($analysedDirectories, $this->scanDirectories));
			foreach ($directories as $directory) {
				$fileLocators[] = $this->optimizedDirectorySourceLocatorRepository->getOrCreate($directory);
			}

			$astPhp8Locator = new Locator($this->php8Parser);

			$composerLocators = [];

			foreach ($this->composerAutoloaderProjectPaths as $composerAutoloaderProjectPath) {
				$locator = $this->composerJsonAndInstalledJsonSourceLocatorMaker->create($composerAutoloaderProjectPath);
				if ($locator === null) {
					continue;
				}
				$composerLocators[] = $locator;
			}

			if (count($composerLocators) > 0) {
				$fileLocators[] = new SkipPolyfillSourceLocator(new AggregateSourceLocator($composerLocators), $this->phpVersion);
			}

			if (extension_loaded('phar')) {
				$pharProtocolPath = Phar::running();
				if ($pharProtocolPath !== '') {
					$mappings = [
						'PHPStan\\BetterReflection\\' => [$pharProtocolPath . '/vendor/ondrejmirtes/better-reflection/src/'],
					];
					if ($this->playgroundMode) {
						$mappings['PHPStan\\'] = [$pharProtocolPath . '/src/'];
					} else {
						$mappings['PHPStan\\Testing\\'] = [$pharProtocolPath . '/src/Testing/'];
					}
					$fileLocators[] = $this->optimizedPsrAutoloaderLocatorFactory->create(
						Psr4Mapping::fromArrayMappings($mappings),
					);
				}
			}

			$locators[] = new RewriteClassAliasSourceLocator(new AggregateSourceLocator($fileLocators));
			$locators[] = new SkipClassAliasSourceLocator(new CachedPhpInternalSourceLocator(
				new PhpInternalSourceLocator($astPhp8Locator, $this->phpstormStubsSourceStubber),
				$this->cache,
				$this->phpVersion,
				$this->extensionVersionProvider,
			));

			// Custom autoloaders registered *after* Composer's class loader are
			// consulted only as a fallback, after the static locators above
			// (analysed files, Composer class map/PSR-4, PHP internals). At runtime
			// Composer's class loader resolves such classes first, so these custom
			// autoloaders are never invoked for them - invoking them eagerly here
			// would run code paths that cannot happen at runtime. They remain useful
			// for classes that only a custom autoloader can produce (e.g. eval'd).
			$locators[] = new AutoloadFunctionsSourceLocator(
				new AutoloadSourceLocator($this->fileNodesFetcher, false),
				new ReflectionClassSourceLocator(
					$astLocator,
					$this->reflectionSourceStubber,
				),
				false,
			);

			$locators[] = new AutoloadSourceLocator($this->fileNodesFetcher, true);
			$locators[] = new PhpVersionBlacklistSourceLocator(new PhpInternalSourceLocator($astLocator, $this->reflectionSourceStubber), $this->phpstormStubsSourceStubber);
			$locators[] = new PhpVersionBlacklistSourceLocator(new EvaledCodeSourceLocator($astLocator, $this->reflectionSourceStubber), $this->phpstormStubsSourceStubber);

			return new AggregateSourceLocator($locators);
		};

		// no MemoizingSourceLocator here - located reflections are already memoized
		// by MemoizingReflector, a second cache layer would only pin them in memory twice
		return new LazySourceLocator($initializer);
	}

}
