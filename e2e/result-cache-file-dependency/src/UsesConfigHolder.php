<?php

namespace ResultCacheE2EFileDependency;

function usesConfigHolder(ConfigHolder $holder): void
{
	$holder->check();
}
