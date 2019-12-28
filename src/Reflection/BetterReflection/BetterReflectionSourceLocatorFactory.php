<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection;

use PHPStan\DependencyInjection\Container;
use Roave\BetterReflection\Reflector\FunctionReflector;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\SourceStubber\PhpStormStubsSourceStubber;
use Roave\BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\Composer\Factory\MakeLocatorForComposerJsonAndInstalledJson;
use Roave\BetterReflection\SourceLocator\Type\MemoizingSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\PhpInternalSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\SourceLocator;

class BetterReflectionSourceLocatorFactory
{

	/** @var \PhpParser\Parser */
	private $parser;

	/** @var PhpStormStubsSourceStubber */
	private $phpStormStubsSourceStubber;

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
		PhpStormStubsSourceStubber $phpStormStubsSourceStubber,
		Container $container,
		array $autoloadDirectories,
		array $autoloadFiles,
		array $analysedPaths,
		array $composerAutoloaderProjectPaths,
		array $analysedPathsFromConfig
	)
	{
		$this->parser = $parser;
		$this->phpStormStubsSourceStubber = $phpStormStubsSourceStubber;
		$this->container = $container;
		$this->autoloadDirectories = $autoloadDirectories;
		$this->autoloadFiles = $autoloadFiles;
		$this->analysedPaths = $analysedPaths;
		$this->composerAutoloaderProjectPaths = $composerAutoloaderProjectPaths;
		$this->analysedPathsFromConfig = $analysedPathsFromConfig;
	}

	public function create(): SourceLocator
	{
		$astLocator = new Locator($this->parser, function (): FunctionReflector {
			return $this->container->getService('betterReflectionFunctionReflector');
		});

		$locators = [];

		foreach ($this->composerAutoloaderProjectPaths as $composerAutoloaderProjectPath) {
			$locators[] = (new MakeLocatorForComposerJsonAndInstalledJson())($composerAutoloaderProjectPath, $astLocator);
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
			$locators[] = new \Roave\BetterReflection\SourceLocator\Type\SingleFileSourceLocator($analysedFile, $astLocator);
		}

		$directories = array_unique(array_merge($analysedDirectories, $this->autoloadDirectories));
		if (count($directories) > 0) {
			$locators[] = new \Roave\BetterReflection\SourceLocator\Type\DirectoriesSourceLocator($directories, $astLocator);
		}

		$locators[] = new PhpInternalSourceLocator($astLocator, $this->phpStormStubsSourceStubber);

		return new MemoizingSourceLocator(new AggregateSourceLocator($locators));
	}

}
