<?php

namespace Bug5804;

class Blah {

	/** @var ?int[] */
	public $value;

	public function doFoo()
	{
		$this->value[] = 'hello';
	}

	public function doBar()
	{
		$this->value[] = new Blah;
	}
}
