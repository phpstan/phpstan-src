<?php

namespace Bug3514;

trait MyTrait
{
	protected function myMethod() : void{
		echo 'hello';
	}
}


class HelloWorld
{
	use MyTrait{ MyMethod as myRenamedMethod;}

	public function sayHello(int $date): void
	{
		$this->myRenamedMethod();
	}
}
