<?php declare(strict_types = 1);

namespace CallConditionCrossFileA;

class Check
{

	/** @return true */
	public function ok(): bool
	{
		return true;
	}

}

function doFoo(Check $c): void
{
	if ($c->ok()) {
	}
}
