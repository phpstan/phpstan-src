<?php

namespace IsStringCertainty;

function maybeDefinedVariable(int $i, string $s): void
{
	if (rand(0, 1)) {
		$a = rand(0,1) ? $i : $s;
	}

	if (is_string($a)) {
		echo $a; // we don't want an error here
	}
}


function isStringNoCertainty(): void
{
	if (is_string($a)) {
		echo $a; // we don't want an error here
	}
}
