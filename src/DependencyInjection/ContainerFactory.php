<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

use Nette\DI\Config\Adapters\PhpAdapter;
use Nette\DI\Extensions\ExtensionsExtension;
use Nette\DI\Extensions\PhpExtension;
use Nette\DI\Helpers;
use Nette\Utils\Strings;
use Nette\Utils\Validators;
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
use PHPStan\Reflection\PhpVersionStaticAccessor;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Reflection\ReflectionProviderStaticAccessor;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\ObjectType;
use Symfony\Component\Finder\Finder;
use function array_diff_key;
use function array_map;
use function array_merge;
use function array_unique;
use function count;
use function dirname;
use function extension_loaded;
use function ini_get;
use function is_dir;
use function is_file;
use function is_readable;
use function spl_object_hash;
use function sprintf;
use function str_ends_with;
use function sys_get_temp_dir;
use function time;
use function unlink;

/** @api */
class ContainerFactory
{

	private FileHelper $fileHelper;

	private string $rootDirectory;

	private string $configDirectory;

	private static ?string $lastInitializedContainerId = null;

	/** @api */
	public function __construct(private string $currentWorkingDirectory, private bool $checkDuplicateFiles = false)
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
	): Container
	{
		$allConfigFiles = $this->detectDuplicateIncludedFiles(
			$additionalConfigFiles,
			[
				'rootDir' => $this->rootDirectory,
				'currentWorkingDirectory' => $this->currentWorkingDirectory,
			],
		);

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
			'allConfigFiles' => $allConfigFiles,
			'composerAutoloaderProjectPaths' => $composerAutoloaderProjectPaths,
			'generateBaselineFile' => $generateBaselineFile,
			'usedLevel' => $usedLevel,
			'cliAutoloadFile' => $cliAutoloadFile,
			'fixerTmpDir' => sys_get_temp_dir() . '/phpstan-fixer',
		]);
		$configurator->addDynamicParameters([
			'analysedPaths' => $analysedPaths,
			'analysedPathsFromConfig' => $analysedPathsFromConfig,
		]);
		$configurator->addConfig($this->configDirectory . '/config.neon');
		foreach ($additionalConfigFiles as $additionalConfigFile) {
			$configurator->addConfig($additionalConfigFile);
		}

		$configurator->setAllConfigFiles($allConfigFiles);

		$container = $configurator->createContainer()->getByType(Container::class);
		self::postInitializeContainer($container);

		return $container;
	}

	/** @internal */
	public static function postInitializeContainer(Container $container): void
	{
		$containerId = spl_object_hash($container);
		if ($containerId === self::$lastInitializedContainerId) {
			return;
		}

		self::$lastInitializedContainerId = $containerId;

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

		$broker = $container->getByType(Broker::class);
		Broker::registerInstance($broker);
		ReflectionProviderStaticAccessor::registerInstance($container->getByType(ReflectionProvider::class));
		PhpVersionStaticAccessor::registerInstance($container->getByType(PhpVersion::class));
		ObjectType::resetCaches();
		$container->getService('typeSpecifier');

		BleedingEdgeToggle::setBleedingEdge($container->getParameter('featureToggles')['bleedingEdge']);
		AccessoryArrayListType::setListTypeEnabled($container->getParameter('featureToggles')['listType']);
		TemplateTypeVariance::setInvarianceCompositionEnabled($container->getParameter('featureToggles')['invarianceComposition']);
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

	/**
	 * @param string[] $configFiles
	 * @param array<string, string> $loaderParameters
	 * @return string[]
	 * @throws DuplicateIncludedFilesException
	 */
	private function detectDuplicateIncludedFiles(
		array $configFiles,
		array $loaderParameters,
	): array
	{
		$neonAdapter = new NeonAdapter();
		$phpAdapter = new PhpAdapter();
		$allConfigFiles = [];
		foreach ($configFiles as $configFile) {
			$allConfigFiles = array_merge($allConfigFiles, self::getConfigFiles($this->fileHelper, $neonAdapter, $phpAdapter, $configFile, $loaderParameters, null));
		}

		$normalized = array_map(fn (string $file): string => $this->fileHelper->normalizePath($file), $allConfigFiles);

		$deduplicated = array_unique($normalized);
		if (count($normalized) <= count($deduplicated)) {
			return $normalized;
		}

		if (!$this->checkDuplicateFiles) {
			return $normalized;
		}

		$duplicateFiles = array_unique(array_diff_key($normalized, $deduplicated));

		throw new DuplicateIncludedFilesException($duplicateFiles);
	}

	/**
	 * @param array<string, string> $loaderParameters
	 * @return string[]
	 */
	private static function getConfigFiles(
		FileHelper $fileHelper,
		NeonAdapter $neonAdapter,
		PhpAdapter $phpAdapter,
		string $configFile,
		array $loaderParameters,
		?string $generateBaselineFile,
	): array
	{
		if ($generateBaselineFile === $fileHelper->normalizePath($configFile)) {
			return [];
		}
		if (!is_file($configFile) || !is_readable($configFile)) {
			return [];
		}

		if (str_ends_with($configFile, '.php')) {
			$data = $phpAdapter->load($configFile);
		} else {
			$data = $neonAdapter->load($configFile);
		}
		$allConfigFiles = [$configFile];
		if (isset($data['includes'])) {
			Validators::assert($data['includes'], 'list', sprintf("section 'includes' in file '%s'", $configFile));
			$includes = Helpers::expand($data['includes'], $loaderParameters);
			foreach ($includes as $include) {
				$include = self::expandIncludedFile($include, $configFile);
				$allConfigFiles = array_merge($allConfigFiles, self::getConfigFiles($fileHelper, $neonAdapter, $phpAdapter, $include, $loaderParameters, $generateBaselineFile));
			}
		}

		return $allConfigFiles;
	}

	private static function expandIncludedFile(string $includedFile, string $mainFile): string
	{
		return Strings::match($includedFile, '#([a-z]+:)?[/\\\\]#Ai') !== null // is absolute
			? $includedFile
			: dirname($mainFile) . '/' . $includedFile;
	}

}
