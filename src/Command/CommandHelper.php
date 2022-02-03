<?php declare(strict_types = 1);

namespace PHPStan\Command;

use Closure;
use Composer\XdebugHandler\XdebugHandler;
use Nette\DI\Config\Adapters\PhpAdapter;
use Nette\DI\Helpers;
use Nette\DI\InvalidConfigurationException;
use Nette\DI\ServiceCreationException;
use Nette\FileNotFoundException;
use Nette\InvalidStateException;
use Nette\Schema\Context as SchemaContext;
use Nette\Schema\Processor;
use Nette\Schema\ValidationException;
use Nette\Utils\AssertionException;
use Nette\Utils\Strings;
use Nette\Utils\Validators;
use PHPStan\Command\Symfony\SymfonyOutput;
use PHPStan\Command\Symfony\SymfonyStyle;
use PHPStan\DependencyInjection\Container;
use PHPStan\DependencyInjection\ContainerFactory;
use PHPStan\DependencyInjection\LoaderFactory;
use PHPStan\DependencyInjection\NeonAdapter;
use PHPStan\ExtensionInstaller\GeneratedConfig;
use PHPStan\File\FileFinder;
use PHPStan\File\FileHelper;
use PHPStan\ShouldNotHappenException;
use ReflectionClass;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;
use function array_diff_key;
use function array_key_exists;
use function array_map;
use function array_merge;
use function array_unique;
use function class_exists;
use function count;
use function dirname;
use function error_get_last;
use function error_reporting;
use function function_exists;
use function get_class;
use function getcwd;
use function gettype;
use function implode;
use function ini_get;
use function ini_restore;
use function ini_set;
use function intval;
use function is_dir;
use function is_file;
use function is_readable;
use function is_string;
use function mkdir;
use function pcntl_async_signals;
use function pcntl_signal;
use function register_shutdown_function;
use function sprintf;
use function str_ends_with;
use function str_repeat;
use function strpos;
use function sys_get_temp_dir;
use const DIRECTORY_SEPARATOR;
use const E_ERROR;
use const PHP_VERSION_ID;
use const SIGINT;

class CommandHelper
{

	public const DEFAULT_LEVEL = '0';

	private static ?string $reservedMemory = null;

