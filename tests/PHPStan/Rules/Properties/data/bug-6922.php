<?php // lint >= 8.0

namespace Bug6922;

class Person {

	public function __construct(
		public readonly string $name,
		public readonly bool $isDeveloper,
		public readonly bool $isAdmin
	) {

	}
}

class Proof
{
	public function test(?Person $person): void
	{
		if ($person?->isDeveloper === FALSE ||
			$person?->isAdmin === FALSE) {
			echo "Bug";
		}
	}
}
