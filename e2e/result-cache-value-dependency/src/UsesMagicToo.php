<?php

namespace ResultCacheE2EValueDependency;

function usesMagicToo(Magic $magic): string
{
	return $magic->greet();
}