	/**
	 * @param string[] $paths
	 * @param string[] $composerAutoloaderProjectPaths
	 *
	 * @throws InceptionNotSuccessfulException
	 */
	public static function begin(
		InputInterface $input,
		OutputInterface $output,
		array $paths,
		?string $memoryLimit,
		?string $autoloadFile,
		array $composerAutoloaderProjectPaths,
		?string $projectConfigFile,
		?string $generateBaselineFile,
		?string $level,
		bool $allowXdebug,
		bool $debugEnabled = false,
		?string $singleReflectionFile = null,
		?string $singleReflectionInsteadOfFile = null,
		bool $cleanupContainerCache = true,
	): InceptionResult
	{
		if (!$allowXdebug) {
			$xdebug = new XdebugHandler('phpstan');
			$xdebug->setPersistent();
			$xdebug->check();
			unset($xdebug);
		}

		$stdOutput = new SymfonyOutput($output, new SymfonyStyle(new ErrorsConsoleStyle($input, $output)));

		/** @var Output $errorOutput */
		$errorOutput = (static function () use ($input, $output): Output {
			$symfonyErrorOutput = $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output;
			return new SymfonyOutput($symfonyErrorOutput, new SymfonyStyle(new ErrorsConsoleStyle($input, $symfonyErrorOutput)));
		})();
		if ($memoryLimit !== null) {
			if (Strings::match($memoryLimit, '#^-?\d+[kMG]?$#i') === null) {
				$errorOutput->writeLineFormatted(sprintf('Invalid memory limit format "%s".', $memoryLimit));
				throw new InceptionNotSuccessfulException();
			}
			if (ini_set('memory_limit', $memoryLimit) === false) {
				$errorOutput->writeLineFormatted(sprintf('Memory limit "%s" cannot be set.', $memoryLimit));
				throw new InceptionNotSuccessfulException();
			}
		}

		self::$reservedMemory = str_repeat('PHPStan', 1463); // reserve 10 kB of space
		register_shutdown_function(static function () use ($errorOutput): void {
			self::$reservedMemory = null;
			$error = error_get_last();
			if ($error === null) {
				return;
			}
			if ($error['type'] !== E_ERROR) {
				return;
			}

			if (strpos($error['message'], 'Allowed memory size') === false) {
				return;
			}

			$errorOutput->writeLineFormatted('');
			$errorOutput->writeLineFormatted(sprintf('<error>PHPStan process crashed because it reached configured PHP memory limit</error>: %s', ini_get('memory_limit')));
			$errorOutput->writeLineFormatted('Increase your memory limit in php.ini or run PHPStan with --memory-limit CLI option.');
		});

		$currentWorkingDirectory = getcwd();
		if ($currentWorkingDirectory === false) {
			throw new ShouldNotHappenException();
		}
		$currentWorkingDirectoryFileHelper = new FileHelper($currentWorkingDirectory);
		$currentWorkingDirectory = $currentWorkingDirectoryFileHelper->getWorkingDirectory();
		if ($autoloadFile !== null) {
			$autoloadFile = $currentWorkingDirectoryFileHelper->absolutizePath($autoloadFile);
			if (!is_file($autoloadFile)) {
				$errorOutput->writeLineFormatted(sprintf('Autoload file "%s" not found.', $autoloadFile));
				throw new InceptionNotSuccessfulException();
			}

			(static function (string $file): void {
				require_once $file;
			})($autoloadFile);
		}
		if ($projectConfigFile === null) {
			foreach (['phpstan.neon', 'phpstan.neon.dist'] as $discoverableConfigName) {
				$discoverableConfigFile = $currentWorkingDirectory . DIRECTORY_SEPARATOR . $discoverableConfigName;
				if (is_file($discoverableConfigFile)) {
					$projectConfigFile = $discoverableConfigFile;
					$errorOutput->writeLineFormatted(sprintf('Note: Using configuration file %s.', $projectConfigFile));
					break;
				}
			}
		} else {
			$projectConfigFile = $currentWorkingDirectoryFileHelper->absolutizePath($projectConfigFile);
		}

		if ($generateBaselineFile !== null) {
			$generateBaselineFile = $currentWorkingDirectoryFileHelper->normalizePath($currentWorkingDirectoryFileHelper->absolutizePath($generateBaselineFile));
		}

		$defaultLevelUsed = false;
		if ($projectConfigFile === null && $level === null) {
			$level = self::DEFAULT_LEVEL;
			$defaultLevelUsed = true;
		}

		$paths = array_map(static fn (string $path): string => $currentWorkingDirectoryFileHelper->normalizePath($currentWorkingDirectoryFileHelper->absolutizePath($path)), $paths);

		$analysedPathsFromConfig = [];
		$containerFactory = new ContainerFactory($currentWorkingDirectory);
		$projectConfig = null;
		if ($projectConfigFile !== null) {
			if (!is_file($projectConfigFile)) {
				$errorOutput->writeLineFormatted(sprintf('Project config file at path %s does not exist.', $projectConfigFile));
				throw new InceptionNotSuccessfulException();
			}

			$loader = (new LoaderFactory(
				$currentWorkingDirectoryFileHelper,
				$containerFactory->getRootDirectory(),
				$containerFactory->getCurrentWorkingDirectory(),
				$generateBaselineFile,
			))->createLoader();

			try {
				$projectConfig = $loader->load($projectConfigFile, null);
			} catch (InvalidStateException | FileNotFoundException $e) {
				$errorOutput->writeLineFormatted($e->getMessage());
				throw new InceptionNotSuccessfulException();
			}
			$defaultParameters = [
				'rootDir' => $containerFactory->getRootDirectory(),
				'currentWorkingDirectory' => $containerFactory->getCurrentWorkingDirectory(),
			];

			if (isset($projectConfig['parameters']['tmpDir'])) {
				$tmpDir = Helpers::expand($projectConfig['parameters']['tmpDir'], $defaultParameters);
			}
			if ($level === null && isset($projectConfig['parameters']['level'])) {
				$level = (string) $projectConfig['parameters']['level'];
			}
			if (isset($projectConfig['parameters']['paths'])) {
				$analysedPathsFromConfig = Helpers::expand($projectConfig['parameters']['paths'], $defaultParameters);
			}
			if (count($paths) === 0) {
				$paths = $analysedPathsFromConfig;
			}
		}

		$additionalConfigFiles = [];
		if ($level !== null) {
			$levelConfigFile = sprintf('%s/config.level%s.neon', $containerFactory->getConfigDirectory(), $level);
			if (!is_file($levelConfigFile)) {
				$errorOutput->writeLineFormatted(sprintf('Level config file %s was not found.', $levelConfigFile));
				throw new InceptionNotSuccessfulException();
			}

			$additionalConfigFiles[] = $levelConfigFile;
		}

		if (class_exists('PHPStan\ExtensionInstaller\GeneratedConfig')) {
			$generatedConfigReflection = new ReflectionClass('PHPStan\ExtensionInstaller\GeneratedConfig');
			$generatedConfigDirectory = dirname($generatedConfigReflection->getFileName());
			foreach (GeneratedConfig::EXTENSIONS as $name => $extensionConfig) {
				foreach ($extensionConfig['extra']['includes'] ?? [] as $includedFile) {
					if (!is_string($includedFile)) {
						$errorOutput->writeLineFormatted(sprintf('Cannot include config from package %s, expecting string file path but got %s', $name, gettype($includedFile)));
						throw new InceptionNotSuccessfulException();
					}
					$includedFilePath = null;
					if (isset($extensionConfig['relative_install_path'])) {
						$includedFilePath = sprintf('%s/%s/%s', $generatedConfigDirectory, $extensionConfig['relative_install_path'], $includedFile);
						if (!is_file($includedFilePath) || !is_readable($includedFilePath)) {
							$includedFilePath = null;
						}
					}

					if ($includedFilePath === null) {
						$includedFilePath = sprintf('%s/%s', $extensionConfig['install_path'], $includedFile);
					}
					if (!is_file($includedFilePath) || !is_readable($includedFilePath)) {
						$errorOutput->writeLineFormatted(sprintf('Config file %s does not exist or isn\'t readable', $includedFilePath));
						throw new InceptionNotSuccessfulException();
					}
					$additionalConfigFiles[] = $includedFilePath;
				}
			}
		}

		if (
			$projectConfigFile !== null
			&& $currentWorkingDirectoryFileHelper->normalizePath($projectConfigFile, '/') !== $currentWorkingDirectoryFileHelper->normalizePath(__DIR__ . '/../../conf/config.stubFiles.neon', '/')
		) {
			$additionalConfigFiles[] = $projectConfigFile;
		}

		$loaderParameters = [
			'rootDir' => $containerFactory->getRootDirectory(),
			'currentWorkingDirectory' => $containerFactory->getCurrentWorkingDirectory(),
		];

		self::detectDuplicateIncludedFiles(
			$errorOutput,
			$currentWorkingDirectoryFileHelper,
			$additionalConfigFiles,
			$loaderParameters,
		);

		$createDir = static function (string $path) use ($errorOutput): void {
			if (!is_dir($path) && !@mkdir($path, 0777) && !is_dir($path)) {
				$errorOutput->writeLineFormatted(sprintf('Cannot create a temp directory %s', $path));
				throw new InceptionNotSuccessfulException();
			}
		};

		if (!isset($tmpDir)) {
			$tmpDir = sys_get_temp_dir() . '/phpstan';
			$createDir($tmpDir);
		}

		try {
			$container = $containerFactory->create($tmpDir, $additionalConfigFiles, $paths, $composerAutoloaderProjectPaths, $analysedPathsFromConfig, $level ?? self::DEFAULT_LEVEL, $generateBaselineFile, $autoloadFile, $singleReflectionFile, $singleReflectionInsteadOfFile);
		} catch (InvalidConfigurationException | AssertionException $e) {
			$errorOutput->writeLineFormatted('<error>Invalid configuration:</error>');
			$errorOutput->writeLineFormatted($e->getMessage());
			throw new InceptionNotSuccessfulException();
		} catch (ServiceCreationException $e) {
			$matches = Strings::match($e->getMessage(), '#Service of type (?<serviceType>[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff\\\\]*[a-zA-Z0-9_\x7f-\xff]): Service of type (?<parserServiceType>[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff\\\\]*[a-zA-Z0-9_\x7f-\xff]) needed by \$(?<parameterName>[a-zA-Z_\x7f-\xff][a-zA-Z_0-9\x7f-\xff]*) in (?<methodName>[a-zA-Z_\x7f-\xff][a-zA-Z_0-9\x7f-\xff]*)\(\)#');
			if ($matches === null) {
				throw $e;
			}

			if ($matches['parserServiceType'] !== 'PHPStan\\Parser\\Parser') {
				throw $e;
			}

			if ($matches['methodName'] !== '__construct') {
				throw $e;
			}

			$errorOutput->writeLineFormatted('<error>Invalid configuration:</error>');
			$errorOutput->writeLineFormatted(sprintf("Service of type <fg=cyan>%s</> is no longer autowired.\n", $matches['parserServiceType']));
			$errorOutput->writeLineFormatted('You need to choose one of the following services');
			$errorOutput->writeLineFormatted(sprintf('and use it in the %s argument of your service <fg=cyan>%s</>:', $matches['parameterName'], $matches['serviceType']));
			$errorOutput->writeLineFormatted('* <fg=cyan>defaultAnalysisParser</> (if you\'re parsing files from analysed paths)');
			$errorOutput->writeLineFormatted('* <fg=cyan>currentPhpVersionSimpleDirectParser</> (in most other situations)');

			$errorOutput->writeLineFormatted('');
			$errorOutput->writeLineFormatted('After fixing this problem, your configuration will look something like this:');
			$errorOutput->writeLineFormatted('');
			$errorOutput->writeLineFormatted('-');
			$errorOutput->writeLineFormatted(sprintf("\tclass: %s", $matches['serviceType']));
			$errorOutput->writeLineFormatted(sprintf("\targuments:"));
			$errorOutput->writeLineFormatted(sprintf("\t\t%s: @defaultAnalysisParser", $matches['parameterName']));
			$errorOutput->writeLineFormatted('');

			throw new InceptionNotSuccessfulException();
		}

		if ($cleanupContainerCache) {
			$containerFactory->clearOldContainers($tmpDir);
		}

		if (count($paths) === 0) {
			$errorOutput->writeLineFormatted('At least one path must be specified to analyse.');
			throw new InceptionNotSuccessfulException();
		}

		self::setUpSignalHandler($errorOutput);
		if (!$container->hasParameter('customRulesetUsed')) {
			$errorOutput->writeLineFormatted('');
			$errorOutput->writeLineFormatted('<comment>No rules detected</comment>');
			$errorOutput->writeLineFormatted('');
			$errorOutput->writeLineFormatted('You have the following choices:');
			$errorOutput->writeLineFormatted('');
			$errorOutput->writeLineFormatted('* while running the analyse option, use the <info>--level</info> option to adjust your rule level - the higher the stricter');
			$errorOutput->writeLineFormatted('');
			$errorOutput->writeLineFormatted(sprintf('* create your own <info>custom ruleset</info> by selecting which rules you want to check by copying the service definitions from the built-in config level files in <options=bold>%s</>.', $currentWorkingDirectoryFileHelper->normalizePath(__DIR__ . '/../../conf')));
			$errorOutput->writeLineFormatted('  * in this case, don\'t forget to define parameter <options=bold>customRulesetUsed</> in your config file.');
			$errorOutput->writeLineFormatted('');
			throw new InceptionNotSuccessfulException();
		} elseif ((bool) $container->getParameter('customRulesetUsed')) {
			$defaultLevelUsed = false;
		}

		$schema = $container->getParameter('__parametersSchema');
		$processor = new Processor();
		$processor->onNewContext[] = static function (SchemaContext $context): void {
			$context->path = ['parameters'];
		};

		try {
			$processor->process($schema, $container->getParameters());
		} catch (ValidationException $e) {
			foreach ($e->getMessages() as $message) {
				$errorOutput->writeLineFormatted('<error>Invalid configuration:</error>');
				$errorOutput->writeLineFormatted($message);
			}
			throw new InceptionNotSuccessfulException();
		}

		$phpstanErrorReporting = intval(ini_get('error_reporting'));
		ini_restore('error_reporting');
		foreach ($container->getParameter('bootstrapFiles') as $bootstrapFileFromArray) {
			self::executeBootstrapFile($bootstrapFileFromArray, $container, $errorOutput, $debugEnabled);
		}
		error_reporting($phpstanErrorReporting);

		if (PHP_VERSION_ID >= 80000) {
			require_once __DIR__ . '/../../stubs/runtime/Enum/UnitEnum.php';
			require_once __DIR__ . '/../../stubs/runtime/Enum/BackedEnum.php';
			require_once __DIR__ . '/../../stubs/runtime/Enum/ReflectionEnum.php';
			require_once __DIR__ . '/../../stubs/runtime/Enum/ReflectionEnumUnitCase.php';
			require_once __DIR__ . '/../../stubs/runtime/Enum/ReflectionEnumBackedCase.php';
		}

		foreach ($container->getParameter('scanFiles') as $scannedFile) {
			if (is_file($scannedFile)) {
				continue;
			}

			$errorOutput->writeLineFormatted(sprintf('Scanned file %s does not exist.', $scannedFile));

			throw new InceptionNotSuccessfulException();
		}

		foreach ($container->getParameter('scanDirectories') as $scannedDirectory) {
			if (is_dir($scannedDirectory)) {
				continue;
			}

			$errorOutput->writeLineFormatted(sprintf('Scanned directory %s does not exist.', $scannedDirectory));

			throw new InceptionNotSuccessfulException();
		}

		$alreadyAddedStubFiles = [];
		foreach ($container->getParameter('stubFiles') as $stubFile) {
			if (array_key_exists($stubFile, $alreadyAddedStubFiles)) {
				$errorOutput->writeLineFormatted(sprintf('Stub file %s is added multiple times.', $stubFile));

				throw new InceptionNotSuccessfulException();
			}

			$alreadyAddedStubFiles[$stubFile] = true;

			if (is_file($stubFile)) {
				continue;
			}

			$errorOutput->writeLineFormatted(sprintf('Stub file %s does not exist.', $stubFile));

			throw new InceptionNotSuccessfulException();
		}

		$excludesAnalyse = $container->getParameter('excludes_analyse');
		$excludePaths = $container->getParameter('excludePaths');
		if (count($excludesAnalyse) > 0 && $excludePaths !== null) {
			$errorOutput->writeLineFormatted(sprintf('Configuration parameters <fg=cyan>excludes_analyse</> and <fg=cyan>excludePaths</> cannot be used at the same time.'));
			$errorOutput->writeLineFormatted('');
			$errorOutput->writeLineFormatted(sprintf('Parameter <fg=cyan>excludes_analyse</> has been deprecated so use <fg=cyan>excludePaths</> only from now on.'));
			$errorOutput->writeLineFormatted('');

			throw new InceptionNotSuccessfulException();
		} elseif (count($excludesAnalyse) > 0) {
			$errorOutput->writeLineFormatted('⚠️  You\'re using a deprecated config option <fg=cyan>excludes_analyse</>. ⚠️️');
			$errorOutput->writeLineFormatted('');
			$errorOutput->writeLineFormatted(sprintf('Parameter <fg=cyan>excludes_analyse</> has been deprecated so use <fg=cyan>excludePaths</> only from now on.'));
		}

		$tempResultCachePath = $container->getParameter('tempResultCachePath');
		$createDir($tempResultCachePath);

		/** @var FileFinder $fileFinder */
		$fileFinder = $container->getService('fileFinderAnalyse');

		$pathRoutingParser = $container->getService('pathRoutingParser');

		/** @var Closure(): array{string[], bool} $filesCallback */
		$filesCallback = static function () use ($fileFinder, $pathRoutingParser, $paths): array {
			$fileFinderResult = $fileFinder->findFiles($paths);
			$files = $fileFinderResult->getFiles();

			$pathRoutingParser->setAnalysedFiles($files);

			return [$files, $fileFinderResult->isOnlyFiles()];
		};

		return new InceptionResult(
			$filesCallback,
			$stdOutput,
			$errorOutput,
			$container,
			$defaultLevelUsed,
			$projectConfigFile,
			$projectConfig,
			$generateBaselineFile,
		);
	}

