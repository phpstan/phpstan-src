<?php

namespace ResultCacheE2EPartialFunctionApplication;

function unrelated(): \Closure
{
	return f(...);
}
