<?php declare(strict_types = 1);

namespace ResultCacheE2EExportedNodes\EnumCaseValue;

function user(): bool
{
	return Foo::First->value === 1;
}
