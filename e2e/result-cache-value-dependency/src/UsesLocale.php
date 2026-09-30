<?php

namespace ResultCacheE2EValueDependency;

/**
 * @return 'none'
 */
function usesLocale(): string
{
	return parameter('locale');
}
