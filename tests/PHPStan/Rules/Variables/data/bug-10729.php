<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug10729;

class HelloWorld
{
	public function sayHello(?\DateTimeImmutable $date): void
	{
		var_dump($date?->format($format = "Y-m-d"));
		var_dump($format); // might not be defined if $date is null
	}

	public function nonNullable(\DateTimeImmutable $date): void
	{
		var_dump($date->format($format = "Y-m-d"));
		var_dump($format); // always defined, $date can't be null
	}

	public function nullOnly(): void
	{
		$date = null;
		var_dump($date?->format($format = "Y-m-d"));
		var_dump($format); // undefined, $date is always null
	}

	public function multipleArgs(?\DateTimeImmutable $date): void
	{
		$date?->createFromFormat($format = 'Y-m-d', $value = '2024-01-01');
		var_dump($format); // might not be defined
		var_dump($value); // might not be defined
	}

	public function nestedAssignment(?\DateTimeImmutable $date): void
	{
		$result = $date?->format($format = "Y-m-d");
		var_dump($format); // might not be defined
	}

	public function existingVarStillDefined(?\DateTimeImmutable $date): void
	{
		$existing = 'before';
		$date?->format($format = "Y-m-d");
		var_dump($existing); // always defined, not affected by nullsafe
	}
}
