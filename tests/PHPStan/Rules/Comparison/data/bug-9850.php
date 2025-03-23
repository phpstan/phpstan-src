<?php

namespace Bug9850;

class CsvImport
{
	public function imporContent(): void
	{
		$lineCount = 0;
		$inputArray = ['a', 'b', 'c', 'd', 'e', 'f'];
		$datasets = [];
		foreach ($inputArray as $bnn3Line) {
			++$lineCount;
			if ($lineCount === 1) {
				// special treatment for line number 1
			} else {
				$datasets[] = $bnn3Line;
				if (count($datasets) >= 3) {
					// todo
				}
			}
		}
	}
}
