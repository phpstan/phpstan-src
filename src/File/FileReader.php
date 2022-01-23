<?php declare(strict_types = 1);

namespace PHPStan\File;

use function file_get_contents;
use function is_file;

class FileReader
{

	public static function read(string $fileName): string
	{
		if (!is_file($fileName)) {
			throw new CouldNotReadFileException($fileName);
		}
		$contents = @file_get_contents($fileName);
		if ($contents === false) {
			throw new CouldNotReadFileException($fileName);
		}

		return $contents;
	}

}
