<?php declare(strict_types = 1);

namespace Bug15394;

function x(): void {

	$b = 1;
	declare(ticks=1) {
		echo $b;
	}
}

function writeInsideReadAfter(): void
{
	declare(ticks=1) {
		$a = 1;
	}

	echo $a;
}

function overwrittenInside(): void
{
	$a = 1;
	declare(ticks=1) {
		$a = 2;
	}

	echo $a;
}

function unusedInside(): void
{
	declare(ticks=1) {
		$a = 1;
	}
}

function nested(): void
{
	$a = 1;
	declare(ticks=1) {
		declare(ticks=2) {
			echo $a;
		}
	}
}