	/**
	 * @throws InceptionNotSuccessfulException
	 */
	private static function executeBootstrapFile(
		string $file,
		Container $container,
		Output $errorOutput,
		bool $debugEnabled,
	): void
	{
		if (!is_file($file)) {
			$errorOutput->writeLineFormatted(sprintf('Bootstrap file %s does not exist.', $file));
			throw new InceptionNotSuccessfulException();
		}
		try {
			(static function (string $file) use ($container): void {
				require_once $file;
			})($file);
		} catch (Throwable $e) {
			$errorOutput->writeLineFormatted(sprintf('%s thrown in %s on line %d while loading bootstrap file %s: %s', get_class($e), $e->getFile(), $e->getLine(), $file, $e->getMessage()));

			if ($debugEnabled) {
				$errorOutput->writeLineFormatted($e->getTraceAsString());
			}

			throw new InceptionNotSuccessfulException();
		}
	}

	private static function setUpSignalHandler(Output $output): void
	{
		if (!function_exists('pcntl_signal')) {
			return;
		}

		pcntl_async_signals(true);
		pcntl_signal(SIGINT, static function () use ($output): void {
			$output->writeLineFormatted('');
			exit(1);
		});
	}

	/**
	 * @param string[] $configFiles
	 * @param array<string, string> $loaderParameters
	 * @throws InceptionNotSuccessfulException
	 */
	private static function detectDuplicateIncludedFiles(
		Output $output,
		FileHelper $fileHelper,
		array $configFiles,
		array $loaderParameters,
	): void
	{
		$neonAdapter = new NeonAdapter();
		$phpAdapter = new PhpAdapter();
		$allConfigFiles = [];
		foreach ($configFiles as $configFile) {
			$allConfigFiles = array_merge($allConfigFiles, self::getConfigFiles($fileHelper, $neonAdapter, $phpAdapter, $configFile, $loaderParameters, null));
		}

		$normalized = array_map(static fn (string $file): string => $fileHelper->normalizePath($file), $allConfigFiles);

		$deduplicated = array_unique($normalized);
		if (count($normalized) <= count($deduplicated)) {
			return;
		}

		$duplicateFiles = array_unique(array_diff_key($normalized, $deduplicated));

		$format = "<error>These files are included multiple times:</error>\n- %s";
		if (count($duplicateFiles) === 1) {
			$format = "<error>This file is included multiple times:</error>\n- %s";
		}
		$output->writeLineFormatted(sprintf($format, implode("\n- ", $duplicateFiles)));

		if (class_exists('PHPStan\ExtensionInstaller\GeneratedConfig')) {
			$output->writeLineFormatted('');
			$output->writeLineFormatted('It can lead to unexpected results. If you\'re using phpstan/extension-installer, make sure you have removed corresponding neon files from your project config file.');
		}
		throw new InceptionNotSuccessfulException();
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
