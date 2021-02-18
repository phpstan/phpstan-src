<?php

namespace Bug1613;

class TestClass
{
	public function test(string $index)
	{
		$array = [
			"123" => "test"
		];
		return array_key_exists($index, $array);
	}
}
