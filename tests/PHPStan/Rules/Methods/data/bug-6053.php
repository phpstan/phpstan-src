<?php

namespace Bug6053;

class HelloWorld
{
	/**
	 * @param ?mixed $items
	 *
	 * @return ?array<mixed>
	 */
	public function processItems($items): ?array
	{
		if ($items === null || !is_array($items)) {
			return null;
		}

		if ($items === []) {
			return [];
		}

		$result = [];
		foreach ($items as $item) {
			$result[] = 'something';
		}

		return $result;
	}
}
