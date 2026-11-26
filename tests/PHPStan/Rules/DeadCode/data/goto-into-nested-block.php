<?php declare(strict_types = 1);

namespace GotoIntoNestedBlock;

function jumpedInto(bool $b): void
{
	goto inside;
	if ($b) {
		echo 'skipped';
		inside:
		echo 'reached';
	}
}

function neverJumpedInto(bool $b): void
{
	return;
	if ($b) {
		inside:
		echo 'unreachable';
	}
	goto inside;
}
