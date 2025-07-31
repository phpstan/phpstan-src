<?php

namespace Bug13307;

function dd(): never {
	exit(1);
}

class HelloWorld
{
	public function testMethod(): string
	{
		var_dump("\Bug13307\dd");

		return "test";
	}

	public function testMethod2(): string
	{
		var_dump("\Bug13307\DD");

		return "test";
	}
}
