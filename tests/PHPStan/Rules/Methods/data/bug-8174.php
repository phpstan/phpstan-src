<?php declare(strict_types = 1);

namespace Bug8174;

use function PHPStan\Testing\assertType;

class HelloWorld
{
	/**
	 * @param list<string> $list
	 * @return list<string>
	 */
	public function filterList(array $list): array {
		$filtered = array_filter($list, function ($elem) {
			return $elem === '23423';
		});
		assertType("array<int<0, max>, '23423'>", $filtered); // this is not a list
		assertType("list<'23423'>", array_values($filtered)); // this is a list

		// why am I allowed to return not a list then?
		return $filtered;
	}
}
