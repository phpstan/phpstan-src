<?php declare(strict_types = 1);

namespace DotsInPathResultCache;

class ScannedGreeter
{

	public function greet(string $name): string
	{
		return sprintf('Hello, %s', $name);
	}

}
