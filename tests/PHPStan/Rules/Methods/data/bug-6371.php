<?php

namespace Bug6371;

/**
 * @template T
 * @template K
 *
 * @method bool compare(T $t, K $k) Compares two objects magically somehow
 */
class HelloWorld
{
	public function __call(string $name, array $args) {
		return true;
	}
}

/**
 * @param HelloWorld<int, string> $hw
 * @return void
 */
function foo (HelloWorld $hw): void {
	$hw->compare(1, 'foo');
	$hw->compare(true, false);
};
