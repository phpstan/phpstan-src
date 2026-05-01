<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection;

use Phar;
use PhpParser\Parser;
use PHPStan\BetterReflection\Identifier\Identifier;
use PHPStan\BetterReflection\Identifier\IdentifierType;
use PHPStan\BetterReflection\Reflector\Reflector;
use PHPStan\BetterReflection\SourceLocator\Ast\Locator;
use PHPStan\BetterReflection\SourceLocator\SourceStubber\PhpStormStubsSourceStubber;
use PHPStan\BetterReflection\SourceLocator\SourceStubber\ReflectionSourceStubber;
use PHPStan\BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use PHPStan\BetterReflection\SourceLocator\Type\Composer\Psr\Psr4Mapping;
use PHPStan\BetterReflection\SourceLocator\Type\EvaledCodeSourceLocator;
use PHPStan\BetterReflection\SourceLocator\Type\MemoizingSourceLocator;
use PHPStan\BetterReflection\SourceLocator\Type\PhpInternalSourceLocator;
use PHPStan\BetterReflection\SourceLocator\Type\SourceLocator;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\BetterReflection\SourceLocator\AutoloadFunctionsSourceLocator;
use PHPStan\Reflection\BetterReflection\SourceLocator\AutoloadSourceLocator;
use PHPStan\Reflection\BetterReflection\SourceLocator\ComposerJsonAndInstalledJsonSourceLocatorMaker;
use PHPStan\Reflection\BetterReflection\SourceLocator\FileNodesFetcher;
use PHPStan\Reflection\BetterReflection\SourceLocator\OptimizedDirectorySourceLocatorRepository;
use PHPStan\Reflection\BetterReflection\SourceLocator\OptimizedPsrAutoloaderLocatorFactory;
use PHPStan\Reflection\BetterReflection\SourceLocator\OptimizedSingleFileSourceLocatorRepository;
use PHPStan\Reflection\BetterReflection\SourceLocator\PhpVersionBlacklistSourceLocator;
use PHPStan\Reflection\BetterReflection\SourceLocator\ReflectionClassSourceLocator;
use PHPStan\Reflection\BetterReflection\SourceLocator\RewriteClassAliasSourceLocator;
use PHPStan\Reflection\BetterReflection\SourceLocator\SkipClassAliasSourceLocator;
use PHPStan\Reflection\BetterReflection\SourceLocator\SkipPolyfillSourceLocator;
use stdClass;
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
		private PhpVersion $phpVersion,
		private PhpStormStubsSourceStubber $phpstormStubsSourceStubber,
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
		$initializer = function() {
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
			$locators[] = new AutoloadFunctionsSourceLocator(
				new AutoloadSourceLocator($this->fileNodesFetcher, false),
				new ReflectionClassSourceLocator(
					$astLocator,
					$this->reflectionSourceStubber,
				),
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
			$locators[] = new SkipClassAliasSourceLocator(new PhpInternalSourceLocator($astPhp8Locator, $this->phpstormStubsSourceStubber));

			$locators[] = new AutoloadSourceLocator($this->fileNodesFetcher, true);
			$locators[] = new PhpVersionBlacklistSourceLocator(new PhpInternalSourceLocator($astLocator, $this->reflectionSourceStubber), $this->phpstormStubsSourceStubber);
			$locators[] = new PhpVersionBlacklistSourceLocator(new EvaledCodeSourceLocator($astLocator, $this->reflectionSourceStubber), $this->phpstormStubsSourceStubber);

			return new AggregateSourceLocator($locators);
		};

		$lazyLocator = new class($initializer) implements SourceLocator {
			private ?SourceLocator $wrappedSourceLocator = null;

			/**
			 * @var callable():SourceLocator
			 */
			private $initializer;

			/**
			 * @param callable():SourceLocator $initializer
			 */
			public function __construct(callable $initializer)
			{
				$this->initializer = $initializer;
			}

			private function lazyInitialize(): SourceLocator {
				if ($this->wrappedSourceLocator === null) {
					$this->wrappedSourceLocator = ($this->initializer)();
				}
				return $this->wrappedSourceLocator;
			}

			public function locateIdentifier(Reflector $reflector, Identifier $identifier): ?\PHPStan\BetterReflection\Reflection\Reflection
			{
				return $this->lazyInitialize()->locateIdentifier($reflector, $identifier);
			}

			public function locateIdentifiersByType(Reflector $reflector, IdentifierType $identifierType): array
			{
				return $this->lazyInitialize()->locateIdentifiersByType($reflector, $identifierType);
			}
		};

		return new MemoizingSourceLocator($lazyLocator);
	}

}
