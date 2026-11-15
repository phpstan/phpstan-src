<?php

namespace ResultCacheE2EFirstClassCallable;

function methodFcc(Dep $dep): string
{
	$f = $dep->m(...);

	return $f();
}
