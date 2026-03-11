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
		$additionalConfigFiles = [__DIR__ . '/TestCase.neon'];
		foreach (static::getAdditionalConfigFiles() as $configFile) {
			$additionalConfigFiles[] = $configFile;
		}
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
				(static function (string $file): void {
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
