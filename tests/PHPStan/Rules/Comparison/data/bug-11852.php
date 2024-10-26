<?php // lint >= 8.0

namespace Bug11852;

function sayHello(int $type, string $activity): int
{
	return match("$type:$activity") {
		'159:Work' => 12,
		'159:education' => 19,

		default => throw new \InvalidArgumentException("unknown values $type:$activity"),
	};
}
