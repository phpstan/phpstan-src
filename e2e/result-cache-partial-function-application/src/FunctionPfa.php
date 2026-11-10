<?php

namespace ResultCacheE2EPartialFunctionApplication;

function bindToFunction(): \Closure
{
	return f(Dep::value(), ?);
}
