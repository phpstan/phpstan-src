<?php

namespace Bug6081;

class HelloWorld
{
	/** @param mixed[]|string $thing */
	public function something($thing) : void{}

	/** @param int[] $array */
	public function sayHello(array $array): void
	{
		$this->something($array);
		if(count($array) !== 0){
			$this->something($array);
		}
	}

}
