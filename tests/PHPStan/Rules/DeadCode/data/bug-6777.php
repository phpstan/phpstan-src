<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug6777;

class HelloWorld
{
	/** @param \ArrayObject<int, string> $array */
	public function __construct(private \ArrayObject $array){}

	public function send(string $s) : void{
		$this->array[] = $s;
	}
}
