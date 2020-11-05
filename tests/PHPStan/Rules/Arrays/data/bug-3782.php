<?php

namespace Bug3782;

class HelloWorld
{
	/** @param mixed[] $data */
	public function sayHello(array $data): void
	{
		foreach($data as $key => $value){
			$this[$key] = $value;
		}
	}
}
