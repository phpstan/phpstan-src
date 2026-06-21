<?php declare(strict_types = 1);

namespace OutputBuffering;

use function PHPStan\Testing\assertType;

function noBuffer(): void
{
	assertType('int<0, max>', ob_get_level());
	assertType('string|false', ob_get_contents());
	assertType('string|false', ob_get_clean());
	assertType('string|false', ob_get_flush());
	assertType('int|false', ob_get_length());
}

function activeBuffer(): void
{
	ob_start();
	assertType('int<1, max>', ob_get_level());
	assertType('string', ob_get_contents());
	assertType('int', ob_get_length());
}

function obCleanAndFlushKeepBuffer(): void
{
	ob_start();
	assertType('int<1, max>', ob_get_level());
	ob_clean();
	assertType('int<1, max>', ob_get_level());
	assertType('string', ob_get_contents());
	ob_flush();
	assertType('int<1, max>', ob_get_level());
	assertType('string', ob_get_contents());
}

function getCleanClosesBuffer(): void
{
	ob_start();
	assertType('int<1, max>', ob_get_level());
	assertType('string', ob_get_clean());
	assertType('int<0, max>', ob_get_level());
	assertType('string|false', ob_get_contents());
}

function getFlushClosesBuffer(): void
{
	ob_start();
	assertType('int<1, max>', ob_get_level());
	assertType('string', ob_get_flush());
	assertType('int<0, max>', ob_get_level());
	assertType('string|false', ob_get_contents());
}

function endCleanClosesBuffer(): void
{
	ob_start();
	assertType('int<1, max>', ob_get_level());
	assertType('string', ob_get_contents());
	ob_end_clean();
	assertType('int<0, max>', ob_get_level());
	assertType('string|false', ob_get_contents());
}

function endFlushClosesBuffer(): void
{
	ob_start();
	assertType('int<1, max>', ob_get_level());
	assertType('string', ob_get_contents());
	ob_end_flush();
	assertType('int<0, max>', ob_get_level());
	assertType('string|false', ob_get_contents());
}

function nested(): void
{
	ob_start();
	assertType('int<1, max>', ob_get_level());
	ob_start();
	assertType('int<2, max>', ob_get_level());
	assertType('string', ob_get_contents());
	ob_end_clean();
	assertType('int<1, max>', ob_get_level());
	assertType('string', ob_get_contents());
	ob_end_clean();
	assertType('int<0, max>', ob_get_level());
	assertType('string|false', ob_get_contents());
}

function conditional(bool $cond): void
{
	if ($cond) {
		ob_start();
	}
	assertType('int<0, max>', ob_get_level());
	assertType('string|false', ob_get_contents());
}

function fullyQualified(): void
{
	\ob_start();
	assertType('int<1, max>', ob_get_level());
	assertType('string', \ob_get_contents());
	assertType('string', ob_get_contents());
}

function levelNarrowedToConstInt(): void
{
	if (ob_get_level() === 2) {
		assertType('2', ob_get_level());
		assertType('string', ob_get_clean());
		// closing call decrements the const-int level, keeping it exact
		assertType('1', ob_get_level());
	}
}

function levelNarrowedToRegularInt(): void
{
	assertType('int<0, max>', ob_get_level());
	assertType('string|false', ob_get_clean());
}

function levelNarrowedToIntRange(): void
{
	if (ob_get_level() >= 1) {
		assertType('int<1, max>', ob_get_level());
		assertType('string', ob_get_clean());
	}
}

function levelNarrowedToUnionInt(): void
{
	if (ob_get_level() === 1 || ob_get_level() === 3) {
		assertType('1|3', ob_get_level());
		assertType('string', ob_get_contents());
		ob_start();
		assertType('2|4', ob_get_level());
		assertType('string', ob_get_contents());
	}
}

function levelNarrowedToZeroConstInt(): void
{
	if (ob_get_level() === 0) {
		assertType('0', ob_get_level());
		assertType('string|false', ob_get_clean());
	}
}

function levelNarrowedToBoundedIntRange(): void
{
	if (ob_get_level() >= 2 && ob_get_level() <= 5) {
		assertType('int<2, 5>', ob_get_level());
		assertType('string', ob_get_clean());
		// closing call shifts the whole range down, preserving the upper bound
		assertType('int<1, 4>', ob_get_level());
	}
}
