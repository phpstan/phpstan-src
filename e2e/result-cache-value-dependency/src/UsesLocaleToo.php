<?php

namespace ResultCacheE2EValueDependency;

/**
 * @return 'none'
 */
function usesLocaleToo(): string
{
	return parameter('locale');
}
