<?php

namespace ResultCacheE2EFileDependency;

/**
 * @return 'none'
 */
function usesMethod(Repository $repository): string
{
	return $repository->methodData();
}
