<?php

namespace Bug10291;

class HelloWorld
{
	/** @return void|int */
	public function sayHello()
	{
		return rand(0, 1) ? 17 : null;
	}

	/**
	 * @param bool $something
	 * @return void|int
	 */
	public function sayHello2($something)
	{
		if ($something) {
			return rand(0, 1);
		} else {
			return $this->myrand();
		}
	}
}
