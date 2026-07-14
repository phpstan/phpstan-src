<?php declare(strict_types = 1);

namespace PHPStan\Internal;

use Nette\Utils\Json;
use Nette\Utils\JsonException;
use PHPStan\File\CouldNotReadFileException;
use PHPStan\File\FileReader;
use function basename;
use function getenv;
use function is_file;
use function is_string;
use function preg_match;
use function substr;
use function trim;

final class ComposerHelper
{

	public const UNKNOWN_VERSION = 'Unknown version';

	private static ?string $phpstanVersion = null;

	private static ?string $betterReflectionVersion = null;

	private static ?string $phpDocParserVersion = null;

	private static ?string $phpStormStubsVersion = null;

	/** @var array<string, mixed[]> */
	private static array $decodedCache = [];

	/** @var array<string, mixed>|null */
	private static ?array $installed;

	/** @return array<string, mixed>|null */
	public static function getComposerConfig(string $root): ?array
	{
		if (isset(self::$decodedCache[$root])) {
			return self::$decodedCache[$root];
		}

		$composerJsonPath = self::getComposerJsonPath($root);
		if ($composerJsonPath === null) {
			return null;
		}

		try {
			$composerJsonContents = FileReader::read($composerJsonPath);

			return self::$decodedCache[$root] ??= Json::decode($composerJsonContents, Json::FORCE_ARRAY);
		} catch (CouldNotReadFileException | JsonException) {
			return null;
		}
	}

	public static function getComposerJsonPath(string $root): ?string
	{
		$envComposer = getenv('COMPOSER');
		$fileName = is_string($envComposer) ? $envComposer : 'composer.json';
		$fileName = basename(trim($fileName));

		$path = $root . '/' . $fileName;
		if (!is_file($path)) {
			return null;
		}

		return $path;
	}

	/**
	 * @param array<string, mixed> $composerConfig
	 */
	public static function getVendorDirFromComposerConfig(string $root, array $composerConfig): string
	{
		$vendorDirectory = $composerConfig['config']['vendor-dir'] ?? 'vendor';

		return $root . '/' . trim($vendorDirectory, '/');
	}

	/**
	 * @param array<string, mixed> $composerConfig
	 */
	public static function getBinDirFromComposerConfig(string $root, array $composerConfig): string
	{
		$vendorDirectory = $composerConfig['config']['bin-dir'] ?? 'vendor/bin';

		return $root . '/' . trim($vendorDirectory, '/');
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function getInstalled(): array
	{
		return self::$installed ??= require __DIR__ . '/../../vendor/composer/installed.php';
	}

	public static function getPhpStanVersion(): string
	{
		if (self::$phpstanVersion !== null) {
			return self::$phpstanVersion;
		}

		$installed = self::getInstalled();
		$rootPackage = $installed['root'] ?? null;
		if ($rootPackage === null) {
			return self::$phpstanVersion = self::UNKNOWN_VERSION;
		}

		return self::$phpstanVersion = self::processPackageVersion($rootPackage);
	}

	public static function getBetterReflectionVersion(): string
	{
		if (self::$betterReflectionVersion !== null) {
			return self::$betterReflectionVersion;
		}

		$installed = self::getInstalled();
		$rootPackage = $installed['versions']['ondrejmirtes/better-reflection'] ?? null;
		if ($rootPackage === null) {
			return self::$betterReflectionVersion = self::UNKNOWN_VERSION;
		}

		return self::$betterReflectionVersion = self::processPackageVersion($rootPackage);
	}

	public static function getPhpDocParserVersion(): string
	{
		if (self::$phpDocParserVersion !== null) {
			return self::$phpDocParserVersion;
		}

		$installed = self::getInstalled();
		$rootPackage = $installed['versions']['phpstan/phpdoc-parser'] ?? null;
		if ($rootPackage === null) {
			return self::$phpDocParserVersion = self::UNKNOWN_VERSION;
		}

		return self::$phpDocParserVersion = self::processPackageVersion($rootPackage);
	}

	public static function getPhpStormStubsVersion(): string
	{
		if (self::$phpStormStubsVersion !== null) {
			return self::$phpStormStubsVersion;
		}

		$installed = self::getInstalled();
		$package = $installed['versions']['jetbrains/phpstorm-stubs'] ?? null;
		if ($package === null) {
			return self::$phpStormStubsVersion = self::UNKNOWN_VERSION;
		}

		return self::$phpStormStubsVersion = self::processPackageVersion($package);
	}

	/**
	 * @param array<string, mixed> $package
	 * @return string
	 */
	private static function processPackageVersion(array $package): string
	{
		if (preg_match('/[^v\d.]/', $package['pretty_version']) === 0) {
			// Handles tagged versions, see https://github.com/Jean85/pretty-package-versions/blob/2.0.5/src/Version.php#L31
			return $package['pretty_version'];
		}

		return $package['pretty_version'] . '@' . substr((string) $package['reference'], 0, 7);
	}

}
