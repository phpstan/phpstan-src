<?php

namespace Php73Functions;

class Foo
{

	/**
	 * @param $mixed
	 * @param int $integer
	 * @param array $mixedArray
	 * @param array $nonEmptyArray
	 * @param array<string, mixed> $arrayWithStringKeys
	 * @param array{a?: 0, b: 1, c: 2} $constantArrayOptionalKeys1
	 * @param array{a: 0, b?: 1, c: 2} $constantArrayOptionalKeys2
	 * @param array{a: 0, b: 1, c?: 2} $constantArrayOptionalKeys3
	 */
	public function doFoo(
		$mixed,
		int $integer,
		array $mixedArray,
		array $nonEmptyArray,
		array $arrayWithStringKeys,
		array $constantArrayOptionalKeys1,
		array $constantArrayOptionalKeys2,
		array $constantArrayOptionalKeys3
	)
	{
		if (count($nonEmptyArray) === 0) {
			return;
		}

		$emptyArray = [];
		$literalArray = [1, 2, 3];
		$anotherLiteralArray = $literalArray;
		if (rand(0, 1) === 0) {
			$anotherLiteralArray[] = 4;
		}

		/** @var bool $bool */
		$bool = doBar();

		$hrtime1 = hrtime();
		$hrtime2 = hrtime(false);
		$hrtime3 = hrtime(true);
		$hrtime4 = hrtime($bool);

		die;
	}

}
