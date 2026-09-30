<?php

namespace ResultCacheE2EFileDependency;

/**
 * @return 'none'
 */
function usesStaticMethod(): string
{
	return Repository::staticMethodData();
}
