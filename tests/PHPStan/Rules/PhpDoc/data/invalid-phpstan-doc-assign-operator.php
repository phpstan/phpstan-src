<?php

namespace InvalidPHPStanDocAssignOperator;

class Boo extends Baz
{
	function baz()
	{
		/** @phpstan-va */
		$c ??= 'foo';
	}
}
