<?php declare(strict_types = 1);

namespace Bug10544;

use function PHPStan\Testing\assertType;

class Boo {
	/**
	 * @param array{check1:bool,boo:string}|array{check2:bool,foo:string} $param
	 */
	public function foo(array $param): string {
		if (isset($param['check1'])) {
			assertType('array{check1: bool, boo: string}', $param);
			return $param['boo'];
		}
		assertType('array{check2: bool, foo: string}', $param);
		return $param['foo'];
	}
}
