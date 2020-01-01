<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection;

use PHPStan\DependencyInjection\Container;
use PHPStan\Reflection\BetterReflection\SourceLocator\ComposerJsonAndInstalledJsonSourceLocatorMaker;
use PHPStan\Reflection\BetterReflection\SourceLocator\OptimizedDirectorySourceLocatorRepository;
use PHPStan\Reflection\BetterReflection\SourceLocator\OptimizedSingleFileSourceLocatorRepository;
use Roave\BetterReflection\Reflector\FunctionReflector;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\SourceStubber\PhpStormStubsSourceStubber;
use Roave\BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\MemoizingSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\PhpInternalSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\SourceLocator;

class BetterReflectionSourceLocatorFactory
{

	/** @var \PhpParser\Parser */
	private $parser;

	/** @var PhpStormStubsSourceStubber */
	private $phpStormStubsSourceStubber;

	/** @var \PHPStan\Reflection\BetterReflection\SourceLocator\OptimizedSingleFileSourceLocatorRepository */
	private $optimizedSingleFileSourceLocatorRepository;

	/** @var \PHPStan\Reflection\BetterReflection\SourceLocator\OptimizedDirectorySourceLocatorRepository */
	private $optimizedDirectorySourceLocatorRepository;

	/** @var ComposerJsonAndInstalledJsonSourceLocatorMaker */
	private $composerJsonAndInstalledJsonSourceLocatorMaker;

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

	/** @var bool */
	private $enableScanningPaths;

	/**
	 * @param string[] $autoloadDirectories
	 * @param string[] $autoloadFiles
	 * @param string[] $analysedPaths
	 * @param string[] $composerAutoloaderProjectPaths
	 * @param string[] $analysedPathsFromConfig
	 * @param bool $enableScanningPaths
	 */
	public function __construct(
		\PhpParser\Parser $parser,
		PhpStormStubsSourceStubber $phpStormStubsSourceStubber,
		OptimizedSingleFileSourceLocatorRepository $optimizedSingleFileSourceLocatorRepository,
		OptimizedDirectorySourceLocatorRepository $optimizedDirectorySourceLocatorRepository,
		ComposerJsonAndInstalledJsonSourceLocatorMaker $composerJsonAndInstalledJsonSourceLocatorMaker,
		Container $container,
		array $autoloadDirectories,
		array $autoloadFiles,
		array $analysedPaths,
		array $composerAutoloaderProjectPaths,
		array $analysedPathsFromConfig,
		bool $enableScanningPaths
	)
	{
		$this->parser = $parser;
		$this->phpStormStubsSourceStubber = $phpStormStubsSourceStubber;
		$this->optimizedSingleFileSourceLocatorRepository = $optimizedSingleFileSourceLocatorRepository;
		$this->optimizedDirectorySourceLocatorRepository = $optimizedDirectorySourceLocatorRepository;
		$this->composerJsonAndInstalledJsonSourceLocatorMaker = $composerJsonAndInstalledJsonSourceLocatorMaker;
		$this->container = $container;
		$this->autoloadDirectories = $autoloadDirectories;
		$this->autoloadFiles = $autoloadFiles;
		$this->analysedPaths = $analysedPaths;
		$this->composerAutoloaderProjectPaths = $composerAutoloaderProjectPaths;
		$this->analysedPathsFromConfig = $analysedPathsFromConfig;
		$this->enableScanningPaths = $enableScanningPaths;
	}

	public function create(): SourceLocator
	{
		$locators = [];

		foreach ($this->composerAutoloaderProjectPaths as $composerAutoloaderProjectPath) {
			$locators[] = $this->composerJsonAndInstalledJsonSourceLocatorMaker->create($composerAutoloaderProjectPath);
		}

		if ($this->enableScanningPaths) {
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
		}

		$astLocator = new Locator($this->parser, function (): FunctionReflector {
			return $this->container->getService('betterReflectionFunctionReflector');
		});
		$locators[] = new PhpInternalSourceLocator($astLocator, $this->phpStormStubsSourceStubber);

		return new MemoizingSourceLocator(new AggregateSourceLocator($locators));
	}

}
