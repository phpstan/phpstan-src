<?php declare(strict_types = 1);

namespace Bug15394;

function test(): void
{
	$x = 1;
	declare(ticks=1) {
		echo $x;
	}
}

function testAssignInsideDeclare(): void
{
	declare(ticks=1) {
		$y = 2;
	}
	echo $y;
}

function testUnusedInsideDeclare(): void
{
	declare(ticks=1) {
		$z = 3;
	}
}
