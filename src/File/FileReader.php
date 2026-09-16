<?php declare(strict_types = 1);

namespace PHPStan\File;

use PHPStan\Turbo\ReferencedByTurboExtension;
use function file_get_contents;
use function stream_resolve_include_path;

#[ReferencedByTurboExtension(key: 'fileReader')]
final class FileReader
{

	/**
	 * @throws CouldNotReadFileException
	 */
	public static function read(string $fileName): string
	{
		$path = $fileName;

		$contents = @file_get_contents($path);
		if ($contents === false) {
			$path = stream_resolve_include_path($fileName);

			if ($path === false) {
				throw new CouldNotReadFileException($fileName);
			}

			$contents = @file_get_contents($path);
		}

		if ($contents === false) {
			throw new CouldNotReadFileException($fileName);
		}

		return $contents;
	}

}
