<?php declare(strict_types = 1);

namespace PHPStan\Internal;

use PHPStan\Turbo\ShadowedByTurboExtension;
use function str_replace;

#[ShadowedByTurboExtension(implementation: __DIR__ . '/../../turbo-ext/src/SprintfHelper.cpp')]
final class SprintfHelper
{

	public static function escapeFormatString(string $format): string
	{
		return str_replace('%', '%%', $format);
	}

}
