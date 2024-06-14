<?php

namespace Bug3024;

use function PHPStan\Testing\assertType;

interface A { function getUser() : U; }
interface U { }

class HelloWorld
{
	/** @param U[]|A[] $arr */
	public function foo($arr) : void
	{
		foreach($arr as $elt) {
			$admin = ($elt instanceof A);
			if($elt instanceof A) {
				$u = $elt->getUser();
				assertType('Bug3024\A', $elt);
			} else {
				$u = $elt;
			}
		}
	}

	/** @param U[]|A[] $arr */
	public function bar($arr) : void
	{
		foreach($arr as $elt) {
			if($admin = ($elt instanceof A)) {
				assertType('Bug3024\A', $elt);
			} else {
				$u = $elt;
			}
		}
	}

	/** @param U[]|A[] $arr */
	public function baz($arr) : void
	{
		foreach($arr as $elt) {
			$admin = ($elt instanceof A);
			if($admin) {
				assertType('Bug3024\A', $elt);
			} else {
				$u = $elt;
			}
		}
	}
}
