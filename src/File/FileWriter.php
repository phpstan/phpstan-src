<?php declare(strict_types = 1);

namespace PHPStan\File;

use function error_get_last;
use function file_put_contents;

final class FileWriter
{

	public static function write(string $fileName, string $contents): void
	{
		$success = @file_put_contents($fileName, $contents);
		if ($success === false) {
			$error = error_get_last();

			throw new CouldNotWriteFileException(
				$fileName,
				$error !== null ? $error['message'] : 'unknown cause',
			);
		}
	}

}
