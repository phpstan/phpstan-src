<?php declare(strict_types = 1);

namespace PHPStan\Internal;

use function str_replace;

class SprintfHelper
{

	public static function escapeFormatString(string $format): string
	{
		return str_replace('%', '%%', $format);
	}

}
