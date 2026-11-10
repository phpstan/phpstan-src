<?php

namespace ResultCacheE2EPartialFunctionApplication;

function bindToMethod(Foo $foo): \Closure
{
	return $foo->m(Dep::value(), ?);
}
