<?php

namespace Bug7270;

class Foo
{

	/** @var bool */
	private $current = false;

	public function setCurrent(): void
	{
		$this->current = false;
	}

	public function getCurrent(): bool
	{
		return $this->current;
	}

}

function (): void {
	$a = (bool) rand(0, 1);
	$obj = new Foo();
	$a && $obj->setCurrent();

	var_dump($obj->getCurrent());
};
