<?php

namespace Bug11602;

class HelloWorld
{
	public function parseRef(string $coordinate, string $ref): string
	{
		if (preg_match('/^([A-Z]{1,3})([0-9]{1,7})(:([A-Z]{1,3})([0-9]{1,7}))?$/', $ref, $matches) !== 1) {
			return $ref;
		}
		if (!isset($matches[3])) { // single cell, not range
			return $coordinate;
		}
		$minRow = (int) $matches[2];
		$maxRow = (int) $matches[5];
		$rows = $maxRow - $minRow + 1;
		$minCol = $matches[1];
		$maxCol = $matches[4];

		return "$minCol$minRow:$maxCol$maxRow";
	}
}
