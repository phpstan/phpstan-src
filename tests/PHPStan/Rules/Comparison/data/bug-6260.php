<?php // lint >= 8.0

namespace Bug6260;

class Foo{
	public function __construct(
		/** @var non-empty-array<mixed> */
		private array $array
	){
		if(count($array) === 0){
			throw new \InvalidArgumentException();
		}
	}
}
