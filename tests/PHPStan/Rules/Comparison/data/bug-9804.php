<?php

namespace Bug9804;

class Test
{
	public function error(?string $someString): void
	{
		// Line below is the only difference to "pass" method
		if (!$someString) {
			return;
		}

		// Strict comparison using === between int<min, -1>|int<1, max> and 0 will always evaluate to false.
		$firstLetterAsInt = (int)substr($someString, 0, 1);
		if ($firstLetterAsInt === 0) {
			return;
		}
	}

	public function pass(?string $someString): void
	{
		// Line below is the only difference to "error" method
		if ($someString === null) {
			return;
		}

		// All ok
		$firstLetterAsInt = (int)substr($someString, 0, 1);
		if ($firstLetterAsInt === 0) {
			return;
		}
	}
}
