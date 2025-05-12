<?php declare(strict_types = 1);

namespace PHPStan\File;

use function is_file;
use function strtoupper;

final class FilesystemHelper
{

	private static ?bool $isCaseSensitive = null;

	public static function isCaseSensitive(): bool
	{
		if (self::$isCaseSensitive === null) {
			self::$isCaseSensitive = is_file(__DIR__ . '/' . strtoupper(__FILE__));
		}
		return self::$isCaseSensitive;
	}

}
