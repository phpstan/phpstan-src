<?php declare(strict_types = 1);

namespace ResultCacheE2EExportedNodes\FunctionByRef;

function user(): mixed
{
	return array_pop(values());
}
