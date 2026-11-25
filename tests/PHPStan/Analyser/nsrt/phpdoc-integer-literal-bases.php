<?php declare(strict_types = 1);

namespace PhpDocIntegerLiteralBases;

use function PHPStan\Testing\assertType;

class Foo
{

	/**
	 * @param 0x1F $hex
	 * @param 0b101 $binary
	 * @param 0o17 $octal
	 * @param 017 $legacyOctal
	 * @param -0x10 $negativeHex
	 * @param 1_000 $underscores
	 * @param int<0, 0xFF> $range
	 * @param array{0x10: int, 0b11: string} $shape
	 */
	public function doFoo($hex, $binary, $octal, $legacyOctal, $negativeHex, $underscores, $range, $shape): void
	{
		assertType('31', $hex);
		assertType('5', $binary);
		assertType('15', $octal);
		assertType('15', $legacyOctal);
		assertType('-16', $negativeHex);
		assertType('1000', $underscores);
		assertType('int<0, 255>', $range);
		assertType('array{16: int, 3: string}', $shape);
		assertType('5|31', $this->doBar());
		assertType('array{16?: string}', $this->doBaz());

		/** @var -0b11|0o777 $var */
		$var = $this->doBar();
		assertType('-3|511', $var);
	}

	/** @return 0x1F|0b101 */
	public function doBar(): int
	{
		return 31;
	}

	/** @return array<0x10, string> */
	public function doBaz(): array
	{
		return [];
	}

}
