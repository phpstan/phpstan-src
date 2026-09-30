<?php

namespace ResultCacheE2EValueDependency;

function usesCache(): object
{
	return service('cache');
}
