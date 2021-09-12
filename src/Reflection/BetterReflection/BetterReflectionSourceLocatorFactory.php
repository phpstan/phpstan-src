<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection;

use PHPStan\DependencyInjection\Container;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\BetterReflection\SourceLocator\AutoloadSourceLocator;
use PHPStan\Reflection\BetterReflection\SourceLocator\ClassBlacklistSourceLocator;
use PHPStan\Reflection\BetterReflection\SourceLocator\ClassWhitelistSourceLocator;
use PHPStan\Reflection\BetterReflection\SourceLocator\ComposerJsonAndInstalledJsonSourceLocatorMaker;
use PHPStan\Reflection\BetterReflection\SourceLocator\OptimizedDirectorySourceLocatorRepository;
use PHPStan\Reflection\BetterReflection\SourceLocator\OptimizedSingleFileSourceLocatorRepository;
use PHPStan\Reflection\BetterReflection\SourceLocator\PhpVersionBlacklistSourceLocator;
use PHPStan\Reflection\BetterReflection\SourceLocator\SkipClassAliasSourceLocator;
use PHPStan\BetterReflection\Reflector\FunctionReflector;
use PHPStan\BetterReflection\SourceLocator\Ast\Locator;
use PHPStan\BetterReflection\SourceLocator\SourceStubber\PhpStormStubsSourceStubber;
use PHPStan\BetterReflection\SourceLocator\SourceStubber\ReflectionSourceStubber;
use PHPStan\BetterReflection\SourceLocator\SourceStubber\SourceStubber;
use PHPStan\BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use PHPStan\BetterReflection\SourceLocator\Type\EvaledCodeSourceLocator;
use PHPStan\BetterReflection\SourceLocator\Type\MemoizingSourceLocator;
use PHPStan\BetterReflection\SourceLocator\Type\PhpInternalSourceLocator;
use PHPStan\BetterReflection\SourceLocator\Type\SourceLocator;

class BetterReflectionSourceLocatorFactory
{

	/** @var \PhpParser\Parser */
	private $parser;

	/** @var \PhpParser\Parser */
	private $php8Parser;

	/** @var PhpStormStubsSourceStubber */
	private $phpstormStubsSourceStubber;

	/** @var ReflectionSourceStubber */
	private $reflectionSourceStubber;

	/** @var \PHPStan\Reflection\BetterReflection\SourceLocator\OptimizedSingleFileSourceLocatorRepository */
	private $optimizedSingleFileSourceLocatorRepository;

	/** @var \PHPStan\Reflection\BetterReflection\SourceLocator\OptimizedDirectorySourceLocatorRepository */
	private $optimizedDirectorySourceLocatorRepository;

	/** @var ComposerJsonAndInstalledJsonSourceLocatorMaker */
	private $composerJsonAndInstalledJsonSourceLocatorMaker;

	/** @var AutoloadSourceLocator */
	private $autoloadSourceLocator;

	/** @var \PHPStan\DependencyInjection\Container */
	private $container;

	/** @var string[] */
	private $autoloadDirectories;

	/** @var string[] */
	private $scanFiles;

	/** @var string[] */
	private $scanDirectories;

	/** @var string[] */
	private $analysedPaths;

	/** @var string[] */
	private $composerAutoloaderProjectPaths;

	/** @var string[] */
	private $analysedPathsFromConfig;

	/** @var string|null */
	private $singleReflectionFile;

	/** @var string[] */
	private array $staticReflectionClassNamePatterns;

