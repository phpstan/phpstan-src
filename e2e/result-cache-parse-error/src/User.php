<?php declare(strict_types = 1);

namespace ResultCacheE2EParseError;

function doUser(): int
{
	return (new Thing())->doThing();
}
