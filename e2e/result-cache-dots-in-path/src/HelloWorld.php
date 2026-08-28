<?php declare(strict_types = 1);

namespace DotsInPathResultCache;

class HelloWorld
{

	/** @return non-empty-string */
	public function sayHello(): string
	{
		return (new ScannedGreeter())->greet('PHPStan');
	}

}
