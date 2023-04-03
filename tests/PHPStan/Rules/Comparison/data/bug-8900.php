<?php // lint >= 8.0

namespace Bug8900;

class Foo
{

	public function doFoo(): void
	{
		$test_array = [];
		for($index = 0; $index++; $index < random_int(1,100)) {
			$test_array[] = 'entry';
		}

		foreach($test_array as $key => $value) {
			$key_mod_4 = match($key % 4) {
				0 => '0',
				1 => '1',
				2 => '2',
				3 => '3',
			};
		}
	}

}
