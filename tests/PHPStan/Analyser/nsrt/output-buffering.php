<?php declare(strict_types = 1);

namespace OutputBuffering;

use function PHPStan\Testing\assertType;

function noBuffer(): void
{
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
	ob_clean();
	assertType('string', ob_get_contents());
	ob_flush();
	assertType('string', ob_get_contents());
}

function getCleanClosesBuffer(): void
{
	ob_start();
	assertType('string', ob_get_clean());
	assertType('string|false', ob_get_contents());
}

function getFlushClosesBuffer(): void
{
	ob_start();
	assertType('string', ob_get_flush());
	assertType('string|false', ob_get_contents());
}

function endCleanClosesBuffer(): void
{
	ob_start();
	assertType('string', ob_get_contents());
	ob_end_clean();
	assertType('string|false', ob_get_contents());
}

function endFlushClosesBuffer(): void
{
	ob_start();
	assertType('string', ob_get_contents());
	ob_end_flush();
	assertType('string|false', ob_get_contents());
}

function nested(): void
{
	ob_start();
	ob_start();
	assertType('string', ob_get_contents());
	ob_end_clean();
	assertType('string', ob_get_contents());
	ob_end_clean();
	assertType('string|false', ob_get_contents());
}

function conditional(bool $cond): void
{
	if ($cond) {
		ob_start();
	}
	assertType('string|false', ob_get_contents());
}

function fullyQualified(): void
{
	\ob_start();
	assertType('string', \ob_get_contents());
	assertType('string', ob_get_contents());
}
