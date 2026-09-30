<?php

namespace ResultCacheE2EValueDependency;

function usesLogger(): void
{
	service('logger')->log();
}
