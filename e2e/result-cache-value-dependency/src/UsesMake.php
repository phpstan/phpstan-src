<?php

namespace ResultCacheE2EValueDependency;

function usesMake(): int
{
	return make('ResultCacheE2EValueDependency\Lib\Gadget')->run();
}
