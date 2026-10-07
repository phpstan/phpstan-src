<?php declare(strict_types = 1);

namespace Bug15394;

function readInBlock(): void
{
	$value = 1;
	declare(ticks=1) {
		echo $value;
	}
}

function writeInBlock(): void
{
	declare(ticks=1) {
		$value = 1;
	}
	echo $value;
}

function readInNestedBlock(): void
{
	$value = 1;
	declare(ticks=1) {
		declare(ticks=2) {
			echo $value;
		}
	}
}

function unusedInBlock(): void
{
	declare(ticks=1) {
		$unused = 1;
	}
}

function overwrittenInBlock(): void
{
	$value = 1;
	declare(ticks=1) {
		$value = 2;
	}
	echo $value;
}

function readAfterDeclareStatement(): void
{
	$value = 1;
	declare(ticks=1);
	echo $value;
}

function readInAlternativeBlock(): void
{
	$value = 1;
	declare(ticks=1):
		echo $value;
	enddeclare;
}
