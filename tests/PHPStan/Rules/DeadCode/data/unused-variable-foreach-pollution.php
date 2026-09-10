<?php

namespace UnusedVariableForeachPollution;

function initializeBeforeLoop(): int
{
	$value = 0;
	foreach ([1, 2] as $item) {
		$value = $item;
	}
	return $value;
}
