<?php

namespace Bug2945;

use function PHPStan\Analyser\assertNativeType;
use function PHPStan\Analyser\assertType;

class A{
	/**
	 * @param \stdClass[] $blocks
	 *
	 * @return void
	 */
	public function doFoo(array $blocks){
		foreach($blocks as $b){
			if(!($b instanceof \stdClass)){
				assertType('*NEVER*', $b);
				assertNativeType('mixed~stdClass', $b);
				throw new \TypeError();
			}
			$pk = new \Exception();

			$pk->x = $b->x;
		}
	}

	/**
	 * @param \stdClass[] $blocks
	 *
	 * @return void
	 */
	public function doBar(array $blocks){
		foreach($blocks as $b){
			if(!($b instanceof \stdClass)){
				assertType('*NEVER*', $b);
				assertNativeType('mixed~stdClass', $b);
				throw new \TypeError();
			}
			$pk = new \Exception();

			$x = $b->x;
		}
	}
}
