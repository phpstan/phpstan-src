<?php declare(strict_types = 1);

namespace PHPStan\Internal;

use PHPStan\Turbo\CompiledIntoTurboExtension;
use function str_replace;

#[CompiledIntoTurboExtension]
final class SprintfHelper
{

	public static function escapeFormatString(string $format): string
	{
		return str_replace('%', '%%', $format);
	}

}
