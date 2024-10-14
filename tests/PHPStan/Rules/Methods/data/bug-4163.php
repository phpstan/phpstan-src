<?php declare(strict_types = 1);

namespace Bug4163;

class HelloWorld
{
	/**
	 * @phpstan-var numeric
	 */
	public $lall = 0;

	/**
	 * @phpstan-return array<string, string>
	 */
	function lall() {
		$helloCollection = [new HelloWorld(), new HelloWorld()];
		$result = [];

		foreach ($helloCollection as $hello) {
			$key = (string)$hello->lall;

			if (!isset($result[$key])) {
				$lall = 'do_something_here';
				$result[$key] = $lall;
			}
		}

		return $result;
	}
}
