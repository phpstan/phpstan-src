<?php declare(strict_types = 1);

namespace Bug9684;

trait RecursiveTrait
{
	public function getRecursive(): object
	{
		return new class () {
			use RecursiveTrait;
		};
	}

}
