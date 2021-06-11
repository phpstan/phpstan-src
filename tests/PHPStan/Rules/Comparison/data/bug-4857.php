<?php // lint >= 8.0

namespace Bug4857;

use function PHPStan\Testing\assertType;

class Foo
{

	function format(int $seconds): void
	{
		$minutes = (int) \round($seconds / 60);
		match(true) {
			$minutes < 60 => assertType('int<min, 59>', $minutes),
			$minutes < 90 => assertType('int<60, 89>', $minutes),
			$minutes < 150 => assertType('int<90, 149>', $minutes),
		};
	}

	function format2(int $seconds): void
	{
		$minutes = (int) \round($seconds / 60);
		match(true) {
			$minutes <= 60 => assertType('int<min, 60>', $minutes),
			$minutes <= 90 => assertType('int<61, 90>', $minutes),
			$minutes <= 150 => assertType('int<91, 150>', $minutes),
		};
	}

	public function sayHello(): void
	{
		/** @var int */
		$x = 5; // int<min,max>

		match(true) {
			$x < 60 => assertType('int<min, 59>', $x),
			$x < 90 => assertType('int<60, 89>', $x),
			default => assertType('int<90, max>', $x),
		};
	}

}
