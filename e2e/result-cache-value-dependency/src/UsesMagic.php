<?php

namespace ResultCacheE2EValueDependency;

function usesMagic(Magic $magic): string
{
	return $magic->greet();
}
