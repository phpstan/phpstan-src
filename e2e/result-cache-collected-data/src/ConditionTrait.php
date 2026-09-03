<?php declare(strict_types = 1);

namespace ResultCacheE2ECollectedData;

trait ConditionTrait
{

	public function doCondition(): void
	{
		if (resultCacheE2ECollectedDataCondition()) {
			echo 'always';
		}
	}

}
