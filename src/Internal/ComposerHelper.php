<?php declare(strict_types = 1);

namespace PHPStan\Internal;

use Composer\InstalledVersions;
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

	/** @return array<string, mixed> */
	public static function getComposerConfig(string $root): ?array
	{
		$composerJsonPath = self::getComposerJsonPath($root);

		if (!is_file($composerJsonPath)) {
			return null;
		}

		try {
			$composerJsonContents = FileReader::read($composerJsonPath);

			return Json::decode($composerJsonContents, Json::FORCE_ARRAY);
		} catch (CouldNotReadFileException | JsonException) {
			return null;
		}
	}

	private static function getComposerJsonPath(string $root): string
	{
		$envComposer = getenv('COMPOSER');
		$fileName = is_string($envComposer) ? $envComposer : 'composer.json';
		$fileName = basename(trim($fileName));

		return $root . '/' . $fileName;
	}

	/**
	 * @param array<string, mixed> $composerConfig
	 */
	public static function getVendorDirFromComposerConfig(string $root, array $composerConfig): string
	{
		$vendorDirectory = $composerConfig['config']['vendor-dir'] ?? 'vendor';

		return $root . '/' . trim($vendorDirectory, '/');
	}

	public static function getPhpStanVersion(): string
	{
		$rootPackage = InstalledVersions::getRootPackage();

		if (preg_match('/[^v\d.]/', $rootPackage['pretty_version']) === 0) {
			// Handles tagged versions, see https://github.com/Jean85/pretty-package-versions/blob/2.0.5/src/Version.php#L31
			return $rootPackage['pretty_version'];
		}

		return $rootPackage['pretty_version'] . '@' . substr((string) $rootPackage['reference'], 0, 7);
	}

}
