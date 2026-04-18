<?php declare(strict_types = 1);

namespace BugSelfReferencedTrait;

trait RecursiveTrait
{
	public function getRecursive(): object
	{
		return new class () {
			use RecursiveTrait;
		};
	}

}
