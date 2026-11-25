<?php declare(strict_types = 1);

namespace Bug15394;

function x(): void {
	$b = 1;
	declare(ticks=1) {
		echo $b;
	}
}

function writeInside(int $a): void
{
	$foo = 1;
	declare(ticks=1) {
		$foo = $a;
	}
	echo $foo;
}

function neverRead(): void
{
	declare(ticks=1) {
		$foo = 1;
	}
}

function declareInBlock(): void
{
	$b = 1;
	{
		declare(ticks=1) {
			echo $b;
		}
	}
}

function blockInDeclare(): void
{
	$b = 1;
	declare(ticks=1) {
		{
			echo $b;
		}
	}
}
