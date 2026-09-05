<?php declare(strict_types = 1);

namespace PHPStan\Testing;

use PHPStan\DependencyInjection\Container;
use PHPStan\DependencyInjection\ContainerFactory;
use PHPStan\File\FileHelper;
use PHPStan\Internal\DirectoryCreator;
use PHPStan\Internal\DirectoryCreatorException;
use RuntimeException;
use function array_merge;
use function hash;
use function implode;
use function sys_get_temp_dir;
use const PHP_VERSION_ID;

trait PHPStanTestCaseTrait
{

	/** @var array<string, Container> */
	private static array $containers = [];

	public static function getContainer(): Container
	{
		$additionalConfigFiles = [self::getBaseConfigFile()];
		foreach (static::getAdditionalConfigFiles() as $configFile) {
			$additionalConfigFiles[] = $configFile;
		}

		return self::getContainerForConfigFiles($additionalConfigFiles);
	}

	/**
	 * Installs the global state of the container built from just the base config -
	 * the bleeding edge toggle, the static reflection provider, the PhpVersion and
	 * the type caches all live in static properties that the last initialized
	 * container owns.
	 *
	 * PHPUnit >= 10 initializes the right container before every data provider and
	 * every test (see PHPStanPHPUnitExtension). PHPUnit 9 - which the "Tests with old
	 * PHPUnit" jobs run - rejects that extension and has no hook that fires before a
	 * data provider, while ParaTest reuses one worker process for many test files. A
	 * class whose container turns on e.g. bleeding edge would therefore leak that
	 * state into the data providers of the next test file, which build Type objects
	 * that read the toggle in their constructor. Restoring the base state when a test
	 * class is done keeps those data providers deterministic on every supported
	 * PHPUnit version.
	 */
	public static function restoreBaseContainer(): void
	{
		self::getContainerForConfigFiles([self::getBaseConfigFile()]);
	}

	/**
	 * @param string[] $additionalConfigFiles
	 */
	private static function getContainerForConfigFiles(array $additionalConfigFiles): Container
	{
		$cacheKey = hash('sha256', implode("\n", $additionalConfigFiles));

		if (!isset(self::$containers[$cacheKey])) {
			$tmpDir = sys_get_temp_dir() . '/phpstan-tests';
			try {
				DirectoryCreator::ensureDirectoryExists($tmpDir, 0777);
			} catch (DirectoryCreatorException $e) {
				throw new RuntimeException($e->getMessage(), previous: $e);
			}

			$rootDir = __DIR__ . '/../..';
			$fileHelper = new FileHelper($rootDir);
			$rootDir = $fileHelper->normalizePath($rootDir, '/');
			$containerFactory = new ContainerFactory($rootDir);
			$container = $containerFactory->create($tmpDir, array_merge([
				$containerFactory->getConfigDirectory() . '/config.level8.neon',
			], $additionalConfigFiles), []);
			self::$containers[$cacheKey] = $container;

			foreach ($container->getParameter('bootstrapFiles') as $bootstrapFile) {
				(static function (string $file) use ($container): void {
					require_once $file;
				})($bootstrapFile);
			}

			if (PHP_VERSION_ID >= 80000) {
				require_once __DIR__ . '/../../stubs/runtime/Enum/UnitEnum.php';
				require_once __DIR__ . '/../../stubs/runtime/Enum/BackedEnum.php';
				require_once __DIR__ . '/../../stubs/runtime/Enum/ReflectionEnum.php';
				require_once __DIR__ . '/../../stubs/runtime/Enum/ReflectionEnumUnitCase.php';
				require_once __DIR__ . '/../../stubs/runtime/Enum/ReflectionEnumBackedCase.php';
			}
		} else {
			ContainerFactory::postInitializeContainer(self::$containers[$cacheKey]);
		}

		return self::$containers[$cacheKey];
	}

	private static function getBaseConfigFile(): string
	{
		return __DIR__ . '/TestCase.neon';
	}

	/**
	 * @return string[]
	 */
	public static function getAdditionalConfigFiles(): array
	{
		return [];
	}

	public static function getFileHelper(): FileHelper
	{
		return self::getContainer()->getByType(FileHelper::class);
	}

}
