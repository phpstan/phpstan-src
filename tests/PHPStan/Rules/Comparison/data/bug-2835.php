<?php

namespace Bug2835;

class Foo
{

	/**
	 * @param array<int, array> $tokens
	 * @return bool
	 */
	public function doFoo(array $tokens): bool {
		$i = 0;
		while (isset($tokens[$i])) {
			if ($tokens[$i]['code'] !== 1) {
				$i++;
				continue;
			}
			$i++;
			if ($tokens[$i]['code'] !== 2) {
				$i++;
				continue;
			}
			return true;
		}
		return false;
	}

}
