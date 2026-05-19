<?php declare(strict_types = 1);

namespace Bug11731;

function test(): void
{
	goto success;

	throw new \Exception();

	success:
	echo "OK";
}

function testUnreachableBetweenGotoAndLabel(): void
{
	goto end;

	echo "unreachable";

	end:
	echo "reachable";
}
