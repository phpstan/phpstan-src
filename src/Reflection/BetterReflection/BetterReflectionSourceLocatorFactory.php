<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection;

use PHPStan\DependencyInjection\Container;
use PHPStan\Reflection\BetterReflection\SourceLocator\AutoloadSourceLocator;
use PHPStan\Reflection\BetterReflection\SourceLocator\ComposerJsonAndInstalledJsonSourceLocatorMaker;
use PHPStan\Reflection\BetterReflection\SourceLocator\OptimizedDirectorySourceLocatorRepository;
use PHPStan\Reflection\BetterReflection\SourceLocator\OptimizedSingleFileSourceLocatorRepository;
use Roave\BetterReflection\Reflector\FunctionReflector;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\SourceStubber\PhpStormStubsSourceStubber;
use Roave\BetterReflection\SourceLocator\SourceStubber\ReflectionSourceStubber;
use Roave\BetterReflection\SourceLocator\SourceStubber\SourceStubber;
use Roave\BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\EvaledCodeSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\MemoizingSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\PhpInternalSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\SourceLocator;

class BetterReflectionSourceLocatorFactory
{

	/** @var \PhpParser\Parser */
	private $parser;

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
	private $autoloadFiles;

	/** @var string[] */
	private $analysedPaths;

	/** @var string[] */
	private $composerAutoloaderProjectPaths;

	/** @var string[] */
	private $analysedPathsFromConfig;

	/**
	 * @param string[] $autoloadDirectories
	 * @param string[] $autoloadFiles
	 * @param string[] $analysedPaths
	 * @param string[] $composerAutoloaderProjectPaths
	 * @param string[] $analysedPathsFromConfig
	 */
	public function __construct(
		\PhpParser\Parser $parser,
		PhpStormStubsSourceStubber $phpstormStubsSourceStubber,
		ReflectionSourceStubber $reflectionSourceStubber,
		OptimizedSingleFileSourceLocatorRepository $optimizedSingleFileSourceLocatorRepository,
		OptimizedDirectorySourceLocatorRepository $optimizedDirectorySourceLocatorRepository,
		ComposerJsonAndInstalledJsonSourceLocatorMaker $composerJsonAndInstalledJsonSourceLocatorMaker,
		AutoloadSourceLocator $autoloadSourceLocator,
		Container $container,
		array $autoloadDirectories,
		array $autoloadFiles,
		array $analysedPaths,
		array $composerAutoloaderProjectPaths,
		array $analysedPathsFromConfig
	)
	{
		$this->parser = $parser;
		$this->phpstormStubsSourceStubber = $phpstormStubsSourceStubber;
		$this->reflectionSourceStubber = $reflectionSourceStubber;
		$this->optimizedSingleFileSourceLocatorRepository = $optimizedSingleFileSourceLocatorRepository;
		$this->optimizedDirectorySourceLocatorRepository = $optimizedDirectorySourceLocatorRepository;
		$this->composerJsonAndInstalledJsonSourceLocatorMaker = $composerJsonAndInstalledJsonSourceLocatorMaker;
		$this->autoloadSourceLocator = $autoloadSourceLocator;
		$this->container = $container;
		$this->autoloadDirectories = $autoloadDirectories;
		$this->autoloadFiles = $autoloadFiles;
		$this->analysedPaths = $analysedPaths;
		$this->composerAutoloaderProjectPaths = $composerAutoloaderProjectPaths;
		$this->analysedPathsFromConfig = $analysedPathsFromConfig;
	}

	public function create(): SourceLocator
	{
		$locators = [];

		foreach ($this->composerAutoloaderProjectPaths as $composerAutoloaderProjectPath) {
			$locator = $this->composerJsonAndInstalledJsonSourceLocatorMaker->create($composerAutoloaderProjectPath);
			if ($locator === null) {
				continue;
			}
			$locators[] =  $locator;
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

		$analysedFiles = array_unique(array_merge($analysedFiles, $this->autoloadFiles));
		foreach ($analysedFiles as $analysedFile) {
			$locators[] = $this->optimizedSingleFileSourceLocatorRepository->getOrCreate($analysedFile);
		}

		$directories = array_unique(array_merge($analysedDirectories, $this->autoloadDirectories));
		foreach ($directories as $directory) {
			$locators[] = $this->optimizedDirectorySourceLocatorRepository->getOrCreate($directory);
		}

		$astLocator = new Locator($this->parser, function (): FunctionReflector {
			return $this->container->getService('betterReflectionFunctionReflector');
		});
		$locators[] = new PhpInternalSourceLocator($astLocator, $this->phpstormStubsSourceStubber);
		$locators[] = $this->autoloadSourceLocator;
		$locators[] = new PhpInternalSourceLocator($astLocator, $this->reflectionSourceStubber);
		$locators[] = new EvaledCodeSourceLocator($astLocator, $this->reflectionSourceStubber);

		return new MemoizingSourceLocator(new AggregateSourceLocator($locators));
	}

}
