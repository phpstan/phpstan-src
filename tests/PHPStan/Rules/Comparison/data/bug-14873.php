<?php declare(strict_types = 1);

namespace Bug14873Rule;

class HelloWorld
{

	/**
	 * @param 'a'|'b'|'c' $full
	 * @param 'a'|'b' $subset
	 * @param 'a'|'x' $partial
	 */
	public function sayHello(string $full, string $subset, string $partial): void
	{
		$a = ['a', 'b', 'c'];

		if (in_array($full, $a, true)) {
			echo 'full';
		}

		if (in_array($subset, $a, true)) {
			echo 'subset';
		}

		if (in_array($partial, $a, true)) {
			echo 'partial';
		}
	}

}
