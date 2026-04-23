<?php

namespace TestResultCache9;

class Bar
{

	/**
	 * @param list<string> $strings
	 */
	public function acceptStrings(array $strings): void
	{
	}

	public function doBar(Foo $foo): void
	{
		$this->acceptStrings($foo->x);
	}

}
