<?php declare(strict_types=1);

namespace Bug12115;

class HelloWorld
{
	public function testMe(): void
	{
		$indexes = [1, 2, 3, 4, 5];
		$loopArray = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];
		$counterArray = [];


		foreach ($indexes as $index) {
			// Check if the index is not set in the counter array
			if (!isset($counterArray[$index])) {
				$counterArray[$index] = ['string_index_1' => 0, 'string_index_2' => 0];
			}

			foreach ($loopArray as $loop) {
				$stringIndex = $loop % 2 === 0 ? 'string_index_1' : 'string_index_2';
				$counterArray[$index][$stringIndex]++;
			}
		}

	}
}
