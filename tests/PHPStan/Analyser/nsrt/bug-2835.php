<?php

namespace Bug2835AssertTypes;

use function PHPStan\Testing\assertType;

class Foo
{

	/**
	 * @param array<int, array> $tokens
	 * @return bool
	 */
	public function doFoo(array $tokens): bool {
		$i = 0;
		while (isset($tokens[$i])) {
			assertType('int<0, max>', $i);
			if ($tokens[$i]['code'] !== 1) {
				assertType('mixed~1', $tokens[$i]['code']);
				$i++;
				assertType('int<1, max>', $i);
				assertType('mixed', $tokens[$i]['code']);
				continue;
			}
			assertType('1', $tokens[$i]['code']);
			$i++;
			assertType('int<1, max>', $i);
			assertType('mixed', $tokens[$i]['code']);
			if ($tokens[$i]['code'] !== 2) {
				assertType('mixed~2', $tokens[$i]['code']);
				$i++;
				assertType('int<2, max>', $i);
				continue;
			}
			assertType('2', $tokens[$i]['code']);
			return true;
		}
		return false;
	}

}
