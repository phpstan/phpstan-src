<?php // lint >= 8.0

namespace CallablesNullsafe;

class Bar
{

	public int $val;

}

function doFoo(?Bar $bar): void
{
	$fn = function (int $val) {

	};

	$fn($bar?->val);
}

