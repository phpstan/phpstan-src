<?php declare(strict_types = 1);

namespace Bug11054;

class XXX {
	public const DEF_VALUE = [NAN];
	public const NO_VALUE  = [NAN];
}

class YYY
{
	public const LOSSLESS = [NAN];

	/**
	 * @param  string|int|mixed $substituteChar
	 */
	public static function convertEncoding (
		string $str,
		string $sourceEncoding,
		string $targetEncoding,
		$substituteChar = XXX::DEF_VALUE,
		$defValue       = XXX::NO_VALUE
	): string {
		if ($substituteChar === XXX::DEF_VALUE) { // no error expected
			return mb_convert_encoding($str, $targetEncoding, $sourceEncoding);
		}

		if ($substituteChar === self::LOSSLESS) { // no error expected
			return $str;
		}

		return $str;
	}
}

class SimpleTest {
	/** @param mixed $v */
	public static function test($v): void
	{
		if ($v === [NAN]) {} // no error expected
	}
}
