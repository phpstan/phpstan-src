<?php declare(strict_types = 1);

namespace Bug4570;

trait MyLogic
{
	public function fetchValue() : string {
		if (array_key_exists('valueToFetch', self::MY_CONST_ARRAY)) {
			return self::MY_CONST_ARRAY['valueToFetch'];
		}

		return 'defaultValue';
	}
}

final class FirstConsumer
{
	use MyLogic;

	private const MY_CONST_ARRAY = [
		'someValue'    => 'abc',
		'valueToFetch' => '123',
	];
}

final class SecondConsumer
{
	use MyLogic;

	private const MY_CONST_ARRAY = [
		'someValue'      => 'abc',
		'someOtherValue' => '123',
	];
}
