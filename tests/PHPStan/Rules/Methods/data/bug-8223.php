<?php declare(strict_types = 1);

namespace Bug8223;

class HelloWorld
{
	public function sayHello(string $modify): \DateTimeImmutable
	{
		$date = new \DateTimeImmutable();

		return $date->modify($modify);
	}

	/**
	 * @return array<\DateTimeImmutable>
	 */
	public function sayHello2(string $modify): array
	{
		$date = new \DateTimeImmutable();

		return [$date->modify($modify)];
	}

	public function test()
	{
		$r = new HelloWorld();

		$r->sayHello('ss');
	}
}
