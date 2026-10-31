<?php

namespace Bug2648;

use function PHPStan\Testing\assertType;

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
			assertType("array<mixed~'fooo', bool>", $list);
			assertType('int<0, max>', count($list));
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
				assertType('0|int<2, max>', count($list));
				if ($item === false) {
					unset($list[$key]);
					assertType('int<0, max>', count($list));
				}

				assertType('int<0, max>', count($list));

				if (count($list) === 1) {
					assertType('1', count($list));
					$list[] = false;
					assertType('int<1, max>', count($list));
					break;
				}
			}

			assertType('int<0, max>', count($list));
		}

		assertType('int<0, max>', count($list));
	}

}
