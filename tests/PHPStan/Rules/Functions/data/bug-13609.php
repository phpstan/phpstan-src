<?php declare(strict_types=1);

namespace Bug13609;

class Datehelper
{
	/**
	 * @param numeric-string $year
	 * @param numeric-string $month
	 * @param numeric-string $day
	 */
	public static function dateFormat(string $year, string $month, string $day): string
	{
		return \sprintf('%04d-%02d-%02d', $year, $month, $day);
	}

	/**
	 * @param numeric-string $value
	 */
	public static function formatFloat(string $value): string
	{
		return \sprintf('%f', $value);
	}
}
