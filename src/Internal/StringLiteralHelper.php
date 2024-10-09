<?php declare(strict_types = 1);

namespace PHPStan\Internal;

use function addcslashes;
use function preg_match;
use function sprintf;
use function str_contains;

final class StringLiteralHelper
{

	public static function escape(string $string): string
	{
		return addcslashes($string, "\0..\37\177\\");
	}

	public static function escapeAndQuoteIfNeeded(string $string): string
	{
		if ($string === '') {
			return "''";
		}

		$escaped = self::escape($string);

		if (str_contains($escaped, '\\') || str_contains($escaped, "'")) {
			return $escaped === addcslashes($string, '\'\\')
				? sprintf("'%s'", $escaped)
				: sprintf('"%s"', addcslashes($escaped, '"'));
		}

		if (preg_match('/[!-,.\/:-@[-^`{|}~]/', $escaped) === 1 || preg_match('/\p{Zs}/u', $escaped) === 1) {
			return sprintf("'%s'", addcslashes($escaped, "\\'"));
		}

		return $escaped;
	}

	public static function quote(string $string): string
	{
		if ($string === '') {
			return "''";
		}

		$escaped = self::escape($string);

		if (str_contains($escaped, '\\') || str_contains($escaped, "'")) {
			return $escaped === addcslashes($string, '\'\\')
				? sprintf("'%s'", $escaped)
				: sprintf('"%s"', addcslashes($escaped, '"'));
		}

		return sprintf("'%s'", addcslashes($escaped, "\\'"));
	}

}
