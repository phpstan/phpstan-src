<?php

class OldStyleConstructorOnPhp8
{

	public function OldStyleConstructorOnPhp8(int $i)
	{

	}

	public static function create(): self
	{
		return new self(1);
	}

}

function () {
	new OldStyleConstructorOnPhp8();
	new OldStyleConstructorOnPhp8(1);
};
