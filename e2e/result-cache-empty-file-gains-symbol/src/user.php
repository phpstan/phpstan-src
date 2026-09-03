<?php declare(strict_types = 1);

namespace ResultCacheE2EEmptyFileGainsSymbol;

function doUser(): void
{
	$t = new Thing();
	echo $t->doThing();
}
