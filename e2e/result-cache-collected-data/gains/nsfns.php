<?php declare(strict_types = 1);

namespace ResultCacheE2ECollectedData;

function resultCacheE2ECollectedDataHelper(): int
{
	echo 'side effect';

	return 1;
}

function resultCacheE2ECollectedDataCondition(): bool
{
	return (bool) rand(0, 1);
}

class Missing
{

}
