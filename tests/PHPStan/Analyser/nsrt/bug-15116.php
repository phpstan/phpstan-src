<?php declare(strict_types = 1);

namespace Bug15116;

use function PHPStan\Testing\assertType;

class CoffeeBreak
{
	/**
	 * @param array{0:string, 1:bool, 2:int} $args
	 */
	public function makeCoffee(array $args): int {
		// array with indexes:
		// 0 - string naming the coffee
		// 1 - boolean "retry", by reference, can set it to true if the coffee making can be retried
		// 2 - number of cups to make
		if ($args[0] === "cappucino") {
			// make only 1 cup at a time
			$cupsMade = 1;
			if ($args[2] > 1) {
				// tell the caller that they can retry (they wanted more cups)
				$args[1] = true;
			} else {
				$args[1] = false;
			}
		} else {
			// for all other types of coffee, make all the cups in the same call
			$cupsMade = $args[2];
			$args[1] = false;
		}
		return $cupsMade;
	}
}

function () {
	$retry = false;
	$cupsWanted = 10;
	$cb = new CoffeeBreak();
	$cupsMade = $cb->makeCoffee(["cappucino", &$retry, $cupsWanted]);
	assertType('bool', $retry);
	if ($retry) {
		$cupsRemaining = $cupsWanted - $cupsMade;
		echo "still need $cupsRemaining cups of coffee\n";
	}
};
