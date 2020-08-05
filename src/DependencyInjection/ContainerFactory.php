<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

use Nette\DI\Extensions\PhpExtension;
use Phar;
use PHPStan\Broker\Broker;
use PHPStan\Command\CommandHelper;
use PHPStan\File\FileHelper;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\SourceLocator\SourceStubber\PhpStormStubsSourceStubber;

class ContainerFactory
{

	private string $currentWorkingDirectory;

	private FileHelper $fileHelper;

	private string $rootDirectory;

	private string $configDirectory;

	public function __construct(string $currentWorkingDirectory)
	{
		$this->currentWorkingDirectory = $currentWorkingDirectory;
		$this->fileHelper = new FileHelper($currentWorkingDirectory);

		$rootDir = __DIR__ . '/../..';
		$originalRootDir = $this->fileHelper->normalizePath($rootDir);
		if (extension_loaded('phar')) {
			$pharPath = Phar::running(false);
			if ($pharPath !== '') {
				$rootDir = dirname($pharPath);
			}
		}
		$this->rootDirectory = $this->fileHelper->normalizePath($rootDir);
		$this->configDirectory = $originalRootDir . '/conf';
	}

	/**
	 * @param string $tempDirectory
	 * @param string[] $additionalConfigFiles
	 * @param string[] $analysedPaths
	 * @param string[] $composerAutoloaderProjectPaths
	 * @param string[] $analysedPathsFromConfig
	 * @param string[] $allCustomConfigFiles
	 * @param string $usedLevel
	 * @param string|null $generateBaselineFile
	 * @param string|null $cliAutoloadFile
	 * @param string|null $singleReflectionFile
	 * @return \PHPStan\DependencyInjection\Container
	 */
	public function create(
		string $tempDirectory,
		array $additionalConfigFiles,
		array $analysedPaths,
		array $composerAutoloaderProjectPaths = [],
		array $analysedPathsFromConfig = [],
		array $allCustomConfigFiles = [],
		string $usedLevel = CommandHelper::DEFAULT_LEVEL,
		?string $generateBaselineFile = null,
		?string $cliAutoloadFile = null,
		?string $singleReflectionFile = null
	): Container
	{
		$configurator = new Configurator(new LoaderFactory(
			$this->fileHelper,
			$this->rootDirectory,
			$this->currentWorkingDirectory,
			$generateBaselineFile
		));
		$configurator->defaultExtensions = [
			'php' => PhpExtension::class,
			'extensions' => \Nette\DI\Extensions\ExtensionsExtension::class,
		];
		$configurator->setDebugMode(true);
		$configurator->setTempDirectory($tempDirectory);
		$configurator->addParameters([
			'rootDir' => $this->rootDirectory,
			'currentWorkingDirectory' => $this->currentWorkingDirectory,
			'cliArgumentsVariablesRegistered' => ini_get('register_argc_argv') === '1',
			'tmpDir' => $tempDirectory,
			'additionalConfigFiles' => $additionalConfigFiles,
			'analysedPaths' => $analysedPaths,
			'composerAutoloaderProjectPaths' => $composerAutoloaderProjectPaths,
			'analysedPathsFromConfig' => $analysedPathsFromConfig,
			'allCustomConfigFiles' => $allCustomConfigFiles,
			'usedLevel' => $usedLevel,
			'cliAutoloadFile' => $cliAutoloadFile,
		]);
		$configurator->addDynamicParameters([
			'singleReflectionFile' => $singleReflectionFile,
		]);
		$configurator->addConfig($this->configDirectory . '/config.neon');
		foreach ($additionalConfigFiles as $additionalConfigFile) {
			$configurator->addConfig($additionalConfigFile);
		}

		$container = $configurator->createContainer();

		// @phpstan-ignore-next-line
		BetterReflection::populate(
			$container->getService('betterReflectionSourceLocator'),
			$container->getService('betterReflectionClassReflector'),
			$container->getService('betterReflectionFunctionReflector'),
			$container->getService('betterReflectionConstantReflector'),
			$container->getService('phpParserDecorator'),
			$container->getByType(PhpStormStubsSourceStubber::class)
		);

		/** @var Broker $broker */
		$broker = $container->getByType(Broker::class);
		Broker::registerInstance($broker);
		$container->getService('typeSpecifier');

		return $container->getByType(Container::class);
	}

	public function getCurrentWorkingDirectory(): string
	{
		return $this->currentWorkingDirectory;
	}

	public function getRootDirectory(): string
	{
		return $this->rootDirectory;
	}

	public function getConfigDirectory(): string
	{
		return $this->configDirectory;
	}

}
