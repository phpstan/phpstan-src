<?php

namespace Bug7251;

class Foo
{
	private $bar;

	public function doStuff()
	{
		$this->setToOne($this->bar);
	}

	private function setToOne(&$var)
	{
		$var = 1;
	}
}
