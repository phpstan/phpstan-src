<?php declare(strict_types = 1);

namespace Bug3364;

class HelloWorld
{
	/**
	 * @param string|array<int|string>|null $value
	 */
	public function transform($value) {
		$value != 1;
	}

	/**
	 * @param array<int|string>|null $value
	 */
	public function transform2($value) {
		$value != 1;
	}


	/**
	 * @param object|null $value
	 */
	public function transform3($value) {
		$value != 1;
	}

	/**
	 * @param array<int|string>|object $value
	 */
	public function transform4($value) {
		$value != 1;
	}

	/**
	 * @param null $value
	 */
	public function transform5($value) {
		$value != 1;
	}
}
