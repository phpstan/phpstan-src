<?php

namespace Bug14814;

use function PHPStan\Testing\assertType;

class CoffeeBreak
{

	/**
	 * @param int<0, 5> $range
	 */
	public function doFoo(int $i, int $range): void
	{
		switch ($i) {
			case $range:
				assertType('int<0, 5>', $i);
				if ($i === 0) {
					echo 'zero';
					return;
				}
				assertType('int<1, 5>', $i);
			case 0:
				break;
		}
	}

	/**
	 * @param int<0, 5> $range
	 */
	public function doBar(int $i, int $range): void
	{
		switch ($i) {
			case $range:
				// the case condition value must not be narrowed to truthy
				assertType('int<0, 5>', $range);
				break;
		}
	}

	/**
	 * @param ''|'a'|'b' $s
	 */
	public function doBaz(string $x, string $s): void
	{
		switch ($x) {
			case $s:
				assertType('\'\'|\'a\'|\'b\'', $s);
				break;
		}
	}

}
