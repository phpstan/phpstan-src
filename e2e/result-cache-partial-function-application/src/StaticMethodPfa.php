<?php

namespace ResultCacheE2EPartialFunctionApplication;

function bindToStaticMethod(): \Closure
{
	return Foo::s(?, Dep::value());
}
