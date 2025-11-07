<?php

namespace Bug13676;

class HelloWorld
{
	/**
	 * @param array<array<string, int>> $rows
	 *
	 * @return array<array<string, int|string>>
	 */
	private function prepareExpectedRows(array $rows): array
	{
		if (rand(0,1)) {
			return $rows;
		}

		if (rand(0,1)) {
			foreach ($rows as &$row) {
				foreach ($row as &$value) {
					$value = (string) $value;
				}
			}
		}

		if (rand(0,1)) {
			return $rows;
		}

		foreach ($rows as &$row) {
			$row = array_change_key_case($row, CASE_UPPER);
		}

		return $rows;
	}
}
