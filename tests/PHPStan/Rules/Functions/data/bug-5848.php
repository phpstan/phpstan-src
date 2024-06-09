<?php declare(strict_types = 1);

namespace Bug5848;

class HelloWorld
{
	public function test() : void{
		var_dump(array_diff([new \stdClass], [new \stdClass]));
	}
}

(new HelloWorld())->test();
