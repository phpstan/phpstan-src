<?php declare(strict_types = 1);

function resultCacheE2ECollectedDataHelper(): int
{
	return 1;
}

/**
 * @return true
 */
function resultCacheE2ECollectedDataCondition(): bool
{
	echo 'condition';

	return true;
}
