<?php declare(strict_types = 1);

namespace CallConditionCrossFileMarkerB;

function doBar(int $x): void
{
	if (is_int($x)) {
	}
}

function is_int($value): \stdClass
{
	return new \stdClass();
}
