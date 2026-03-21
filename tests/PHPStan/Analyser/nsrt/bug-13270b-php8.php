<?php // lint >= 8.0

declare(strict_types=1);

namespace Bug13270bPhp8;

use function PHPStan\Testing\assertType;

class Test
{
	/**
	 * @param mixed[] $data
	 * @return mixed[]
	 */
	public function parseData(array $data): array
	{
		if (isset($data['price'])) {
			assertType('mixed~null', $data['price']);
			if (!array_key_exists('priceWithVat', $data['price'])) {
				$data['price']['priceWithVat'] = null;
			}
			assertType("non-empty-array&hasOffsetValue('priceWithVat', mixed)", $data['price']);
			if (!array_key_exists('priceWithoutVat', $data['price'])) {
				$data['price']['priceWithoutVat'] = null;
			}
			assertType("non-empty-array&hasOffsetValue('priceWithoutVat', mixed)&hasOffsetValue('priceWithVat', mixed)", $data['price']);
		}
		return $data;
	}
}
