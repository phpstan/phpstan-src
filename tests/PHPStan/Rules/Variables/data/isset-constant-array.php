<?php declare(strict_types = 1);

namespace IssetConstantArray;

class HelloWorld
{
	/**
	 * @param list{0: string, 1: string, 2?: string, 3?: string, 4?: string} $list
	 */
	public function sayHello(array $list): bool
	{
		if (isset($list[3])) {
			return isset($list[2]); // offset 3 implies offset 2;
		}

		if (isset($list[4])) {
			return isset($list[3]); // offset 4 cannot be set - offset 3 is not (see above), and the list is contiguous
		}

		return false;
	}

	/**
	 * @param list{0: string, 1: string, 2?: string, 3?: string, 4?: string} $list
	 */
	public function sayGoodbye(array $list): bool
	{
		if (isset($list[4])) {
			return isset($list[3]); // offset 4 implies offset 3;
		}

		return false;
	}
}
