<?php declare(strict_types = 1);

namespace RelativePathResultCache;

/**
 * A constant condition inside a trait makes ConstantConditionInTraitCollector emit collected data
 * carrying an Error, whose paths live inside the collected value rather than in the cache's error
 * section. Those need relativizing too - see https://github.com/phpstan/phpstan-src/pull/6190
 */
trait TraitWithConstantCondition
{

	public function alwaysFalse(): bool
	{
		$one = 1;

		return !$one;
	}

}

class UsesTraitOnce
{

	use TraitWithConstantCondition;

}

class UsesTraitTwice
{

	use TraitWithConstantCondition;

}
