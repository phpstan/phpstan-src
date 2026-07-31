<?php declare(strict_types = 1);

namespace CallConditionCrossFileB;

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
