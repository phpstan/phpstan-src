<?php

namespace ResultCacheE2EFirstClassCallable;

function unrelated(): \Closure
{
	return strlen(...);
}
