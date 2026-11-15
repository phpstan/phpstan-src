<?php

namespace ResultCacheE2EFirstClassCallable;

function staticMethodFcc(): string
{
	$f = Dep::s(...);

	return $f();
}
