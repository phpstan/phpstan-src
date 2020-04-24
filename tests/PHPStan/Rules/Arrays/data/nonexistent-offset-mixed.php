<?php

namespace NonexistentOffsetMixed;

class Foo
{
	/**
	 * @param mixed $foo
	 */
	public function nonexistentOffsetOnArray($foo, $bar)
	{
		$array = [
			'a' => new \stdClass(),
			2,
		];

		echo $array[$foo];
		echo $array[$bar];
	}

}
