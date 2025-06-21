<?php // lint < 8.2

namespace ArrayColumn;

use DOMElement;
use function PHPStan\Testing\assertType;


class ArrayColumnTest
{
	/**
	 * @param non-empty-list<array<string, mixed>> $list
	 */
	public function testList1(array $list): void
	{
		assertType('non-empty-list<mixed>', array_column($list, 'value'));
	}
}
