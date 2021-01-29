<?php

namespace Bug4455;

class HelloWorld
{
	public function sayHello(string $_): bool
	{
		if($_ ===''){
			return true;
		}

		$this->nope();
	}

	/**
	 * @psalm-pure
	 * @return never
	 */
	function nope() {
		throw new \Exception();
	}
}
