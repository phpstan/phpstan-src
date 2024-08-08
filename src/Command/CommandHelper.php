<?php declare(strict_types = 1);

namespace PHPStan\Command;

use Composer\Semver\Semver;
use Composer\XdebugHandler\XdebugHandler;
use Nette\DI\Helpers;
use Nette\DI\InvalidConfigurationException;
use Nette\DI\ServiceCreationException;
use Nette\FileNotFoundException;
use Nette\InvalidStateException;
use Nette\Schema\ValidationException;
use Nette\Utils\AssertionException;
use Nette\Utils\Strings;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Command\Symfony\SymfonyOutput;
use PHPStan\Command\Symfony\SymfonyStyle;
use PHPStan\DependencyInjection\Container;
use PHPStan\DependencyInjection\ContainerFactory;
use PHPStan\DependencyInjection\DuplicateIncludedFilesException;
use PHPStan\DependencyInjection\InvalidExcludePathsException;
use PHPStan\DependencyInjection\InvalidIgnoredErrorPatternsException;
use PHPStan\DependencyInjection\LoaderFactory;
use PHPStan\ExtensionInstaller\GeneratedConfig;
use PHPStan\File\FileExcluder;
use PHPStan\File\FileFinder;
use PHPStan\File\FileHelper;
use PHPStan\File\SimpleRelativePathHelper;
use PHPStan\Internal\ComposerHelper;
use PHPStan\Internal\DirectoryCreator;
use PHPStan\Internal\DirectoryCreatorException;
use PHPStan\PhpDoc\StubFilesProvider;
use PHPStan\ShouldNotHappenException;
use ReflectionClass;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;
use function array_filter;
use function array_key_exists;
use function array_map;
use function array_values;
use function class_exists;
use function count;
use function dirname;
use function error_get_last;
use function get_class;
use function getcwd;
use function getenv;
use function gettype;
use function implode;
use function ini_get;
use function ini_set;
use function is_dir;
use function is_file;
use function is_readable;
use function is_string;
use function register_shutdown_function;
use function spl_autoload_functions;
use function sprintf;
use function str_contains;
use function str_repeat;
use function sys_get_temp_dir;
use const DIRECTORY_SEPARATOR;
use const E_ERROR;
use const PHP_VERSION_ID;

