<?php

namespace PrestashopBreakdownsEmptyArray;

use function PHPStan\Testing\assertType;

class Foo
{

	/**
	 * @param mixed[] $arrayMixed
	 */
	public function getTaxBreakdown($mixed, $arrayMixed): void
	{
		$breakdowns = [
			'product_tax' => $mixed,
			'shipping_tax' => $arrayMixed,
			'ecotax_tax' => $arrayMixed,
			'wrapping_tax' => $arrayMixed,
		];

		foreach ($breakdowns as $type => $bd) {
			if (empty($bd)) {
				assertType('array{product_tax?: mixed, shipping_tax?: array, ecotax_tax?: array, wrapping_tax?: array}', $breakdowns);
				unset($breakdowns[$type]);
				assertType('array{product_tax?: mixed, shipping_tax?: array, ecotax_tax?: array, wrapping_tax?: array}', $breakdowns);
			}
		}

		assertType('array{product_tax?: mixed, shipping_tax?: array, ecotax_tax?: array, wrapping_tax?: array}', $breakdowns);
	}

	public function doFoo(): void
	{
		$a = ['foo' => 1, 'bar' => 2];
		assertType('array{foo: 1, bar: 2}', $a);
		unset($a['foo']);
		assertType('array{bar: 2}', $a);
	}

}
