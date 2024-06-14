<?php

namespace SplFixedArrayIteratorTypes;

class HelloWorld
{
	/**
	 * @var int[]|\SplFixedArray
	 * @phpstan-var \SplFixedArray<int>
	 */
	public $array;

	public function dump() : void{
		foreach($this->array as $id => $v){
			\PHPStan\Testing\assertType('int|null', $this->array[$id]);
			\PHPStan\Testing\assertType('int|null', $v);
		}
	}
}
