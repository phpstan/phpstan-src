<?php

namespace Bug2648;

use function PHPStan\Analyser\assertType;

class Foo
{

	/**
	 * @param bool[] $list
	 */
	public function doFoo(array $list): void
	{
		if (count($list) > 1) {
			assertType('int<2, max>', count($list));
			unset($list['fooo']);
			assertType('array<bool>', $list);
			assertType('int', count($list));
		}
	}

	/**
	 * @param bool[] $list
	 */
	public function doBar(array $list): void
	{
		if (count($list) > 1) {
			assertType('int<2, max>', count($list));
			foreach ($list as $key => $item) {
				assertType('int', count($list));
				if ($item === false) {
					unset($list[$key]);
					assertType('int', count($list));
				}

				if (count($list) === 1) {
					assertType('int', count($list));
					break;
				}
			}

			assertType('int', count($list));
		}

		assertType('int', count($list));
	}

}
