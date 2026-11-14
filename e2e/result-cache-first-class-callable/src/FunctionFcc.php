<?php

namespace ResultCacheE2EFirstClassCallable;

function functionFcc(): string
{
	$f = f(...);

	return $f();
}
