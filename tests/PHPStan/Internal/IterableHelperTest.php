<?php declare(strict_types = 1);

namespace PHPStan\Internal;

use Generator;
use PHPUnit\Framework\TestCase;
use function iterator_to_array;

class IterableHelperTest extends TestCase
{

	public function testYieldValuesReturnsGenerator(): void
	{
		$values = [['fact'], ['another fact']];
		$iterator = IterableHelper::yieldValues($values);

		$this->assertInstanceOf(Generator::class, $iterator);
		$this->assertSame($values, iterator_to_array($iterator));
	}

}
