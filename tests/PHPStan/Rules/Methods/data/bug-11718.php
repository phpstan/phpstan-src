<?php declare(strict_types = 1);

namespace Bug11718;

class Foo
{
	/**
	 * @return list<string>
	 */
	public function test(string $a, string $b): array
	{
		$list = ['x', 'y', $a, $b];

		unset($list[3]); // no error if uncommented!

		return $list;
	}
}
