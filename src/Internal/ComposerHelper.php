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
use function trim;

final class ComposerHelper
{

	public static function getComposerJsonPath(string $root): string
	{
		$envComposer = getenv('COMPOSER');
		$fileName = is_string($envComposer) ? $envComposer : 'composer.json';
		$fileName = basename(trim($fileName));

		return $root . '/' . $fileName;
	}

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

	/**
	 * @param array<string, mixed> $composerConfig
	 */
	public static function getVendorDirFromComposerConfig(string $root, array $composerConfig): string
	{
		$vendorDirectory = $composerConfig['config']['vendor-dir'] ?? 'vendor';

		return $root . '/' . trim($vendorDirectory, '/');
	}

}
