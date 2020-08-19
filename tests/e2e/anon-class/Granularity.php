<?php declare(strict_types=1);

class Granularity
{

	/**
	 * @return mixed[]
	 */
	protected static function provideInstances(): array
	{
		$myclass = new class() extends Granularity { };

		return [];
	}

}
