<?php declare(strict_types = 1);

namespace UnusedFunctionParametersDeclare;

function readInsideDeclare(int $x): void
{
	declare(ticks=1) {
		echo $x;
	}
}

function unusedWithDeclare(int $x): void
{
	declare(ticks=1) {
		echo 1;
	}
}
