<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection;

use PHPStan\DependencyInjection\Container;
use Roave\BetterReflection\Reflector\FunctionReflector;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\SourceStubber\ReflectionSourceStubber;
use Roave\BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\Composer\Factory\MakeLocatorForComposerJsonAndInstalledJson;
use Roave\BetterReflection\SourceLocator\Type\MemoizingSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\PhpInternalSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\SourceLocator;

class BetterReflectionSourceLocatorFactory
{

	/** @var \PhpParser\Parser */
	private $parser;

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

	/**
	 * @param string[] $autoloadDirectories
	 * @param string[] $autoloadFiles
	 * @param string[] $analysedPaths
	 * @param string[] $composerAutoloaderProjectPaths
	 */
	public function __construct(
		\PhpParser\Parser $parser,
		Container $container,
		array $autoloadDirectories,
		array $autoloadFiles,
		array $analysedPaths,
		array $composerAutoloaderProjectPaths
	)
	{
		$this->parser = $parser;
		$this->container = $container;
		$this->autoloadDirectories = $autoloadDirectories;
		$this->autoloadFiles = $autoloadFiles;
		$this->analysedPaths = $analysedPaths;
		$this->composerAutoloaderProjectPaths = $composerAutoloaderProjectPaths;
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

		foreach ($this->analysedPaths as $analysedPath) {
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

		$locators[] = new PhpInternalSourceLocator($astLocator, new ReflectionSourceStubber());

		return new MemoizingSourceLocator(new AggregateSourceLocator($locators));
	}

}
