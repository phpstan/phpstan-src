<?php

declare(strict_types = 1);

namespace Bug15292Callable;

class BigContainer
{

}

function doFoo(): void
{
	usort($list, ['Bug15292Callable\BigContainer', 'callable_ident']);
}