final class CommandHelper
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
		bool $cleanupContainerCache = true,
	): InceptionResult
	{
		$stdOutput = new SymfonyOutput($output, new SymfonyStyle(new ErrorsConsoleStyle($input, $output)));

		$errorOutput = (static function () use ($input, $output): Output {
			$symfonyErrorOutput = $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output;
			return new SymfonyOutput($symfonyErrorOutput, new SymfonyStyle(new ErrorsConsoleStyle($input, $symfonyErrorOutput)));
		})();

		if (!$allowXdebug) {
			$xdebug = new XdebugHandler('phpstan');
			$xdebug->setPersistent();
			$xdebug->check();
			unset($xdebug);
		}

		if ($allowXdebug) {
			if (!XdebugHandler::isXdebugActive()) {
				$errorOutput->getStyle()->note('You are running with "--xdebug" enabled, but the Xdebug PHP extension is not active. The process will not halt at breakpoints.');
			} else {
				$errorOutput->getStyle()->note("You are running with \"--xdebug\" enabled, and the Xdebug PHP extension is active.\nThe process will halt at breakpoints, but PHPStan will run much slower.\nUse this only if you are debugging PHPStan itself or your custom extensions.");
			}
		} elseif (XdebugHandler::isXdebugActive()) {
			$errorOutput->getStyle()->note('The Xdebug PHP extension is active, but "--xdebug" is not used. This may slow down performance and the process will not halt at breakpoints.');
		} elseif ($debugEnabled) {
			$v = XdebugHandler::getSkippedVersion();
			if ($v !== '') {
				$errorOutput->getStyle()->note(
					"The Xdebug PHP extension is active, but \"--xdebug\" is not used.\n" .
					"The process was restarted and it will not halt at breakpoints.\n" .
					'Use "--xdebug" if you want to halt at breakpoints.',
				);
			}
		}

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

			if (!str_contains($error['message'], 'Allowed memory size')) {
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

		/** @var array<callable>|false $autoloadFunctionsBefore */
		$autoloadFunctionsBefore = spl_autoload_functions();

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
			foreach (['phpstan.neon', 'phpstan.neon.dist', 'phpstan.dist.neon'] as $discoverableConfigName) {
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
		$containerFactory = new ContainerFactory($currentWorkingDirectory, true);
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
				'env' => getenv(),
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

			if (
				count($additionalConfigFiles) > 0
				&& $generatedConfigReflection->hasConstant('PHPSTAN_VERSION_CONSTRAINT')
			) {
				$generatedConfigPhpStanVersionConstraint = $generatedConfigReflection->getConstant('PHPSTAN_VERSION_CONSTRAINT');
				if ($generatedConfigPhpStanVersionConstraint !== null) {
					$phpstanSemverVersion = ComposerHelper::getPhpStanVersion();
					if (
						$phpstanSemverVersion !== ComposerHelper::UNKNOWN_VERSION
						&& !str_contains($phpstanSemverVersion, '@')
						&& !Semver::satisfies($phpstanSemverVersion, $generatedConfigPhpStanVersionConstraint)
					) {
						$errorOutput->writeLineFormatted('<error>Running PHPStan with incompatible extensions</error>');
						$errorOutput->writeLineFormatted('You\'re running PHPStan from a different Composer project');
						$errorOutput->writeLineFormatted('than the one where you installed extensions.');
						$errorOutput->writeLineFormatted('');
						$errorOutput->writeLineFormatted(sprintf('Your PHPStan version is: <fg=red>%s</>', $phpstanSemverVersion));
						$errorOutput->writeLineFormatted(sprintf('Installed PHPStan extensions support: %s', $generatedConfigPhpStanVersionConstraint));

						$errorOutput->writeLineFormatted('');
						if (isset($_SERVER['argv'][0]) && is_file($_SERVER['argv'][0])) {
							$mainScript = $_SERVER['argv'][0];
							$errorOutput->writeLineFormatted(sprintf('PHPStan is running from: %s', $currentWorkingDirectoryFileHelper->absolutizePath(dirname($mainScript))));
						}

						$errorOutput->writeLineFormatted(sprintf('Extensions were installed in: %s', dirname($generatedConfigDirectory, 3)));
						$errorOutput->writeLineFormatted('');

						$simpleRelativePathHelper = new SimpleRelativePathHelper($currentWorkingDirectory);
						$errorOutput->writeLineFormatted(sprintf('Run PHPStan with <fg=green>%s</> to fix this problem.', $simpleRelativePathHelper->getRelativePath(dirname($generatedConfigDirectory, 3) . '/bin/phpstan')));

						$errorOutput->writeLineFormatted('');
						throw new InceptionNotSuccessfulException();
					}
				}
			}
		}

		if (
			$projectConfigFile !== null
			&& $currentWorkingDirectoryFileHelper->normalizePath($projectConfigFile, '/') !== $currentWorkingDirectoryFileHelper->normalizePath(__DIR__ . '/../../conf/config.stubFiles.neon', '/')
		) {
			$additionalConfigFiles[] = $projectConfigFile;
		}

		$createDir = static function (string $path) use ($errorOutput): void {
			try {
				DirectoryCreator::ensureDirectoryExists($path, 0777);
			} catch (DirectoryCreatorException $e) {
				$errorOutput->writeLineFormatted($e->getMessage());
				throw new InceptionNotSuccessfulException();
			}
		};

		if (!isset($tmpDir)) {
			$tmpDir = sys_get_temp_dir() . '/phpstan';
			$createDir($tmpDir);
		}

		try {
			$container = $containerFactory->create($tmpDir, $additionalConfigFiles, $paths, $composerAutoloaderProjectPaths, $analysedPathsFromConfig, $level ?? self::DEFAULT_LEVEL, $generateBaselineFile, $autoloadFile);
		} catch (InvalidConfigurationException | AssertionException $e) {
			$errorOutput->writeLineFormatted('<error>Invalid configuration:</error>');
			$errorOutput->writeLineFormatted($e->getMessage());
			throw new InceptionNotSuccessfulException();
		} catch (InvalidIgnoredErrorPatternsException $e) {
			$errorOutput->writeLineFormatted(sprintf('<error>Invalid %s in ignoreErrors:</error>', count($e->getErrors()) === 1 ? 'entry' : 'entries'));
			foreach ($e->getErrors() as $error) {
				$errorOutput->writeLineFormatted($error);
				$errorOutput->writeLineFormatted('');
			}

			$errorOutput->writeLineFormatted('To ignore non-existent paths in ignoreErrors,');
			$errorOutput->writeLineFormatted('set <fg=cyan>reportUnmatchedIgnoredErrors: false</> in your configuration file.');
			$errorOutput->writeLineFormatted('');

			throw new InceptionNotSuccessfulException();
		} catch (InvalidExcludePathsException $e) {
			$errorOutput->writeLineFormatted(sprintf('<error>Invalid %s in excludePaths:</error>', count($e->getErrors()) === 1 ? 'entry' : 'entries'));
			foreach ($e->getErrors() as $error) {
				$errorOutput->writeLineFormatted($error);
				$errorOutput->writeLineFormatted('');
			}

			$errorOutput->writeLineFormatted('If the excluded path can sometimes exist, append <fg=cyan>(?)</>');
			$errorOutput->writeLineFormatted('to its config entry to mark it as optional.');
			$errorOutput->writeLineFormatted('');

			throw new InceptionNotSuccessfulException();
		} catch (ValidationException $e) {
			foreach ($e->getMessages() as $message) {
				$errorOutput->writeLineFormatted('<error>Invalid configuration:</error>');
				$errorOutput->writeLineFormatted($message);
			}
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
		} catch (DuplicateIncludedFilesException $e) {
			$format = "<error>These files are included multiple times:</error>\n- %s";
			if (count($e->getFiles()) === 1) {
				$format = "<error>This file is included multiple times:</error>\n- %s";
			}
			$errorOutput->writeLineFormatted(sprintf($format, implode("\n- ", $e->getFiles())));

			if (class_exists('PHPStan\ExtensionInstaller\GeneratedConfig')) {
				$errorOutput->writeLineFormatted('');
				$errorOutput->writeLineFormatted('It can lead to unexpected results. If you\'re using phpstan/extension-installer, make sure you have removed corresponding neon files from your project config file.');
			}

			throw new InceptionNotSuccessfulException();
		}

		if ($cleanupContainerCache) {
			$containerFactory->clearOldContainers($tmpDir);
		}

		/** @var bool|null $customRulesetUsed */
		$customRulesetUsed = $container->getParameter('customRulesetUsed');
		if ($customRulesetUsed === null) {
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
		} elseif ($customRulesetUsed) {
			$defaultLevelUsed = false;
		}

		foreach ($container->getParameter('bootstrapFiles') as $bootstrapFileFromArray) {
			self::executeBootstrapFile($bootstrapFileFromArray, $container, $errorOutput, $debugEnabled);
		}

		/** @var array<callable>|false $autoloadFunctionsAfter */
		$autoloadFunctionsAfter = spl_autoload_functions();

		if ($autoloadFunctionsBefore !== false && $autoloadFunctionsAfter !== false) {
			$newAutoloadFunctions = $GLOBALS['__phpstanAutoloadFunctions'] ?? [];
			foreach ($autoloadFunctionsAfter as $after) {
				foreach ($autoloadFunctionsBefore as $before) {
					if ($after === $before) {
						continue 2;
					}
				}

				$newAutoloadFunctions[] = $after;
			}

			$GLOBALS['__phpstanAutoloadFunctions'] = $newAutoloadFunctions;
		}

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

		if ($container->hasParameter('scopeClass') && $container->getParameter('scopeClass') !== MutatingScope::class) {
			$errorOutput->writeLineFormatted('⚠️  You\'re using a deprecated config option <fg=cyan>scopeClass</>. ⚠️️');
			$errorOutput->writeLineFormatted('');
			$errorOutput->writeLineFormatted(sprintf('Please implement PHPStan\Type\ExpressionTypeResolverExtension interface instead and register it as a service.'));
		}

		if ($projectConfig !== null) {
			$parameters = $projectConfig['parameters'] ?? [];
			/** @var bool $checkMissingIterableValueType */
			$checkMissingIterableValueType = $parameters['checkMissingIterableValueType'] ?? true;
			if (!$checkMissingIterableValueType) {
				$errorOutput->writeLineFormatted('⚠️  You\'re using a deprecated config option <fg=cyan>checkMissingIterableValueType</> ⚠️️');
				$errorOutput->writeLineFormatted('');

				$featureToggles = $container->getParameter('featureToggles');
				if (!((bool) $featureToggles['bleedingEdge'])) {
					$errorOutput->writeLineFormatted('It\'s strongly recommended to remove it from your configuration file');
					$errorOutput->writeLineFormatted('and add the missing array typehints.');
					$errorOutput->writeLineFormatted('');
				}

				$errorOutput->writeLineFormatted('If you want to continue ignoring missing typehints from arrays,');
				$errorOutput->writeLineFormatted('add <fg=cyan>missingType.iterableValue</> error identifier to your <fg=cyan>ignoreErrors</>:');
				$errorOutput->writeLineFormatted('');
				$errorOutput->writeLineFormatted('parameters:');
				$errorOutput->writeLineFormatted("\tignoreErrors:");
				$errorOutput->writeLineFormatted("\t\t-");
				$errorOutput->writeLineFormatted("\t\t\tidentifier: missingType.iterableValue");
				$errorOutput->writeLineFormatted('');
			}

			/** @var bool $checkGenericClassInNonGenericObjectType */
			$checkGenericClassInNonGenericObjectType = $parameters['checkGenericClassInNonGenericObjectType'] ?? true;
			if (!$checkGenericClassInNonGenericObjectType) {
				$errorOutput->writeLineFormatted('⚠️  You\'re using a deprecated config option <fg=cyan>checkGenericClassInNonGenericObjectType</> ⚠️️');
				$errorOutput->writeLineFormatted('');
				$errorOutput->writeLineFormatted('It\'s strongly recommended to remove it from your configuration file');
				$errorOutput->writeLineFormatted('and add the missing generic typehints.');
				$errorOutput->writeLineFormatted('');
				$errorOutput->writeLineFormatted('If you want to continue ignoring missing typehints from generics,');
				$errorOutput->writeLineFormatted('add <fg=cyan>missingType.generics</> error identifier to your <fg=cyan>ignoreErrors</>:');
				$errorOutput->writeLineFormatted('');
				$errorOutput->writeLineFormatted('parameters:');
				$errorOutput->writeLineFormatted("\tignoreErrors:");
				$errorOutput->writeLineFormatted("\t\t-");
				$errorOutput->writeLineFormatted("\t\t\tidentifier: missingType.generics");
				$errorOutput->writeLineFormatted('');
			}
		}

		$tempResultCachePath = $container->getParameter('tempResultCachePath');
		$createDir($tempResultCachePath);

		/** @var FileFinder $fileFinder */
		$fileFinder = $container->getService('fileFinderAnalyse');

		$pathRoutingParser = $container->getService('pathRoutingParser');

		$stubFilesProvider = $container->getByType(StubFilesProvider::class);

		$filesCallback = static function () use ($currentWorkingDirectoryFileHelper, $stubFilesProvider, $fileFinder, $pathRoutingParser, $paths, $errorOutput): array {
			if (count($paths) === 0) {
				$errorOutput->writeLineFormatted('At least one path must be specified to analyse.');
				throw new InceptionNotSuccessfulException();
			}
			$fileFinderResult = $fileFinder->findFiles($paths);
			$files = $fileFinderResult->getFiles();

			$pathRoutingParser->setAnalysedFiles($files);

			$stubFilesExcluder = new FileExcluder($currentWorkingDirectoryFileHelper, $stubFilesProvider->getProjectStubFiles(), true);

			$files = array_values(array_filter($files, static fn (string $file) => !$stubFilesExcluder->isExcludedFromAnalysing($file)));

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

}
