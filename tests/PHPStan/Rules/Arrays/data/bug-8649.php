<?php

namespace Bug8649;

class HelloWorld
{
	public function test(): void
	{
		/** @var array{array{a: ?string}, array{b: ?string},} $test */
		$test = [
			['a' => 'test'],
			['b' => 'asdf'],
		];

		foreach ($test as $property) {
			$firstKey = array_key_first($property);

			if ($firstKey === 'b') {
				continue;
			}

			echo($property[$firstKey]);
		}
	}
}
