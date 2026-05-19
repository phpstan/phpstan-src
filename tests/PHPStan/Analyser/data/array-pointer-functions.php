<?php

namespace ResetDynamicReturnTypeExtension;

use function PHPStan\Testing\assertType;

class Foo
{

	/**
	 * @param \stdClass[] $generalArray
	 * @param mixed $somethingElse
	 */
	public function doFoo(array $generalArray, $somethingElse)
	{
		$emptyConstantArray = [];
		$constantArray = [
			'a' => 1,
			'b' => 2,
		];
		/** @var array{a?: 0, b: 1, c: 2} $constantArrayOptionalKeys1 */
		$constantArrayOptionalKeys1 = [];
		/** @var array{a: 0, b?: 1, c: 2} $constantArrayOptionalKeys2 */
		$constantArrayOptionalKeys2 = [];
		/** @var array{a: 0, b: 1, c?: 2} $constantArrayOptionalKeys3 */
		$constantArrayOptionalKeys3 = [];

		$conditionalArray = ['foo', 'bar'];
		if (doFoo()) {
			array_unshift($conditionalArray, 'baz');
		}

		$secondConditionalArray = ['foo', 'bar'];
		if (doFoo()) {
			$secondConditionalArray[] = 'baz';
		}
		die;
	}

}
