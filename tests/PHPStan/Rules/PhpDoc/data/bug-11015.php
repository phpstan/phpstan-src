<?php declare(strict_types = 1);

namespace Bug11015;

class HelloWorld
{
	public function sayHello(PDOStatement $date): void
	{
		$b = $date->fetch();
		if (empty($b)) {
			return;
		}

		/** @var array<string, int> $b */
		echo $b['a'];
	}

	public function sayHello2(PDOStatement $date): void
	{
		$b = $date->fetch();

		/** @var array<string, int> $b */
		echo $b['a'];
	}
}
