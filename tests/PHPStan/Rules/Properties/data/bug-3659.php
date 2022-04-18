<?php

namespace Bug3659;

class Foo
{
	public function func1(object $obj): void
	{
		$this->func2($obj->someProperty ?? null);
	}

	public function func2(?string $param): void
	{
		echo $param ?? 'test';
	}
}

