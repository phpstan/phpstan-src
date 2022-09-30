<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

use Nette\DI\Extensions\ExtensionsExtension;
use Nette\DI\Extensions\PhpExtension;
use Phar;
use PhpParser\Parser;
use PHPStan\BetterReflection\BetterReflection;
use PHPStan\BetterReflection\Reflector\Reflector;
use PHPStan\BetterReflection\SourceLocator\SourceStubber\PhpStormStubsSourceStubber;
use PHPStan\BetterReflection\SourceLocator\Type\SourceLocator;
use PHPStan\Broker\Broker;
use PHPStan\Command\CommandHelper;
use PHPStan\File\FileHelper;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Reflection\ReflectionProviderStaticAccessor;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use Symfony\Component\Finder\Finder;
use function dirname;
use function extension_loaded;
use function ini_get;
use function is_dir;
use function sys_get_temp_dir;
use function time;
use function unlink;

/** @api */
class ContainerFactory
{

	private FileHelper $fileHelper;

	private string $rootDirectory;

	private string $configDirectory;

	/** @api */
	public function __construct(private string $currentWorkingDirectory)
	{
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
	 * @param string[] $additionalConfigFiles
	 * @param string[] $analysedPaths
	 * @param string[] $composerAutoloaderProjectPaths
	 * @param string[] $analysedPathsFromConfig
	 */
	public function create(
		string $tempDirectory,
		array $additionalConfigFiles,
		array $analysedPaths,
		array $composerAutoloaderProjectPaths = [],
		array $analysedPathsFromConfig = [],
		string $usedLevel = CommandHelper::DEFAULT_LEVEL,
		?string $generateBaselineFile = null,
		?string $cliAutoloadFile = null,
		?string $singleReflectionFile = null,
		?string $singleReflectionInsteadOfFile = null,
	): Container
	{
		$configurator = new Configurator(new LoaderFactory(
			$this->fileHelper,
			$this->rootDirectory,
			$this->currentWorkingDirectory,
			$generateBaselineFile,
		));
		$configurator->defaultExtensions = [
			'php' => PhpExtension::class,
			'extensions' => ExtensionsExtension::class,
		];
		$configurator->setDebugMode(true);
		$configurator->setTempDirectory($tempDirectory);
		$configurator->addParameters([
			'rootDir' => $this->rootDirectory,
			'currentWorkingDirectory' => $this->currentWorkingDirectory,
			'cliArgumentsVariablesRegistered' => ini_get('register_argc_argv') === '1',
			'tmpDir' => $tempDirectory,
			'additionalConfigFiles' => $additionalConfigFiles,
			'composerAutoloaderProjectPaths' => $composerAutoloaderProjectPaths,
			'generateBaselineFile' => $generateBaselineFile,
			'usedLevel' => $usedLevel,
			'cliAutoloadFile' => $cliAutoloadFile,
			'fixerTmpDir' => sys_get_temp_dir() . '/phpstan-fixer',
		]);
		$configurator->addDynamicParameters([
			'singleReflectionFile' => $singleReflectionFile,
			'singleReflectionInsteadOfFile' => $singleReflectionInsteadOfFile,
			'analysedPaths' => $analysedPaths,
			'analysedPathsFromConfig' => $analysedPathsFromConfig,
		]);
		$configurator->addConfig($this->configDirectory . '/config.neon');
		foreach ($additionalConfigFiles as $additionalConfigFile) {
			$configurator->addConfig($additionalConfigFile);
		}

		$container = $configurator->createContainer();

		/** @var SourceLocator $sourceLocator */
		$sourceLocator = $container->getService('betterReflectionSourceLocator');

		/** @var Reflector $reflector */
		$reflector = $container->getService('betterReflectionReflector');

		/** @var Parser $phpParser */
		$phpParser = $container->getService('phpParserDecorator');

		BetterReflection::populate(
			$container->getByType(PhpVersion::class)->getVersionId(),
			$sourceLocator,
			$reflector,
			$phpParser,
			$container->getByType(PhpStormStubsSourceStubber::class),
		);

		/** @var Broker $broker */
		$broker = $container->getByType(Broker::class);
		Broker::registerInstance($broker);
		ReflectionProviderStaticAccessor::registerInstance($container->getByType(ReflectionProvider::class));
		$container->getService('typeSpecifier');

		BleedingEdgeToggle::setBleedingEdge($container->parameters['featureToggles']['bleedingEdge']);
		AccessoryArrayListType::setListTypeEnabled($container->parameters['featureToggles']['listType']);

		return $container->getByType(Container::class);
	}

	public function clearOldContainers(string $tempDirectory): void
	{
		$configurator = new Configurator(new LoaderFactory(
			$this->fileHelper,
			$this->rootDirectory,
			$this->currentWorkingDirectory,
			null,
		));
		$configurator->setDebugMode(true);
		$configurator->setTempDirectory($tempDirectory);

		$containerDirectory = $configurator->getContainerCacheDirectory();
		if (!is_dir($containerDirectory)) {
			return;
		}

		$finder = new Finder();
		$finder->name('Container_*')->in($containerDirectory);
		$twoDaysAgo = time() - 24 * 60 * 60 * 2;

		foreach ($finder as $containerFile) {
			$path = $containerFile->getRealPath();
			if ($path === false) {
				continue;
			}
			if ($containerFile->getATime() > $twoDaysAgo) {
				continue;
			}
			if ($containerFile->getCTime() > $twoDaysAgo) {
				continue;
			}

			@unlink($path);
		}
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