	/**
	 * @param string[] $autoloadDirectories
	 * @param string[] $autoloadFiles
	 * @param string[] $scanFiles
	 * @param string[] $scanDirectories
	 * @param string[] $analysedPaths
	 * @param string[] $composerAutoloaderProjectPaths
	 * @param string[] $analysedPathsFromConfig
	 * @param string|null $singleReflectionFile,
	 * @param string[] $staticReflectionClassNamePatterns
	 */
	public function __construct(
		\PhpParser\Parser $parser,
		\PhpParser\Parser $php8Parser,
		PhpStormStubsSourceStubber $phpstormStubsSourceStubber,
		ReflectionSourceStubber $reflectionSourceStubber,
		OptimizedSingleFileSourceLocatorRepository $optimizedSingleFileSourceLocatorRepository,
		OptimizedDirectorySourceLocatorRepository $optimizedDirectorySourceLocatorRepository,
		ComposerJsonAndInstalledJsonSourceLocatorMaker $composerJsonAndInstalledJsonSourceLocatorMaker,
		AutoloadSourceLocator $autoloadSourceLocator,
		Container $container,
		array $autoloadDirectories,
		array $scanFiles,
		array $scanDirectories,
		array $analysedPaths,
		array $composerAutoloaderProjectPaths,
		array $analysedPathsFromConfig,
		?string $singleReflectionFile,
		array $staticReflectionClassNamePatterns
	)
	{
		$this->parser = $parser;
		$this->php8Parser = $php8Parser;
		$this->phpstormStubsSourceStubber = $phpstormStubsSourceStubber;
		$this->reflectionSourceStubber = $reflectionSourceStubber;
		$this->optimizedSingleFileSourceLocatorRepository = $optimizedSingleFileSourceLocatorRepository;
		$this->optimizedDirectorySourceLocatorRepository = $optimizedDirectorySourceLocatorRepository;
		$this->composerJsonAndInstalledJsonSourceLocatorMaker = $composerJsonAndInstalledJsonSourceLocatorMaker;
		$this->autoloadSourceLocator = $autoloadSourceLocator;
		$this->container = $container;
		$this->autoloadDirectories = $autoloadDirectories;
		$this->scanFiles = $scanFiles;
		$this->scanDirectories = $scanDirectories;
		$this->analysedPaths = $analysedPaths;
		$this->composerAutoloaderProjectPaths = $composerAutoloaderProjectPaths;
		$this->analysedPathsFromConfig = $analysedPathsFromConfig;
		$this->singleReflectionFile = $singleReflectionFile;
		$this->staticReflectionClassNamePatterns = $staticReflectionClassNamePatterns;
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

		$directories = array_unique(array_merge($analysedDirectories, $this->autoloadDirectories, $this->scanDirectories));
		foreach ($directories as $directory) {
			$locators[] = $this->optimizedDirectorySourceLocatorRepository->getOrCreate($directory);
		}

		$astLocator = new Locator($this->parser, function (): FunctionReflector {
			return $this->container->getService('betterReflectionFunctionReflector');
		});

		$astPhp8Locator = new Locator($this->php8Parser, function (): FunctionReflector {
			return $this->container->getService('betterReflectionFunctionReflector');
		});

		$locators[] = new SkipClassAliasSourceLocator(new PhpInternalSourceLocator($astPhp8Locator, $this->phpstormStubsSourceStubber));
		$locators[] = new ClassBlacklistSourceLocator($this->autoloadSourceLocator, $this->staticReflectionClassNamePatterns);
		foreach ($this->composerAutoloaderProjectPaths as $composerAutoloaderProjectPath) {
			$locator = $this->composerJsonAndInstalledJsonSourceLocatorMaker->create($composerAutoloaderProjectPath);
			if ($locator === null) {
				continue;
			}
			$locators[] = $locator;
		}
		$locators[] = new ClassWhitelistSourceLocator($this->autoloadSourceLocator, $this->staticReflectionClassNamePatterns);
		$locators[] = new PhpVersionBlacklistSourceLocator(new PhpInternalSourceLocator($astLocator, $this->reflectionSourceStubber), $this->phpstormStubsSourceStubber);
		$locators[] = new PhpVersionBlacklistSourceLocator(new EvaledCodeSourceLocator($astLocator, $this->reflectionSourceStubber), $this->phpstormStubsSourceStubber);

		return new MemoizingSourceLocator(new AggregateSourceLocator($locators));
	}

}
