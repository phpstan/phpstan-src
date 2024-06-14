<?php

namespace Bug4695;

use function PHPStan\Testing\assertType;

class Foo
{

	/**
	 * @param int|null $u
	 * @param int|null $a
	 * @return array<int, int>
	 */
	function foo($u=null, $a=null){
		if (is_null($u) && is_null($a)){
			return [0,0];
		}
		if ($u){
			$a = $u;
		}else if ($a){
			$u = $a;
		}
		assertType('int|null', $u);
		assertType('int|null', $a);
		return [$u, $a];
	}

	/**
	 * @param int|null $u
	 * @param int|null $a
	 * @return array<int, int>
	 */
	function bar($u=null, $a=null){
		if (!$u && !$a){
			return [0,0];
		}
		if ($u){
			$a = $u;
		}else if ($a){
			$u = $a;
		}
		assertType('int<min, -1>|int<1, max>', $u);
		assertType('int<min, -1>|int<1, max>', $a);
		return [$u, $a];
	}

	/**
	 * @param int|null $u
	 * @param int|null $a
	 * @return array<int, int>
	 */
	function baz($u=null, $a=null){
		if (is_null($u) && is_null($a)){
			return [0,0];
		}
		if ($u !== null){
			$a = $u;
		}else if ($a !== null){
			$u = $a;
		}
		assertType('int', $u);
		assertType('int', $a);
		return [$u, $a];
	}

}
