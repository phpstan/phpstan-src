<?php declare(strict_types = 1);

namespace Bug13092;

class HelloWorld
{
	public function sayHello(): void
	{
		$shoppers = \random_int(1000, 10000);
		$transactions = \random_int($shoppers, $shoppers * 3);
	}

	public function shouldStillReport(): void
	{
		$shoppers = \random_int(1000, 10000);
		$transactions = \random_int($shoppers, $shoppers - 1);
	}
}
